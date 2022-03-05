#1/bin/bash

php artisan serve & sleep 5

bash ./vendor/bin/phpunit
