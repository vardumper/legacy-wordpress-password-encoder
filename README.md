# Wordpress Password Encoder for Shopware 6
This tiny plugin simply adds the Wordpress Password Encoder to Shopware 6 and thus allows you to import your existing WooCommerce customers into Shopware _without_ loosing their passwords or having them reset their passwords. Make sure to use the `wordpress` encoder when importing users.

## Installation
```
composer require vardumper/legacy-wordpress-password-encoder
bin/console plugin:refresh
bin/console plugin:install WordpressPasswordEncoder
bin/console plugin:activate WordpressPasswordEncoder
```
