<?php

namespace App\Tests\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The tests' database is built by the migrations (see tests/bootstrap.php) with TABLE_PREFIX set
 */
class SchemaTest extends KernelTestCase
{
    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testMigrationsMatchTheEntities(): void
    {
        $em = $this->entityManager();
        $sql = (new SchemaTool($em))->getUpdateSchemaSql($em->getMetadataFactory()->getAllMetadata());
        // The migrations' own record isn't an entity
        $sql = array_values(array_filter($sql, fn(string $statement) => !str_contains($statement, 'doctrine_migration_versions')));

        $this->assertSame([], $sql, 'Run doctrine:migrations:diff to add a migration for these changes');
    }

    public function testEveryTableHasThePrefix(): void
    {
        $tables = $this->entityManager()->getConnection()->createSchemaManager()->listTableNames();

        $this->assertContains('test_calendars', $tables);
        $this->assertContains('test_role_users', $tables);
        $this->assertContains('test_doctrine_migration_versions', $tables);
        $this->assertSame([], array_values(array_filter($tables, fn(string $table) => !str_starts_with($table, 'test_'))));
    }
}
