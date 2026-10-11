<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\CalendarSlugger;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds calendar slugs for URLs, made from the titles of existing calendars.
 */
final class Version20261011220757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add calendars.slug, filled in from existing titles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE calendars ADD slug VARCHAR(100) DEFAULT NULL');

        $slugger = new CalendarSlugger();
        $taken = [];
        foreach ($this->connection->fetchAllAssociative('SELECT cid, title FROM calendars ORDER BY cid') as $row) {
            $slug = $slugger->uniqueSlug($row['title'], fn(string $slug) => isset($taken[$slug]));
            $taken[$slug] = true;
            $this->addSql('UPDATE calendars SET slug = ? WHERE cid = ?', [$slug, $row['cid']]);
        }

        $this->addSql('ALTER TABLE calendars MODIFY slug VARCHAR(100) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX slug ON calendars (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX slug ON calendars');
        $this->addSql('ALTER TABLE calendars DROP slug');
    }
}
