<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the secret part of users' personal calendar feed links.
 */
final class Version20261012204731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add users.feed_key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD feed_key VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP feed_key');
    }
}
