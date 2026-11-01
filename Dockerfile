<<<<<<< HEAD
FROM php:apache 
#necesaria

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
#necesaria

RUN echo 'AcceptPathInfo On' >> /etc/apache2/apache2.conf
#necesaria 

COPY . /var/www/html/
#no se porque esta

#RUN chown -R www-data:www-data /var/www/html/
#no se porque esta

