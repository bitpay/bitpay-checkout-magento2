docker-php-ext-install ftp
docker-php-ext-enable ftp

composer config audit.block-insecure false
composer config repositories.base composer https://repo.magento.com/
