# Immutable Nuxt SSR image. PHP is an internal data/business backend reached
# only through the exact NUXT_PHP_ORIGIN_ALLOWLIST value configured at runtime.

FROM node:24.20.0-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY . .
RUN npm run build

FROM node:24.20.0-alpine AS runtime
WORKDIR /app
ENV NODE_ENV=production \
    NITRO_PORT=3000 \
    NITRO_HOST=0.0.0.0
RUN addgroup -S nuxt && adduser -S nuxt -G nuxt
COPY --from=build --chown=nuxt:nuxt /app/.output ./.output
USER nuxt
EXPOSE 3000
#
# Probes /_migration/site, not /: the public "/" route 307-redirects to PHP
# whenever homePublicRouteEnabled is false (the safe default), which would
# make the container always report unhealthy before any route is cut over.
# /_migration/site is the internal data bridge - it is never flag-gated and
# still exercises real PHP connectivity. Uses wget (already in node:alpine)
# instead of spawning a fresh `node` process per probe: that cold start alone
# took >3s in testing and caused spurious unhealthy flaps under load.
HEALTHCHECK --interval=10s --timeout=5s --start-period=10s --retries=3 \
  CMD wget -q -O /dev/null http://127.0.0.1:3000/_migration/site || exit 1
CMD ["node", ".output/server/index.mjs"]
