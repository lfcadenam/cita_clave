FROM nginx:alpine

# Reemplazar configuración predeterminada de Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copiar archivos estáticos de la landing page
COPY . /usr/share/nginx/html

# Eliminar archivos innecesarios del root web
RUN rm -f /usr/share/nginx/html/Dockerfile \
          /usr/share/nginx/html/nginx.conf \
          /usr/share/nginx/html/README.md

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
