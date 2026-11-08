# Immutable Nuxt SSR image. Data comes from Laravel (NUXT_API_ORIGIN), which must be listed in
# NUXT_API_ORIGIN_ALLOWLIST at runtime.

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
# A static file proves the Nuxt server answers without depending on Laravel being up.
HEALTHCHECK --interval=10s --timeout=5s --start-period=10s --retries=3 \
  CMD wget -q -O /dev/null http://127.0.0.1:3000/robots.txt || exit 1
CMD ["node", ".output/server/index.mjs"]
