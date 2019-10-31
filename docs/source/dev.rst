Developer Documentation
=======================

Versions
--------

- https://github.com/ubermichael/pkppln @ 7257326e
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

4. Put a copy of the PKP PLN Plugin in the right place. I'm using `8e0cdcd27`_. Install the
   composer dependencies for the plugin.

5. Enable the plugin.

   .. note::

      If you put the plugin in place and then change config.inc.php you may need to clear the
      OJS cache and remove the plugin settings from the database.

      .. code-block:: sql

        DELETE FROM ojs3.plugin_settings WHERE plugin_name='plnplugin';

      .. code-block:: shell

        $ find cache -type f -delete

6. Check the plugin Settings. If the plugin settings page loads and you see the test term of use you
   created above in `Initial Setup - Website` then it worked. Accept the terms by clicking the clicking
   checkboxes and saving.

   .. note::

        If it didn't work, check your ``pln_url`` settings in config.inc.php, clear your cache and
        plugin_settings tables as above and try again. Try checking the staging server's
        service document url, which for my setup looks like this

        http://localhost/pkppln/web/app_dev.php/api/sword/2.0/sd-iri

        On its own, that URL should return an error about missing request headers. To see a proper
        service document response try gently hacking it to fake the missing header. This will
        auto-register a dummy journal. This may cause the Ping step below to issue a processing error.
        Either ignore the processing error or remove the dummy journal in mysql.

        http://localhost/pkppln/web/app_dev.php/api/sword/2.0/sd-iri?On-Behalf-Of=ABC&Journal-Url=http://example.com

        .. code-block:: sql

            delete from pkppln.journal where uuid='ABC';

7. Now check the staging server. Your journal should have automatically registered and be listed in
   the New Journals panel. It's title will be "untitled." The registration process only includes
   the journal url and UUID. By design, the staging server will not accept deposits from the journal yet.

8. The staging server must contact the journal to request the journal's title and check if the terms
   of use have been accepted. There multiple ways to do this.

   A. Use the web user interface to navigate to the journal page using either the New Journals box on
      the home page or with the tools in the Journals navigation menu. Use the Ping button. The staging server
      will contact the journal and request extra information.

   B. Use the command line tools.

      .. code-block:: shell

         $ ./app/console pln:ping-whitelist -a -v
        [2019-07-05 16:04:23] processing.NOTICE: Pinging Journal of International Testing
        [2019-07-05 16:04:24] processing.NOTICE: Ping - 200 - 3.1.2.1 - http://localhost/ojs3/index.php/jit - http://pkppln.dev/web/journal/1

      .. note::

         If you used the On-Behalf-Of and Journal-Url query parameters in step 6 **and** didn't
         remove the fake journal that step created, you will see an error message. It is safe to
         ignore the error message.

         .. code-block::

            [2019-07-05 16:04:24] processing.NOTICE: Pinging unknown
            [2019-07-05 16:04:24] processing.ERROR: Ping - HTTP 404 - - http://example.com - http://pkppln.dev/web/journal/4 - String could not be parsed as XML

   C. Visit the ping URL directly. The URL is visible on the journal's page in the staging server. In
      my setup it is http://localhost/ojs3/index.php/jit/gateway/plugin/PLNGatewayPlugin

   Steps A and B above should update some of the journal's metadata in the database. If you forgot to
   accept the terms of use in step 6 you can return to OJS and accept them now. Ping the journal again
   using method A or B above and the metadata should update.

9. Load some content into OJS. I like the `quick submit plugin`_ (git stable-3_1_2 branch) for this but
   YMMV. The plugin works by archiving issues, so don't forget to create one!

10. There are some things that could have gone wrong by now, so you have to check for and fix them.

    * My OJS instance didn't pick up the plugin's depositor task automatically. I used the Acron
      Plugin's Reload Scheduled Tasks link. That got it loaded.
    * My install didn't automatically create the database tables for the plugin (the only symptom
      of that is a JSON-related error message when visiting the plugin's status page). You
      may need to manually update the tables with the upgrade script like so:

      .. code-block:: shell

        $ php tools/upgrade.php upgrade

11. Run the scheduled tasks. The plugin is configured to run the tasks ever 24 hours. To manually
    run the tasks use the command line.

    .. code-block:: shell

       $ mysql -e "UPDATE ojs3.scheduled_tasks SET last_run = null WHERE class_name = 'plugins.generic.pln.classes.tasks.Depositor';"
       $ php tools/runScheduledTasks.php plugins/generic/pln/xml/scheduledTasks.xml

    This should produce two files for each issue in ``files/journals/1/pln/UUID/``. One is an
    XML file with the deposit metadata. The other is a BagIt zip file with the deposit content. The
    scheduled task runner thing should have sent the deposit XML file to the staging server.

12. Check that the deposit XML file was sent to the server.

    A. Open  http://localhost/pkppln/web/app_dev.php/journal/1 which should include a count of the deposits in the
       deposits row.

    B. Open http://localhost/pkppln/web/app_dev.php/journal/1/deposits which should list the deposits
       for the journal. The state should be ``depositedByJournal``.

    C. Open http://localhost/pkppln/web/app_dev.php/deposit/ which lists all deposits the staging server
       is aware of.

    These are three views of the same data. If one works they should all work.

13. Check that the deposit is ready to download.

    A. Visit http://localhost/pkppln/web/app_dev.php/deposit/1 or click on the deposit UUID to
       view all the data about the deposit.
    B. Verify that the metadata looks correct.
    C. The URL field is what the staging server will use to fetch the deposit. It should look
       like http:/​/​localhost/​ojs3/​index.php/​jit/​pln/​deposits/​FA1FBEE8-465C-48C2-A9D4-ABAD84E21D2C
    D. Use the URL field to download the deposit content.

14. Download the deposit. In the staging server vernacular, this is called a harvest.

  .. code-block:: shell

    $ ./app/console pln:harvest -vvv
    [2019-07-11 12:47:05] processing.INFO: Processing 1 deposits.
    [2019-07-11 12:47:05] processing.NOTICE: Harvest expected to consume 1125000 bytes.
    [2019-07-11 12:47:05] processing.NOTICE: harvest - FA1FBEE8-465C-48C2-A9D4-ABAD84E21D2C
    [2019-07-11 12:47:05] processing.INFO: Harvest - http://localhost/ojs3/index.php/jit/pln/deposits/FA1FBEE8-465C-48C2-A9D4-ABAD84E21D2C - HTTP 200 - 1124106
    [2019-07-11 12:47:05] processing.INFO: Writing deposit to /Users/mjoyce/Sites/pkppln/data/received/B1F7314E-C958-49C1-9039-E5BA8B950FC8/FA1FBEE8-465C-48C2-A9D4-ABAD84E21D2C.zip

15. Run the remaining processing commands at the console, so skip them entirely!

    .. code-block:: shell

        $ ./app/console pln:reset complete

    Or, if you want to run all the commands, do so in this order:

    pln:harvest
      Download deposit content. If a download failed, try ``--retry`` to try again.
    pln:validate-payload
      Calculate a checksum of the BagIt zip file and compare it to the metadata in the deposit
      XML.
    pln:validate-bag
      Run the BagIt validation on the deposit to ensure the bag's contents are correct.
    pln:validate-xml
      Parse and validate the OJS export XML against the DTD. The command will look for XML files
      with this specific public identifier ``-//PKP//OJS Articles and Issues XML//EN``
    pln:virus-scan
      Scan the deposit for viruses. This command may fail if ClamAV isn't setup and configured. For
      development and testing purposes this can be ignored.
    pln:reserialize
      Add some processing information and metadata to the depost and create a new BagIt zip file.
    pln:deposit
      Send LOCKSSOmatic a notification that a new deposit is ready. This command will fail if you
      do not have a LOCKSSOmatic instance running. It's safe to ignore this failure for development
      and testing.
    pln:status
      Check the status of the deposit in LOCKSSOMatic. This command will fail if you
      do not have a LOCKSSOmatic instance running. It's safe to ignore this failure for development
      and testing.

16. Run the OJS scheduled tasks again. The deposit should be complete in the PLN plugin's status page.

.. _Symfony documentation on file permissions: https://symfony.com/doc/2.7/setup/file_permissions.html
.. _8e0cdcd27: https://github.com/defstat/pln
.. _quick submit plugin: https://github.com/pkp/quickSubmit/tree/stable-3_1_2