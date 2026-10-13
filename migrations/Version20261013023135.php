<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\PrefixedMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds each calendar's choice of whether events may overlap (allow, warn, or don't allow).
 */
final class Version20261013023135 extends PrefixedMigration
{
    public function getDescription(): string
    {
        return 'Add calendars.overlap_policy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . $this->table('calendars') . ' ADD overlap_policy SMALLINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . $this->table('calendars') . ' DROP overlap_policy');
    }
}
