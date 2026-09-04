# ---- build stage --------------------------------------------------------
FROM node:18-alpine AS build

WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install

COPY . .
RUN npm run build -- --configuration production

# ---- serve stage ---------------------------------------------------------
FROM nginx:1.27-alpine

COPY --from=build /app/dist/crm-frontend /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
