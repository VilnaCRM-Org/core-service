FROM golang:1.24-alpine AS builder

RUN apk add --no-cache git=2.47.1-r0

RUN go install go.k6.io/xk6/cmd/xk6@v0.11.0 && \
    xk6 build --with github.com/avitalique/xk6-file@v1.7.0 \
              --with github.com/mstoykov/xk6-counter@v0.1.1

FROM grafana/k6:0.54.0

RUN adduser -D -u 1000 loadtester
USER loadtester

COPY --from=builder /go/k6 /usr/bin/k6

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD k6 version || exit 1
