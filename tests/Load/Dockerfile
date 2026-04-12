ARG XK6_VERSION=v1.3.7
ARG K6_VERSION=v1.7.1
ARG XK6_FILE_VERSION=v1.6.1
ARG XK6_COUNTER_VERSION=v0.0.1

FROM golang:1.25-alpine AS builder

ARG XK6_VERSION
ARG K6_VERSION
ARG XK6_FILE_VERSION
ARG XK6_COUNTER_VERSION

RUN apk add --no-cache git

RUN go install go.k6.io/xk6/cmd/xk6@${XK6_VERSION} && \
    xk6 build --k6-version ${K6_VERSION} \
              --with github.com/avitalique/xk6-file@${XK6_FILE_VERSION} \
              --with github.com/mstoykov/xk6-counter@${XK6_COUNTER_VERSION}

FROM grafana/k6:1.7.1

COPY --from=builder /go/k6 /usr/bin/k6

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD k6 version || exit 1
