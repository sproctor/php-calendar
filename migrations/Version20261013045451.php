<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\PrefixedMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Lets a category mark the days of its full-day events, for holidays and days off.
 */
final class Version20261013045451 extends PrefixedMigration
{
    public function getDescription(): string
    {
        return 'Add categories.marks_day';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . $this->table('categories') . ' ADD marks_day TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . $this->table('categories') . ' DROP marks_day');
    }
}
