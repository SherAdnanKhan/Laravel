apt -qy update
apt -qy install curl git zip unzip
docker-php-ext-install pdo_mysql ctype bcmath zip

# Install Composer
curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install NPM
apt -qy install npm
