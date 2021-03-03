# Wordpress Password Encoder for Shopware 6
This tiny plugin simply adds the Wordpress Password Encoder to Shopware 6 and thus allows you to import your existing WooCommerce customers into Shopware _without_ loosing their passwords or having them reset their passwords. This plugin has been written to import CSV files created with the [Shopware 6 Exporter for WooCommerce](https://github.com/vardumper/shopware-six-exporter) Wordpress-Plugin. 

## Installation
```
composer require vardumper/wordpress-password-encoder-for-shopware-six
bin/console plugin:refresh
bin/console plugin:install WordpressPasswordEncoder
bin/console plugin:activate WordpressPasswordEncoder
```

Alternatively download the zip and install in the Shopware Backend.
