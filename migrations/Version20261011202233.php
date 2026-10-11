<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Permissions, roles, read-only events and foreign keys for the finished 3.0 port.
 */
final class Version20261011202233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add calendar anonymous permissions, named roles with members, role-limited categories, read-only events; cascade deletes through foreign keys; drop calendar themes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE role_users (rid INT NOT NULL, uid INT NOT NULL, INDEX IDX_E35F4DC656D41083 (rid), INDEX IDX_E35F4DC6539B0606 (uid), PRIMARY KEY (rid, uid)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE role_users ADD CONSTRAINT FK_E35F4DC656D41083 FOREIGN KEY (rid) REFERENCES roles (rid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_users ADD CONSTRAINT FK_E35F4DC6539B0606 FOREIGN KEY (uid) REFERENCES users (uid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE calendars ADD anon_permission SMALLINT DEFAULT 1 NOT NULL, DROP theme');
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY `FK_3AF346684B30D9C4`');
        $this->addSql('ALTER TABLE categories ADD rid INT DEFAULT NULL, CHANGE cid cid INT NOT NULL');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF346684B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF3466856D41083 FOREIGN KEY (rid) REFERENCES roles (rid) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3AF3466856D41083 ON categories (rid)');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY `FK_5387574A3632DFC5`');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY `FK_5387574A4B30D9C4`');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY `FK_5387574AFC50184C`');
        $this->addSql('ALTER TABLE events ADD readonly TINYINT DEFAULT 0 NOT NULL, CHANGE cid cid INT NOT NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A3632DFC5 FOREIGN KEY (catid) REFERENCES categories (catid) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A4B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AFC50184C FOREIGN KEY (owner_uid) REFERENCES users (uid) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE field_definitions DROP FOREIGN KEY `FK_56D916154B30D9C4`');
        $this->addSql('ALTER TABLE field_definitions CHANGE cid cid INT NOT NULL, CHANGE format format LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE field_definitions ADD CONSTRAINT FK_56D916154B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fields DROP FOREIGN KEY `FK_7EE5E3884DFB1B2F`');
        $this->addSql('ALTER TABLE fields DROP FOREIGN KEY `FK_7EE5E3884FBDA576`');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT FK_7EE5E3884DFB1B2F FOREIGN KEY (fid) REFERENCES field_definitions (fid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT FK_7EE5E3884FBDA576 FOREIGN KEY (eid) REFERENCES events (eid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE occurrences CHANGE eid eid INT NOT NULL');
        $this->addSql('ALTER TABLE roles DROP FOREIGN KEY `FK_B63E2EC74B30D9C4`');
        $this->addSql('ALTER TABLE roles ADD name VARCHAR(255) NOT NULL, CHANGE cid cid INT NOT NULL');
        $this->addSql('ALTER TABLE roles ADD CONSTRAINT FK_B63E2EC74B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_84F605FA4B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_84F605FA539B0606 FOREIGN KEY (uid) REFERENCES users (uid) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_84F605FA4B30D9C4 ON user_permissions (cid)');
        $this->addSql('CREATE INDEX IDX_84F605FA539B0606 ON user_permissions (uid)');
        $this->addSql('CREATE UNIQUE INDEX username ON users (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE role_users DROP FOREIGN KEY FK_E35F4DC656D41083');
        $this->addSql('ALTER TABLE role_users DROP FOREIGN KEY FK_E35F4DC6539B0606');
        $this->addSql('DROP TABLE role_users');
        $this->addSql('ALTER TABLE calendars ADD theme VARCHAR(255) DEFAULT NULL, DROP anon_permission');
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF346684B30D9C4');
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF3466856D41083');
        $this->addSql('DROP INDEX IDX_3AF3466856D41083 ON categories');
        $this->addSql('ALTER TABLE categories DROP rid, CHANGE cid cid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT `FK_3AF346684B30D9C4` FOREIGN KEY (cid) REFERENCES calendars (cid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574A4B30D9C4');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574AFC50184C');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574A3632DFC5');
        $this->addSql('ALTER TABLE events DROP readonly, CHANGE cid cid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT `FK_5387574A4B30D9C4` FOREIGN KEY (cid) REFERENCES calendars (cid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT `FK_5387574AFC50184C` FOREIGN KEY (owner_uid) REFERENCES users (uid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT `FK_5387574A3632DFC5` FOREIGN KEY (catid) REFERENCES categories (catid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE field_definitions DROP FOREIGN KEY FK_56D916154B30D9C4');
        $this->addSql('ALTER TABLE field_definitions CHANGE format format LONGTEXT NOT NULL, CHANGE cid cid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE field_definitions ADD CONSTRAINT `FK_56D916154B30D9C4` FOREIGN KEY (cid) REFERENCES calendars (cid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE fields DROP FOREIGN KEY FK_7EE5E3884DFB1B2F');
        $this->addSql('ALTER TABLE fields DROP FOREIGN KEY FK_7EE5E3884FBDA576');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT `FK_7EE5E3884DFB1B2F` FOREIGN KEY (fid) REFERENCES field_definitions (fid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT `FK_7EE5E3884FBDA576` FOREIGN KEY (eid) REFERENCES events (eid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE occurrences CHANGE eid eid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE roles DROP FOREIGN KEY FK_B63E2EC74B30D9C4');
        $this->addSql('ALTER TABLE roles DROP name, CHANGE cid cid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE roles ADD CONSTRAINT `FK_B63E2EC74B30D9C4` FOREIGN KEY (cid) REFERENCES calendars (cid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY FK_84F605FA4B30D9C4');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY FK_84F605FA539B0606');
        $this->addSql('DROP INDEX IDX_84F605FA4B30D9C4 ON user_permissions');
        $this->addSql('DROP INDEX IDX_84F605FA539B0606 ON user_permissions');
        $this->addSql('DROP INDEX username ON users');
    }
}
