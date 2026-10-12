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
namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    public const DEFAULT_THEME = 'default_theme';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function getValue(string $name): ?string
    {
        return $this->find($name)?->getValue();
    }

    /**
     * Saves $value (null removes the setting)
     */
    public function setValue(string $name, ?string $value): void
    {
        $setting = $this->find($name);
        if ($value === null) {
            if ($setting !== null) {
                $this->getEntityManager()->remove($setting);
            }
        } elseif ($setting === null) {
            $this->getEntityManager()->persist(new Setting($name, $value));
        } else {
            $setting->setValue($value);
        }
        $this->getEntityManager()->flush();
    }
}
