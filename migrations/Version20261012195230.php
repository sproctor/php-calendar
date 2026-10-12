<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds an optional email address to users, unique so people can log in with it.
 */
final class Version20261012195230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add users.email';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD email VARCHAR(180) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON users (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX email ON users');
        $this->addSql('ALTER TABLE users DROP email');
    }
}
