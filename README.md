# PKP Preservation Network Staging Server

## Requirements

 * PHP >= 7.2
 * MySQL >= 5.7
 * Git
 * Composer
 * Yarn
 
This application has been tested with Apache and mod_php and php-fpm. It 
may work with various Nginx setups, but they are untested and unsupported. These 
instructions do not include steps for installing or configuring the 
prerequisites listed above.

## Install

Fetch the most recent code and leave it 
somewhere accessible to the web. The instructions below assume that the application
will be accessed at https://localhost/pkppln

```bash
$ git clone -b https://github.com/pkp/pkppln
$ mv pkppln /var/www/html/pkppln
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

Install the yarn and composer dependencies. The `composer install` step 
will ask for some configuration parameters. Use whatever user name and password 
you used to create the database above. The remainder of the defaults should 
work fine.

```bash
$ yarn install
$ composer install --quiet
```

Set the file permissions for various directories. These directions assume that the 
web server runs as the user _apache_.

```bash
for dir in var/logs var/cache var/sessions data;
do
    mkdir -p $dir
    sudo setfacl -R -m u:apache:rwX $dir
    sudo setfacl -dR -m u:apache:rwX $dir
done
```

Finally, create the database tables:

```bash
$ ./bin/console doctrine:schema:update --force
```

And an administrator user:

```bash
$ ./bin/console nines:user:create admin@example.com
$ ./bin/console nines:user:promote admin@example.com ROLE_ADMIN
$ ./bin/console nines:user:password
$ ./bin/console nines:user:activate
```


You should be able to login at https://localhost/pkppln/public

Quality Tools
-------------

PHP Unit

`./vendor/bin/phpunit`

`./vendor/bin/phpunit --coverage-html=web/docs/coverage`

Sami

`sami -vv update --force sami.php`

PHP CS Fixer

`php-cs-fixer fix`
