<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds a default language to calendars, for visitors who haven't picked one.
 */
final class Version20261012133339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add calendars.locale';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE calendars ADD locale VARCHAR(35) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE calendars DROP locale');
    }
}
