<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\PrefixedMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Lets a calendar be in a view without a color marking its events.
 */
final class Version20261013044116 extends PrefixedMigration
{
    public function getDescription(): string
    {
        return 'Make view_calendars.color optional';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . $this->table('view_calendars') . ' CHANGE color color VARCHAR(7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE ' . $this->table('view_calendars') . " SET color = '#1f6feb' WHERE color IS NULL");
        $this->addSql('ALTER TABLE ' . $this->table('view_calendars') . ' CHANGE color color VARCHAR(7) NOT NULL');
    }
}
