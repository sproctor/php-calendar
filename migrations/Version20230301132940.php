<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230301132940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events CHANGE owner_uid owner_uid INT NOT NULL');
        $this->addSql('ALTER TABLE occurrences DROP FOREIGN KEY FK_3F04912C4FBDA576');
        $this->addSql('ALTER TABLE occurrences ADD CONSTRAINT FK_3F04912C4FBDA576 FOREIGN KEY (eid) REFERENCES events (eid) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events CHANGE owner_uid owner_uid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE occurrences DROP FOREIGN KEY FK_3F04912C4FBDA576');
        $this->addSql('ALTER TABLE occurrences ADD CONSTRAINT FK_3F04912C4FBDA576 FOREIGN KEY (eid) REFERENCES events (eid) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
