1. Installation

First install mysql:

	apt install mysql-server phpmyadmin

Choose password for phpmyadmin user.

Start mysql and create database:

	mysql
	> create database weatherstation;
	> grant all privileges on *.* to phpmyadmin@localhost;
	> create user weatherstation@localhost identified by '***';
	> grant all privileges on weatherstation.* to weatherstation@localhost;
	> quit;

Then move on to bash and do this:

	mkdir /var/www/weatherstation
	useradd -Md/var/www/weatherstation -s/bin/bash weatherstation
	chown -R weatherstation: /var/www/weatherstation


Open http://host/phpmyadmin in your browser, log in as 'phpmyadmin', choose database 'weatherstation' and create the following tables;


