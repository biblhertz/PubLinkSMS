FROM nginx:latest
 RUN rm /etc/nginx/conf.d/default.conf
 COPY ./html /var/www/html
 COPY ./nginx/nginx.conf /etc/nginx/conf.d/default.conf
 RUN mkdir -p /var/www/html/iiif_manifests
 RUN chown -R www-data:www-data /var/www/html
 RUN chmod -R +x /var/www/html/iiif_manifests
 