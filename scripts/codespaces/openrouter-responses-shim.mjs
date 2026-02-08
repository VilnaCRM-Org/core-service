#!/usr/bin/env node

import http from "node:http";

const bindHost = process.env.OPENROUTER_SHIM_BIND_HOST ?? "127.0.0.1";
const port = Number.parseInt(process.env.OPENROUTER_SHIM_PORT ?? "18082", 10);
const upstreamBaseUrl = (process.env.OPENROUTER_SHIM_UPSTREAM_BASE_URL ?? "https://openrouter.ai").replace(/\/+$/, "");

if (!Number.isInteger(port) || port <= 0 || port > 65535) {
    console.error("Invalid OPENROUTER_SHIM_PORT value.");
    process.exit(1);
}

const hopByHopHeaders = new Set([
    "connection",
    "content-length",
    "keep-alive",
    "proxy-authenticate",
    "proxy-authorization",
    "te",
    "trailer",
    "transfer-encoding",
    "upgrade",
]);

const copyResponseHeaders = (headers, response) => {
    for (const [name, value] of headers.entries()) {
        if (hopByHopHeaders.has(name.toLowerCase())) {
            continue;
        }
        response.setHeader(name, value);
    }
};

const sanitizeRequestBody = (requestBody) => {
    if (!requestBody || typeof requestBody !== "object" || !Array.isArray(requestBody.input)) {
        return requestBody;
    }

    requestBody.input = requestBody.input.filter(
        (item) => !(item && typeof item === "object" && item.type === "reasoning"),
    );

    return requestBody;
};

const server = http.createServer(async (request, response) => {
    if (request.method === "GET" && request.url === "/healthz") {
        response.statusCode = 200;
        response.setHeader("content-type", "application/json");
        response.end(JSON.stringify({ status: "ok" }));
        return;
    }

    if (request.method !== "POST") {
        response.statusCode = 405;
        response.end("Method Not Allowed");
        return;
    }

    const chunks = [];
    for await (const chunk of request) {
        chunks.push(chunk);
    }

    const rawBody = Buffer.concat(chunks).toString("utf8");
    let outboundBody = rawBody;

    const contentType = request.headers["content-type"] ?? "";
    if (typeof contentType === "string" && contentType.toLowerCase().includes("application/json")) {
        try {
            const parsed = JSON.parse(rawBody);
            outboundBody = JSON.stringify(sanitizeRequestBody(parsed));
        } catch {
            // Pass-through malformed body unchanged.
        }
    }

    const upstreamUrl = `${upstreamBaseUrl}${request.url ?? ""}`;
    const headers = { ...request.headers };
    delete headers.host;
    delete headers["content-length"];
    delete headers.connection;

    try {
        const upstreamResponse = await fetch(upstreamUrl, {
            method: "POST",
            headers,
            body: outboundBody,
        });

        const responseBody = Buffer.from(await upstreamResponse.arrayBuffer());
        response.statusCode = upstreamResponse.status;
        copyResponseHeaders(upstreamResponse.headers, response);
        response.end(responseBody);
    } catch (error) {
        console.error("OpenRouter shim upstream request failed:", error);
        response.statusCode = 502;
        response.setHeader("content-type", "application/json");
        response.end(
            JSON.stringify({
                error: "upstream_request_failed",
                message: "An upstream request failed.",
            }),
        );
    }
});

server.listen(port, bindHost, () => {
    console.log(`OpenRouter responses shim listening on http://${bindHost}:${port}`);
});
