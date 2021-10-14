Developer Documentation
=======================

Versions
--------

- https://github.com/ubermichael/pkppln @ 1e47b54e
- PHP 7.2.19 (cli) (built: Jun 17 2019 09:03:55) ( NTS )
- mysql Ver 8.0.15 for osx10.14 on x86_64 (Homebrew)
- http Server version: Apache/2.4.38 (Unix)

In my configuration, Apache is loading the php7 module, instead of handing everything
off to php-fpm. Composer is installed globally.

Installation
------------

In my setup, Apache is configured to serve /Users/mjoyce/Sites as the document root and
run as the user mjoyce. This combination is lazy, but it also eliminates any file
permission problems. If your setup is different you may need to consult the `Symfony
documentation on file permissions`_.

Clone the Github repository or your fork of the repository somewhere web accessible
and figure out the base URL for it. In my set up, the base url is http://localhost/pkppln.
The developer version of the website will be accessed from http://localhost/pkppln/web/app_dev.php
which does far less caching than the production version.

Create a database, database user, and set access permissions for the user.

.. code-block:: sql

    CREATE DATABASE IF NOT EXISTS pkppln;
    CREATE USER IF NOT EXISTS pkppln@localhost;
    GRANT ALL ON pkppln.* TO pkppln@localhost;
    SET PASSWORD FOR pkppln@localhost = PASSWORD('abc123')

Install the composer dependencies and provide the configuration parameters. You
should read and understand the :ref:`parameters` before installing
the composer dependencies.

.. code-block:: shell

    $ composer install
    Loading composer repositories with package information
    Installing dependencies (including require-dev) from lock file
    Package operations: 81 installs, 0 updates, 0 removals
      - Installing twig/twig (v1.35.3): Downloading (100%)
      ...

Once the dependencies are downloaded, composer will ask for the configuration parameters. The
defaults will not work.

After the dependencies are installed and the application is configured, the staging
server should be ready for use at http://localhost/pkppln/web/app_dev.php.

Initial Setup - Shell Commands
------------------------------

Create the database tables.

.. code-block:: shell

    $ ./app/console doctrine:schema:update --force

.. note::

    If this command fails, check your database settings in ``app/config/parameters.yml``.

Create an administrator user account.

.. code-block:: shell

    $ ./app/console fos:user:create --super-admin admin@example.com abc123 Admin Library
    $ ./app/console fos:user:promote admin@example.com ROLE_ADMIN

You should be able to login to the application by following the Admin link in the
navigation bar.

.. note::

    A common problem is to login with the correct credentials and then be
    presented with the login form again. This indicates that the HTTP sessio
    cookie settings are incorrect.

    1. Check the ``request.*`` parameters in the configuration file
        ``app/config/parameters.yml``
    2. Use a shell command to clear the cache ``./app/console cache:clear``
    3. Remove the session cookies in your browser. If they've been set incorrectly and
       you leave them in place, Symfony will continue to use them.

Initial Setup - Website
-----------------------

Now that the initial user account is created and you can login, you can define
the terms of use. The staging server and OJS plugin both expect at least one term
of use, and may error out otherwise.

#. Use the Terms of Use link in the navigation bar to access the page.
#. The New button will open a form to define a term of use.

Weight:
  This sets the order of the terms of use. Set it to one.
Key code:
  A computer-readable identifier for the term of use. It must be XML name-compatible.
Lang code:
  Just set it to en-US. This was meant to support translatable terms of use, but that
  feature was never implemented.
Content:
  Plain text content of the term of use.

Initial Setup - OJS
-------------------

Download a clean copy of OJS 3 and put it somewhere web accessible. In my setup it is in
/Users/mjoyce/Sites/ojs3 and is web accessible at http://localhost/ojs3. If your configuration
is different you may need to adjust some of the steps below.

1. Complete the usual OJS installation steps.

2. Create a journal. ISSN 0000-0000 should be valid for testing.

3. Override the default PLN staging URL in config.inc.php. Note that this is the URL to the
front page of the PKP PLN staging server.

.. code-block:: ini

    [lockss]
    pln_url = http://localhost/pkppln/web/app_dev.php

4. Put a copy of the PKP PLN Plugin in the right place. I'm using `8e0cdcd27`_.

5. Enable the plugin.

.. note::

  If you put the plugin in place and then change config.inc.php you may need to clear the
  OJS cache and remove the plugin settings from the database.

  .. code-block:: sql

    DELETE FROM ojs3.plugin_settings WHERE plugin_name='plnplugin';

  .. code-block:: shell

    $ find cache -type f -delete

6. Check the plugin Settings. If the plugin settings page loads and you see the test term of use you
created above in `Initial Setup - Website` then it worked.

.. note::

    If it didn't work, check your ``pln_url`` settings in config.inc.php, clear your cache and
    plugin_settings tables as above and try again. Try checking the staging server's
    service document url, which for my setup looks like this

    http://localhost/pkppln/web/app_dev.php/api/sword/2.0/sd-iri

    On its own, that URL should return an error about missing request headers. To see a proper
    service document response try gently hacking it to fake the missing header. This will
    auto-register a dummy journal. This may cause the Ping step below to issue a processing error.
    Either ignore the processing error or remove the dummy journal in mysql.

    http://localhost/pkppln/web/app_dev.php/api/sword/2.0/sd-iri?On-Behalf-Of=abc&Journal-Url=http://example.com

    .. code-block:: sql

        delete from pkppln.journal where uuid='ABC';

7. Now check the staging server. Your journal should have automatically registered and be listed in
the New Journals panel. It's title will be "untitled." The registration process only includes
the journal url and UUID. By design, the staging server will not accept deposits from the journal yet.

8. PING

.. note::

    At this point our intrepid author noticed a problem with the journal ping shiz but had to go
    to a meeting unrelated to this project.


.. _Symfony documentation on file permissions: https://symfony.com/doc/2.7/setup/file_permissions.html
.. _8e0cdcd27: https://github.com/defstat/pln