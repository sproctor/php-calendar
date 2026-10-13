<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Doctrine\PrefixedMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The 3.0 schema, with every table named with TABLE_PREFIX. It replaced the migrations written during
 * development; a database made by those (with no prefix) already has these tables, so it's only tidied.
 *
 * Index and foreign key names are the ones Doctrine generates from the entities, which depend on the
 * table names; SchemaTest checks that they match.
 */
final class Version20261012220000 extends PrefixedMigration
{
    private const TABLE_OPTIONS = 'DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB';

    public function getDescription(): string
    {
        return 'Create the PHP-Calendar 3.0 tables';
    }

    public function up(Schema $schema): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$this->table('calendars')],
        );
        if ($exists > 0) {
            // Made by the earlier development migrations: drop what 3.0 no longer uses, and forget those
            // migrations, which no longer exist
            $this->addSql('DROP TABLE IF EXISTS config, messenger_messages');
            $this->addSql('DELETE FROM ' . $this->table('doctrine_migration_versions') . ' WHERE version <> ?', [self::class]);

            return;
        }

        $this->createTable('calendars', 'cid INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL, subject_max INT NOT NULL, events_max INT NOT NULL, timezone VARCHAR(255) NOT NULL,
            locale VARCHAR(35) DEFAULT NULL, theme VARCHAR(100) DEFAULT NULL, anon_permission SMALLINT DEFAULT 1 NOT NULL,
            UNIQUE INDEX slug (slug), PRIMARY KEY (cid)');
        $this->createTable('users', 'uid INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL,
            email VARCHAR(180) DEFAULT NULL, hash VARCHAR(255) NOT NULL, feed_key VARCHAR(64) DEFAULT NULL,
            is_admin TINYINT NOT NULL, password_is_editable TINYINT NOT NULL, timezone VARCHAR(255) DEFAULT NULL,
            locale VARCHAR(255) DEFAULT NULL, is_disabled TINYINT NOT NULL,
            UNIQUE INDEX username (username), UNIQUE INDEX email (email), PRIMARY KEY (uid)');
        $this->createTable('roles', 'rid INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL,
            can_read TINYINT NOT NULL, can_create TINYINT NOT NULL, can_update TINYINT NOT NULL,
            can_moderate TINYINT NOT NULL, can_admin TINYINT NOT NULL, cid INT NOT NULL, ' . $this->index('roles', 'cid')
            . ', PRIMARY KEY (rid)');
        $this->createTable('role_users', 'rid INT NOT NULL, uid INT NOT NULL, ' . $this->index('role_users', 'rid')
            . ', ' . $this->index('role_users', 'uid') . ', PRIMARY KEY (rid, uid)');
        $this->createTable('categories', 'catid INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL,
            color VARCHAR(255) NOT NULL, cid INT NOT NULL, rid INT DEFAULT NULL, ' . $this->index('categories', 'cid')
            . ', ' . $this->index('categories', 'rid') . ', PRIMARY KEY (catid)');
        $this->createTable('field_definitions', 'fid INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL,
            is_required TINYINT NOT NULL, format LONGTEXT DEFAULT NULL, cid INT NOT NULL, '
            . $this->index('field_definitions', 'cid') . ', PRIMARY KEY (fid)');
        $this->createTable('events', 'eid INT AUTO_INCREMENT NOT NULL, ctime DATETIME NOT NULL,
            mtime DATETIME DEFAULT NULL, pubtime DATETIME DEFAULT NULL, subject VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL, location VARCHAR(255) DEFAULT NULL, readonly TINYINT DEFAULT 0 NOT NULL,
            cid INT NOT NULL, owner_uid INT DEFAULT NULL, catid INT DEFAULT NULL, ' . $this->index('events', 'cid')
            . ', ' . $this->index('events', 'owner_uid') . ', ' . $this->index('events', 'catid') . ', PRIMARY KEY (eid)');
        $this->createTable('occurrences', 'oid INT AUTO_INCREMENT NOT NULL, start DATETIME NOT NULL,
            end DATETIME NOT NULL, time_type INT NOT NULL, eid INT NOT NULL, ' . $this->index('occurrences', 'eid')
            . ', PRIMARY KEY (oid)');
        $this->createTable('fields', 'value LONGTEXT NOT NULL, fid INT NOT NULL, eid INT NOT NULL, '
            . $this->index('fields', 'fid') . ', ' . $this->index('fields', 'eid') . ', PRIMARY KEY (fid, eid)');
        $this->createTable('user_permissions', '`read` TINYINT NOT NULL, `create` TINYINT NOT NULL,
            `update` TINYINT NOT NULL, `moderate` TINYINT NOT NULL, `admin` TINYINT NOT NULL, cid INT NOT NULL,
            uid INT NOT NULL, ' . $this->index('user_permissions', 'cid') . ', ' . $this->index('user_permissions', 'uid')
            . ', PRIMARY KEY (cid, uid)');
        $this->createTable('settings', 'name VARCHAR(64) NOT NULL, value LONGTEXT DEFAULT NULL, PRIMARY KEY (name)');

        $this->foreignKey('roles', 'cid', 'calendars', 'cid', 'CASCADE');
        $this->foreignKey('role_users', 'rid', 'roles', 'rid', 'CASCADE');
        $this->foreignKey('role_users', 'uid', 'users', 'uid', 'CASCADE');
        $this->foreignKey('categories', 'cid', 'calendars', 'cid', 'CASCADE');
        $this->foreignKey('categories', 'rid', 'roles', 'rid', 'SET NULL');
        $this->foreignKey('field_definitions', 'cid', 'calendars', 'cid', 'CASCADE');
        $this->foreignKey('events', 'cid', 'calendars', 'cid', 'CASCADE');
        $this->foreignKey('events', 'owner_uid', 'users', 'uid', 'SET NULL');
        $this->foreignKey('events', 'catid', 'categories', 'catid', 'SET NULL');
        $this->foreignKey('occurrences', 'eid', 'events', 'eid', 'CASCADE');
        $this->foreignKey('fields', 'fid', 'field_definitions', 'fid', 'CASCADE');
        $this->foreignKey('fields', 'eid', 'events', 'eid', 'CASCADE');
        $this->foreignKey('user_permissions', 'cid', 'calendars', 'cid', 'CASCADE');
        $this->foreignKey('user_permissions', 'uid', 'users', 'uid', 'CASCADE');
    }

    public function down(Schema $schema): void
    {
        foreach (['fields', 'occurrences', 'events', 'field_definitions', 'categories', 'role_users', 'roles',
            'user_permissions', 'settings', 'users', 'calendars'] as $name) {
            $this->addSql('DROP TABLE ' . $this->table($name));
        }
    }

    private function createTable(string $name, string $definition): void
    {
        $this->addSql('CREATE TABLE ' . $this->table($name) . ' (' . preg_replace('/\s+/', ' ', $definition)
            . ') ' . self::TABLE_OPTIONS);
    }

    /**
     * An index on the column that a foreign key uses
     */
    private function index(string $table, string $column): string
    {
        return 'INDEX ' . $this->generatedName('IDX', $table, $column) . " ($column)";
    }

    private function foreignKey(string $table, string $column, string $target, string $targetColumn, string $onDelete): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s',
            $this->table($table), $this->generatedName('FK', $table, $column), $column,
            $this->table($target), $targetColumn, $onDelete));
    }
}
