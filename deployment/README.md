# Deployer Tasks for Symfony Applications

> This document assumes that you have set up and configured passwordless login for your servers. The deployment configuration will not work with password-based authentication.


## Symfony
In the Symfony projects we use [Deployer][deployer] to manage and update applications on our servers.

The project contains a `deploy.php` file in its root which is where Deployer expects to find everything it needs. These `deploy.php` files import the task definitions in the appropriate file in the deployment repository.
```php
<?php

require 'deployment/symfony.php';
```

The deployment is configured in `config/deploy.yaml` which points to the relevant GitHub repository, defines which files and directories are shared and writeable, and specifics SELinux permissions in addition to defining the necessary server parameters like host name and user. These configuration files are not committed to git as they will vary by developer. There should be a generic template in each repository called `config/deploy.yaml.dist` which can be copied and edited for each developer.

The alias `pn` is used for our production server and is defined in `config/deploy.yaml`. A server alias must be specified with each command. The syntax is `dep TASK ALIAS`. `pn` and `local` are configured aliases and the tasks are described below.

### Tasks
Deployer divides up the steps involved into a series of "tasks". These tasks are described below. The most important and frequently used task is `dep deploy pn` which runs most of the tasks below.

pn:precheck
: Check for modified files in the working tree or commits that have not been pushed. Also checks if the deployment directory is locked. If these checks fail, the user is prompted to continue or exit.

pn:assets
: Install any Symfony bundle assets into `public/bundles`

pn:yarn
: Install yarn dependencies from package.json

pn:fonts
: Install fonts if they are configured in config/fonts.yaml

pn:phpunit
: Run all unit tests. If you are using a test database make sure it is configured properly, as the first step in running tests is to clear out the database. It should be configured in `shared/.env.test.local`.

pn:sphinx:build
: If the project includes a top-level `docs` directory, build the sphinx docs there.

pn:sphinx:upload
: If the project includes a top-level `docs` directory, upload the docs built there to the server.

pn:sphinx
: Simple wrapper around pn:sphinx:build and pn:sphinx:upload.

pn:db:backup
: Backup the mysql database to $HOME/APP-DATE-rNUMBER.sql
: _Note: that this is not a wrapper around `pn:db:schema` or `pn:db:data`. It is a pure mysqldump._

pn:db:schema
: Run `mysqldump --no-data` and store the result to `APP-schema.sql`
: _Note: This command is not run as part of a deployment. It is meant to be used as part of the steps to update a developer copy of a database.

pn:db:data
: Run `mysqldump --no-create-info` and store the result to `APP-data.sql`. This command will skip any tables listed in the configuration option `ignore_tables`. Add option `--all-options` to fetch all data.
: _Note: This command is not run as part of a deployment. It is meant to be used as part of the steps to update a developer copy of a database.

pn:db:fetch
: Wrapper around pn:db:schema and pn:db:data
: _Note: This command is not run as part of a deployment. It is meant to be used as part of the steps to update a developer copy of a database.

pn:db:migrate
: Apply database migrations. If there is a single database migration, it is assumed to be a schema definition/rollup and all previous migrations are removed from the database.

pn:media
: Download any uploaded media, assuming it is in /data
: _Note: This command is not run as part of a deployment. It is meant to be used as part of the steps to update a developer copy of a database.

pn:permissions
: Fix selinux permissions

[^n1]: See https://cloud.google.com/architecture/devops/devops-tech-deployment-automation for justification
[deployer]: https://deployer.org