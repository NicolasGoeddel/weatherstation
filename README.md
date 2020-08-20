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

### tl;dr
Replace every occurence of `YOUR_PASSWORD` with the password you want to use for your database user.
```shell
pi@raspberry:~ $ sudo apt install mariadb-server php-fpm apache2 php-mbstring php-json php7.3-opcache php-mysql git
pi@raspberry:~ $ sudo mariadb
MariaDB [(none)]> create database weatherstation;
MariaDB [(none)]> create user weatherstation@localhost identified by 'YOUR_PASSWORD';
MariaDB [(none)]> grant all privileges on weatherstation.* to weathestation@localhost;
MariaDB [(none)]> quit;
pi@raspberry:~ $ sudo useradd -ms/bin/bash weatherstation
pi@raspberry:~ $ sudo su weatherstation
weatherstation@raspberry:/home/pi $ cd ~
weatherstation@raspberry:~ $ echo -e "[client]\npassword=YOUR_PASSWORD" > .my.cnf
weatherstation@raspberry:~ $ chmod 640 .my.cnf
weatherstation@raspberry:~ $ mkdir htdocs
weatherstation@raspberry:~/htdocs $ cd htdocs
weatherstation@raspberry:~ $ git clone https://github.com/NicolasGoeddel/weatherstation-server.git
weatherstation@raspberry:~ $ cd weatherstation-server
weatherstation@raspberry:~/htdocs/weatherstation-server $ mariadb weatherstation < database-init.sql
weatherstation@raspberry:~/htdocs/weatherstation-server $ cp config.sample.php config.php
weatherstation@raspberry:~/htdocs/weatherstation-server $ nano config.php
```
```php
<?php
$baseUrl =  "//{$_SERVER['HTTP_HOST']}/weatherstation";

$db = array('hostname' => 'localhost',
            'port' => 3306,
            'username' => 'weatherstation',
            'password' => 'YOUR_PASSWORD',
            'database' => 'weatherstation');

?>
```
```shell
weatherstation@raspberry:~/htdocs/weatherstation-server $ exit
pi@raspberry:~ $ sudo su
root@raspberry:/home/pi# nano /etc/php/7.3/fpm/pool.d/weatherstation.conf
```
Instead of creating the file exactly like this you can also copy `www.conf` and change only the lines you need like so:
```
[weatherstation]
user = weatherstation
group = weatherstation
listen = /run/php/php-fpm-weatherstation.sock
listen.owner = www-data
listen.group = www-data
;listen.mode = 0660

pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```
```shell
root@raspberry:/home/pi# systemctl reload php7.3-fpm
root@raspberry:/home/pi# cd /etc/apache2/conf-available
root@raspberry:/etc/apache2/conf-available# wget https://gist.githubusercontent.com/NicolasGoeddel/9d16e65c064d4a8b901ff7dd55566a18/raw/16dc18ca43aa5d37d8e7f87901bce58259fa5c5e/easy-php-fpm.conf
... virtualhost anpassen
root@raspberry:/etc/apache2/conf-available# a2enconf easy-php-fpm
root@raspberry:/etc/apache2/conf-available# a2enmod macro rewrite proxy_fcgi
root@raspberry:/etc/apache2/conf-available# nano ../sites-enabled/000-default.conf
```
```xml
<VirtualHost *:80>
        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/html

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

        Use PHPAliasSock /weatherstation /home/weatherstation/htdocs/weatherstation-server php-fpm-weatherstation.sock
</VirtualHost>
```
```shell
root@raspberry:/etc/apache2/conf-available# systemctl restart apache2
```

### MySQL

```shell
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

## Ideas and tips derived from

* https://bootstrapious.com/p/bootstrap-sidebar
* [Stackoverflow.com - Grouping into interval of 5 minutes within a time range](https://stackoverflow.com/a/4345308/4239139)
* [startsWith() and endsWith() functions in PHP](https://stackoverflow.com/a/834355/4239139)

## TODO

* Initializing database tables
* Configuration interface for clients and data types
* Support more database endpoints
* Client authentication
* User and roles management
* ~~Create averages/min/max per user definable date ranges~~
* Show standard deviation using [chartjs-plugin-error-bars](https://github.com/datavisyn/chartjs-plugin-error-bars)