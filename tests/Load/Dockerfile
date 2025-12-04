FROM golang:1.25-alpine AS builder

RUN apk add --no-cache git

RUN go install go.k6.io/xk6/cmd/xk6@latest && \
    xk6 build --with github.com/avitalique/xk6-file@latest \
              --with github.com/mstoykov/xk6-counter@latest

FROM grafana/k6:latest

COPY --from=builder /go/k6 /usr/bin/k6

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD k6 version || exit 1
