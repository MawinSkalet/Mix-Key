# Multi-stage Dockerfile for Next.js Frontend

# --- Base Stage ---
FROM node:18-alpine AS base

WORKDIR /app

# Install dependencies needed for some node-gyp packages
RUN apk add --no-cache libc6-compat

# --- Development Stage ---
FROM base AS dev

EXPOSE 3000

ENV PORT 3000
ENV HOSTNAME "0.0.0.0"

# Auto-install npm dependencies if missing, then set dev running command
CMD sh -c "if [ ! -d node_modules ]; then npm install; fi && npm run dev"

# --- Production Stage ---
FROM base AS production

COPY package*.json ./
RUN npm ci --only=production

COPY . .

# Build the Next.js app
ENV NEXT_TELEMETRY_DISABLED 1
RUN npm run build

EXPOSE 3000

ENV PORT 3000
ENV HOSTNAME "0.0.0.0"

CMD ["npm", "run", "start"]
