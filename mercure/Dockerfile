FROM dunglas/mercure:latest

# Environment variables (will be overridden by Railway)
ENV MERCURE_PUBLISHER_JWT_KEY=${MERCURE_JWT_KEY:-!ChangeThisMercureSecretKey!}
ENV MERCURE_SUBSCRIBER_JWT_KEY=${MERCURE_JWT_KEY:-!ChangeThisMercureSecretKey!}
ENV MERCURE_ALLOW_ANONYMOUS=${MERCURE_ALLOW_ANONYMOUS:-1}
ENV MERCURE_CORS_ALLOWED_ORIGINS="*"
ENV MERCURE_DEBUG="0"
ENV MERCURE_PORT="8080"

# Expose port
EXPOSE 8080

# Start Mercure
CMD ["/usr/bin/caddy", "run", "--config", "/etc/caddy/Caddyfile"]
