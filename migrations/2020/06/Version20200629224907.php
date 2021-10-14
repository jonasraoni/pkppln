<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200629224907 extends AbstractMigration {
    public function getDescription() : string {
        return 'FOSUser -> NinesUser';
    }

    public function up(Schema $schema) : void {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX uniq_5ba994a192fc23a8 ON nines_user');
        $this->addSql('DROP INDEX uniq_5ba994a1a0d96fbf ON nines_user');
        $this->addSql('DROP INDEX uniq_5ba994a1c05fb297 ON nines_user');

        $this->addSql(
            <<<'ENDSQL'
ALTER TABLE nines_user
DROP username_canonical,
DROP email_canonical,
DROP email,
DROP salt,
DROP data,

CHANGE fullname fullname varchar(64) NOT NULL,
CHANGE enabled active TINYINT NOT NULL DEFAULT 0,
CHANGE COLUMN username email VARCHAR(180) NOT NULL,
CHANGE COLUMN confirmation_token reset_token VARCHAR(180) DEFAULT NULL,
CHANGE COLUMN password_requested_at reset_expiry DATETIME DEFAULT NULL,
CHANGE COLUMN institution affiliation VARCHAR(255) DEFAULT NULL,
CHANGE COLUMN last_login login DATETIME DEFAULT NULL,

ADD created DATETIME NOT NULL DEFAULT NOW() COMMENT '(DC2Type:datetime_immutable)',
ADD updated DATETIME NOT NULL DEFAULT NOW() COMMENT '(DC2Type:datetime_immutable)'
;
ENDSQL
        );

        $this->addSql('CREATE UNIQUE INDEX uniq_5ba994a1e7927c74 ON nines_user (email)');
    }

    public function down(Schema $schema) : void {
        $this->throwIrreversibleMigrationException();
    }
}
