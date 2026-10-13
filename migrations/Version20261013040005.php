<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\PrefixedMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds views: named collections of calendars shown together, each calendar with its own color.
 */
final class Version20261013040005 extends PrefixedMigration
{
    public function getDescription(): string
    {
        return 'Add views and view_calendars';
    }

    public function up(Schema $schema): void
    {
        $views = $this->table('views');
        $members = $this->table('view_calendars');
        $this->addSql("CREATE TABLE $views (vid INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL,"
            . ' slug VARCHAR(100) NOT NULL, timezone VARCHAR(255) NOT NULL, locale VARCHAR(35) DEFAULT NULL,'
            . ' theme VARCHAR(100) DEFAULT NULL, events_max INT NOT NULL, UNIQUE INDEX slug (slug), PRIMARY KEY (vid))'
            . ' DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("CREATE TABLE $members (color VARCHAR(7) NOT NULL, position INT NOT NULL, vid INT NOT NULL,"
            . ' cid INT NOT NULL, INDEX ' . $this->generatedName('IDX', 'view_calendars', 'vid') . ' (vid),'
            . ' INDEX ' . $this->generatedName('IDX', 'view_calendars', 'cid') . ' (cid), PRIMARY KEY (vid, cid))'
            . ' DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("ALTER TABLE $members ADD CONSTRAINT " . $this->generatedName('FK', 'view_calendars', 'vid')
            . " FOREIGN KEY (vid) REFERENCES $views (vid) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE $members ADD CONSTRAINT " . $this->generatedName('FK', 'view_calendars', 'cid')
            . ' FOREIGN KEY (cid) REFERENCES ' . $this->table('calendars') . ' (cid) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ' . $this->table('view_calendars'));
        $this->addSql('DROP TABLE ' . $this->table('views'));
    }
}
