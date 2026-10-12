<?php
/*
 * Copyright Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace App\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Starts every table's name with TABLE_PREFIX, so the calendar can share a database with other apps
 * (or with PHP-Calendar 2.0). Migrations use the same prefix through PrefixedMigration.
 */
#[AsDoctrineListener(event: Events::loadClassMetadata)]
class TablePrefixListener
{
    public function __construct(
        #[Autowire('%env(TABLE_PREFIX)%')]
        private string $prefix,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        if ($this->prefix === '') {
            return;
        }
        $metadata = $args->getClassMetadata();
        $metadata->setPrimaryTable(['name' => $this->prefix . $metadata->getTableName()]);
        foreach ($metadata->getAssociationMappings() as $mapping) {
            if ($mapping instanceof ManyToManyOwningSideMapping) {
                $mapping->joinTable->name = $this->prefix . $mapping->joinTable->name;
            }
        }
    }
}
