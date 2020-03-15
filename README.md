# weatherstation-server - Weather station - PHP server

This project is meant to visualize the data of your own Arduino based weather stations. It is designed for a local home network, hence there is no encryption available at the moment. The server together with the database runs fine on a Raspberry Pi 3b+ but it should also be possible on an Raspberry Pi Zero and similar devices, depending on how many clients will connect and how much traffic there will be.

The client code is available in the repository weatherstation-client (TODO).

## Features

* Support for multiple individual weather station clients
* Tracking of multiple data sources per client
* REST API for data acquisition
* Simple browser interfaces with ChartJS to visualize your data with dynamic reloading

## Prerequisites

* MySQL, MariaDB
* PHP 7+

## Installation

The installation process is based on my own setup. Because I installed and developed it in a more complicated setup some steps might be not correct for your setup. Feel free to create a pull request to make some corrections.

### MySQL

```bash
$ sudo apt install mysql-server
$ sudo mysql
> create database weatherstation;
> create user weatherstation@localhost identified by 'YOUR_PASSWORD';
> grant all privileges on weatherstation.* to weathestation@localhost;
> quit;
```

At the moment you need to create the tables manually using the file `database_init.sql` inside this repository.

### PHP and Apache

```bash
$ sudo apt install apache2 php php-mysql php-mbstring php-json php-opcache
$ sudo -u www-data mkdir -p /var/www/weatherstation/htdocs
```

Create a new configuration as `root` in `/etc/apache2/conf-available/weatherstation.conf`:
```
Alias /weatherstation /var/www/weatherstation/htdocs
<Directory /var/www/weatherstation/htdocs>
        Options -Indexes +FollowSymLinks
        <IfModule mod_dir.c>
                DirectoryIndex index.php
        </IfModule>
</Directory>
```

Enable the configuration and make sure there is no error before reloading apache.
```bash
$ sudo a2enconf weatherstation.conf
$ sudo apachectl configtest
$ sudo systemctl reload apache2
```

### Clone repository

Become `www-data` and clone the files.
```bash
$ su -s/bin/bash www-data
$ cd /var/www/weatherstation/htdocs
$ git clone https://github.com/NicolasGoeddel/weatherstation-server.git .
$ cp config{.sample,}.php
```

Now configure your database connection in `config.php`. You should now be able to download this file through http://your.server/weatherstation/README.md

## Included Dependencies

* [Chart.js v2.9.3](https://github.com/chartjs/Chart.js/tree/v2.9.3)
* [bootstrap v4.4.1](https://github.com/twbs/bootstrap/tree/v4.4.1)
* [chartjs-plugin-zoom v0.7.5](https://github.com/chartjs/chartjs-plugin-zoom/tree/v0.7.5)
* [Hammer.JS v2.0.8](https://github.com/hammerjs/hammer.js/tree/v2.0.8)
* [jQuery v3.4.1](https://github.com/jquery/jquery/tree/3.4.1)
* [moment.js v2.24.0](https://github.com/moment/moment/tree/2.24.0)
* [popper.js v2.0.6](https://github.com/popperjs/popper-core/tree/v2.0.6)

## TODO

* Initializing database tables
* Configuration interface for clients and data types
* Support more database endpoints
* Client authentication
* User and roles management
* Create averages per user definable date ranges
