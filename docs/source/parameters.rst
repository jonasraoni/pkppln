.. _parameters:

Config Parameters
=================

Host-specific parameters are configured in
app/config/parameters.yml. Default parameters are defined in
app/config/parameters.yml.dist which is included in the git repository.

Composer will prompt for all of these parameters the first time either
``composer install`` or ``composer update`` is run.

.. code-block:: yaml

    # database connection information
    database_host: 127.0.0.1
    database_port: null
    database_name: pkppln
    database_user: pkppln
    database_password: abc123

    # mailer configuration. These defaults will work.
    mailer_transport: smtp
    mailer_host: 127.0.0.1
    mailer_user: null
    mailer_password: null

    # Lazy: accept the default. Not lazy: http://nux.net/secret
    secret: ThisTokenIsNotSoSecretChangeIt

    # PLN configuration parameters. Each one is important.
    # This must be true for the staging server to accept any deposit or allow
    # any journal to make a deposit. Change it to true.
    pln_accepting: false

    # Maximum allowable deposit and AU size in 1000 byte units. The defaults should do.
    pln_maxUploadSize: 1000000
    pln_maxAuSize: 100000000

    # SHA-1 is the only supported method, but others are planned in future.
    pln_uploadChecksumType: SHA-1

    # Default locale for the terms and conditions.
    pln_defaultLocale: en-US

    # The LOCKSSOMatic LOCKSS plugin requires a journal name.
    pln_journal_name: 'PKP PLN Deposits from OJS'

    # Directory to store and process deposits. Either an absolute path or
    # a path relative to the project directory.
    pln_data_dir: data

    # Path to the clamdscan binary.
    clamdscan_path: /usr/bin/clamdscan

    # URL for the LOCKSSOMatic staging server.
    lockssomatic_sd_iri: 'http://localhost/lockssomatic/web/app_dev.php/api/sword/2.0/sd-iri'

    # The staging server requires its own UUID to identify itself.
    staging_server_uuid: null

    # Duplicate
    terms_of_use_default_locale: en-US

    # If a journal doesn't contact the staging server after this many days, it is
    # considered inactive.
    days_silent: 60

    # The router variables are used to generate absolute URLs and to
    # configure http session cookies. They are the biggest source of
    # difficulty. The example below generates URLs and sets cookie
    # permissions for the dev URL.
    router.request_context.host: localhost
    router.request_context.scheme: http
    router.request_context.base_url: /pkppln/web/app_dev.php

    # For debugging purposes you can save the deposit XML in files.
    save_deposit_xml: false

    # The staging server supports OJS >= 2.4.8
    min_ojs_version: 2.4.8.0

    # Messages sent to the journals
    network_accepting: 'The PKP PLN can accept deposits from this journal.'
    network_oldojs: 'This journal must be running OJS 2.4.8 to make deposits to the PKP PLN.'
    network_default: 'The PKP PLN does not know about this journal yet.'

    # Deposits that are processed and have achieved 100% agreement in LOCKSS can be removed.
    remove_complete_deposits: false

    # Limit the number of harvest attempts for each deposit.
    max_harvest_attempts: 5

    # Deposits from OJS journal versions higher than this will be held,
    # instead of sending them to LOCKSSOMatic and LOCKSS.
    held_versions: 3

