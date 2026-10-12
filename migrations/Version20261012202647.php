<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds an optional location to events.
 */
final class Version20261012202647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add events.location';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events ADD location VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events DROP location');
    }
}
