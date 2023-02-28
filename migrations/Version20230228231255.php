<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230228231255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574AB2DCD28A');
        $this->addSql('DROP INDEX IDX_5387574AB2DCD28A ON events');
        $this->addSql('ALTER TABLE events DROP author_uid, CHANGE pubtime pubtime DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ADD author_uid INT DEFAULT NULL, CHANGE pubtime pubtime DATETIME NOT NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AB2DCD28A FOREIGN KEY (author_uid) REFERENCES users (uid) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5387574AB2DCD28A ON events (author_uid)');
    }
}
