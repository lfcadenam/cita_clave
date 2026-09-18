# Etapa 1: Compilación de Frontend Angular 19
FROM node:20-alpine AS build

WORKDIR /app

COPY frontend/package*.json ./
RUN npm install

COPY frontend/ ./
RUN npm run build

# Etapa 2: Servidor Nginx de Alto Rendimiento
FROM nginx:alpine

# Reemplazar configuración default por la configuración de Cita Clave
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf

# Copiar aplicación Angular compilada
COPY --from=build /app/dist/frontend/browser /var/www/frontend

# Copiar activos públicos del backend (tema CSS, imágenes, favicon)
COPY backend/public /var/www/backend/public

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]