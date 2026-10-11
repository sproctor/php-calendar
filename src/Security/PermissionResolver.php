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

namespace App\Security;

use App\Entity\Calendar;
use App\Entity\User;
use App\Repository\CalendarRoleRepository;
use App\Repository\UserPermissionsRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Works out what a user (or an anonymous visitor) can do on a calendar. Effective permissions combine:
 * the calendar's anonymous permission level, which everyone gets; the user's own permissions; and
 * the permissions of every role on the calendar the user belongs to. Site admins can do everything.
 */
class PermissionResolver implements ResetInterface
{
    /** @var array<string, Permissions> */
    private array $cache = [];

    public function __construct(
        private UserPermissionsRepository $userPermissionsRepository,
        private CalendarRoleRepository $roleRepository,
    ) {
    }

    public function getPermissions(Calendar $calendar, ?User $user): Permissions
    {
        $key = $calendar->getCid() . ':' . ($user?->getUid() ?? '');

        return $this->cache[$key] ??= $this->resolve($calendar, $user);
    }

    private function resolve(Calendar $calendar, ?User $user): Permissions
    {
        if ($user !== null && $user->isAdmin()) {
            return Permissions::all();
        }

        $level = $calendar->getAnonPermission();
        $permissions = new Permissions(
            read: $level >= Calendar::ANON_READ,
            create: $level >= Calendar::ANON_CREATE,
            update: $level >= Calendar::ANON_MODIFY,
        );

        if ($user !== null) {
            $own = $this->userPermissionsRepository->findOneBy(['calendar' => $calendar, 'user' => $user]);
            if ($own !== null) {
                $permissions = $permissions->merge(new Permissions(
                    $own->canRead(),
                    $own->canCreate(),
                    $own->canUpdate(),
                    $own->canModerate(),
                    $own->canAdmin(),
                ));
            }
            foreach ($this->roleRepository->findForUser($calendar, $user) as $role) {
                $permissions = $permissions->merge(new Permissions(
                    $role->canRead(),
                    $role->canCreate(),
                    $role->canUpdate(),
                    $role->canModerate(),
                    $role->canAdmin(),
                ));
            }
        }

        return $permissions->normalized();
    }

    /**
     * Forgets cached results. Runs at the start of every request, so long-running workers
     * don't keep stale permissions.
     */
    #[AsEventListener(KernelEvents::REQUEST, priority: 512)]
    public function reset(): void
    {
        $this->cache = [];
    }
}
