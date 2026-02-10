# Utilizamos una imagen oficial de PHP 8.1 como imagen base
FROM php:8.4-apache

# Instalamos los paquetes necesarios
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
    libpq-dev \
    git \ 
    curl \
    vim \
    unzip \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Configuramos los módulos de PHP
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql intl

# Instalamos y habilitamos Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug


# Habilita mod_rewrite
RUN a2enmod rewrite

# Establecemos la raíz web de Apache en el directorio público del proyecto
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Copiamos nuestra aplicación a la carpeta de trabajo del contenedor
COPY . /var/www/html/

# Allow Override all cambio 
COPY config/apache2.conf /etc/apache2/apache2.conf

# Establecemos la carpeta de trabajo
WORKDIR /var/www/html/

# Ejecutamos los comandos necesarios para instalar las dependencias de PHP y ejecutar nuestro proyecto
RUN curl -sS https://getcomposer.org/installer | php \
    && php composer.phar install \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/var
    
# Copiamos configuraci   n de Xdebug
COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Copiamos a la imagen el fichero de configuracion del virtualhost
COPY ./config/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

# Activamos el modulo ssl
RUN a2enmod ssl

# Creamos el directorio raiz y lo activamos
RUN a2dissite 000-default && a2ensite default-ssl

# El contenedor escucha en los puertos TCP 80 y 443
EXPOSE 443

# Exponemos el puerto 80 para el tr  fico HTTP
EXPOSE 80

# Iniciamos el servidor Apache en primer plano
CMD ["apache2-foreground"]

