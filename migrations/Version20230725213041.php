<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230725213041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE calendars (cid INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, subject_max INT NOT NULL, events_max INT NOT NULL, timezone VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, theme VARCHAR(255) DEFAULT NULL, PRIMARY KEY(cid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categories (catid INT AUTO_INCREMENT NOT NULL, cid INT DEFAULT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(255) NOT NULL, INDEX IDX_3AF346684B30D9C4 (cid), PRIMARY KEY(catid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE config (`key` VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(`key`)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE events (eid INT AUTO_INCREMENT NOT NULL, cid INT DEFAULT NULL, owner_uid INT DEFAULT NULL, catid INT DEFAULT NULL, ctime DATETIME NOT NULL, mtime DATETIME DEFAULT NULL, pubtime DATETIME DEFAULT NULL, subject VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_5387574A4B30D9C4 (cid), INDEX IDX_5387574AFC50184C (owner_uid), INDEX IDX_5387574A3632DFC5 (catid), PRIMARY KEY(eid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE field_definitions (fid INT AUTO_INCREMENT NOT NULL, cid INT DEFAULT NULL, name VARCHAR(255) NOT NULL, is_required TINYINT(1) NOT NULL, format LONGTEXT NOT NULL, INDEX IDX_56D916154B30D9C4 (cid), PRIMARY KEY(fid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fields (fid INT NOT NULL, eid INT NOT NULL, value LONGTEXT NOT NULL, INDEX IDX_7EE5E3884DFB1B2F (fid), INDEX IDX_7EE5E3884FBDA576 (eid), PRIMARY KEY(fid, eid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE occurrences (oid INT AUTO_INCREMENT NOT NULL, eid INT DEFAULT NULL, start DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', time_type INT NOT NULL, INDEX IDX_3F04912C4FBDA576 (eid), PRIMARY KEY(oid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roles (rid INT AUTO_INCREMENT NOT NULL, cid INT DEFAULT NULL, can_read TINYINT(1) NOT NULL, can_create TINYINT(1) NOT NULL, can_update TINYINT(1) NOT NULL, can_moderate TINYINT(1) NOT NULL, can_admin TINYINT(1) NOT NULL, INDEX IDX_B63E2EC74B30D9C4 (cid), PRIMARY KEY(rid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_permissions (cid INT NOT NULL, uid INT NOT NULL, `read` TINYINT(1) NOT NULL, `create` TINYINT(1) NOT NULL, `update` TINYINT(1) NOT NULL, moderate TINYINT(1) NOT NULL, `admin` TINYINT(1) NOT NULL, PRIMARY KEY(cid, uid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (uid INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, is_admin TINYINT(1) NOT NULL, password_is_editable TINYINT(1) NOT NULL, timezone VARCHAR(255) DEFAULT NULL, locale VARCHAR(255) DEFAULT NULL, is_disabled TINYINT(1) NOT NULL, PRIMARY KEY(uid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF346684B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A4B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AFC50184C FOREIGN KEY (owner_uid) REFERENCES users (uid)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A3632DFC5 FOREIGN KEY (catid) REFERENCES categories (catid)');
        $this->addSql('ALTER TABLE field_definitions ADD CONSTRAINT FK_56D916154B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid)');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT FK_7EE5E3884DFB1B2F FOREIGN KEY (fid) REFERENCES field_definitions (fid)');
        $this->addSql('ALTER TABLE fields ADD CONSTRAINT FK_7EE5E3884FBDA576 FOREIGN KEY (eid) REFERENCES events (eid)');
        $this->addSql('ALTER TABLE occurrences ADD CONSTRAINT FK_3F04912C4FBDA576 FOREIGN KEY (eid) REFERENCES events (eid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE roles ADD CONSTRAINT FK_B63E2EC74B30D9C4 FOREIGN KEY (cid) REFERENCES calendars (cid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF346684B30D9C4');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574A4B30D9C4');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574AFC50184C');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574A3632DFC5');
        $this->addSql('ALTER TABLE field_definitions DROP FOREIGN KEY FK_56D916154B30D9C4');
        $this->addSql('ALTER TABLE fields DROP FOREIGN KEY FK_7EE5E3884DFB1B2F');
        $this->addSql('ALTER TABLE fields DROP FOREIGN KEY FK_7EE5E3884FBDA576');
        $this->addSql('ALTER TABLE occurrences DROP FOREIGN KEY FK_3F04912C4FBDA576');
        $this->addSql('ALTER TABLE roles DROP FOREIGN KEY FK_B63E2EC74B30D9C4');
        $this->addSql('DROP TABLE calendars');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE config');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE field_definitions');
        $this->addSql('DROP TABLE fields');
        $this->addSql('DROP TABLE occurrences');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE user_permissions');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
