pkppln2
=======

A Symfony project created on February 23, 2018, 2:00 pm.

Requirements
------------

 * PHP >= 7.2
 * MySQL >= 5.6
 * Git
 * Composer
 * Bower
 
This application has been tested with Apache and mod_php and php-fpm. It 
may work with various Nginx setups, but they are untested and unsupported. These 
instructions do not include steps for installing or configuring the 
prerequisites listed above.

You may also consider this Vagrant setup, which will create a complete working 
application environment in a Centos 7 virtual machine.

https://git.lib.sfu.ca/mjoyce/pkppn-v2-vagrant

Install
-------

Fetch the most recent code from the SFU Library gitlab and put it 
somewhere accessible to the web. The instructions below assume that the application
will be accessed at http://somehost/pkppln

```bash
$ git clone https://git.lib.sfu.ca/mjoyce/pkppln-v2.git
$ sudo mv pkppln-v2 /var/www/html/pkppln
$ cd /var/www/html/pkppln
$ git submodule update --init
```

Create a MySQL user and database, and give the user access to the database.

```sql
CREATE DATABASE IF NOT EXISTS pkppln;
CREATE USER IF NOT EXISTS pkppln@localhost;
GRANT ALL ON pkppln.* TO pkppln@localhost;
SET PASSWORD FOR pkppln@localhost = PASSWORD('abc123');
```

Install the bower and composer dependencies. The `composer install` step 
will ask for some configuration parameters. Use whatever user name and password 
you used to create the database above. The remainder of the defaults should 
work fine.

```bash
$ bower install
$ /usr/local/bin/composer install --quiet
```

Set the file permissions for various directories. These directions assume that the 
web server runs as the user _apache_ and you are logging in as _vagrant_.

```bash
for dir in var/logs var/cache var/sessions data;
do
    mkdir -p $dir
    sudo setfacl -R -m u:apache:rwX -m u:vagrant:rwx $dir
    sudo setfacl -dR -m u:apache:rwX -m u:vagrant:rwx $dir
done
```

Finally, create the database tables and the administrator account.

```bash
$ ./bin/console doctrine:schema:update --force
$ ./bin/console fos:user:create --super-admin admin@example.com supersecret Admin Library
$ ./bin/console fos:user:promote admin@example.com ROLE_ADMIN
```

You should be able to login at http://servername/pkppln/

Quality Tools
-------------

PHP Unit

`./vendor/bin/phpunit`

`./vendor/bin/phpunit --coverage-html=web/docs/coverage`

Sami

`sami -vv update --force sami.php`

PHP CS Fixer

`php-cs-fixer fix`
