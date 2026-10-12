<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds site settings (for the default theme) and a theme per calendar.
 */
final class Version20261012212630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add settings and calendars.theme';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE settings (name VARCHAR(64) NOT NULL, value LONGTEXT DEFAULT NULL, PRIMARY KEY (name)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE calendars ADD theme VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE settings');
        $this->addSql('ALTER TABLE calendars DROP theme');
    }
}
