FROM php:apache 
#necesaria

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
#necesaria

RUN echo 'AcceptPathInfo On' >> /etc/apache2/apache2.conf
#necesaria 

COPY . /var/www/html/
#copia el proyecto a la carpeta que sirve Apache

# Apache corre como el usuario www-data, pero COPY deja los archivos como root.
# Sin este permiso no se pueden guardar las fotos de las incidencias
# ("No se pudo guardar la fotografía").
RUN mkdir -p /var/www/html/backend/uploads/incidencias \
    && chown -R www-data:www-data /var/www/html/backend/uploads
