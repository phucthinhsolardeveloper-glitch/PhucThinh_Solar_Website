# Nuxt SSR service image for staging/local rehearsal only. Does not change
# production PHP/Caddy; the server still only talks to PHP hosts explicitly
# listed in NUXT_PHP_ORIGIN_ALLOWLIST (frontend/server/utils/migration-api.ts).

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
HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=3 \
  CMD node -e "fetch('http://127.0.0.1:3000/migration-hub-preview').then(r=>{if(r.status!==200)throw new Error(r.status)}).catch(()=>process.exit(1))"
CMD ["node", ".output/server/index.mjs"]
