FROM php:apache
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN echo 'AcceptPathInfo On' >> /etc/apache2/apache2.conf
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/

