<?php

declare(strict_types=1);

namespace <namespace>;

use App\Doctrine\PrefixedMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * TODO: say what this changes, and write table names with $this->table('name') so they get TABLE_PREFIX
 */
final class <className> extends PrefixedMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
<up>
    }

    public function down(Schema $schema): void
    {
<down>
    }<override>
}
