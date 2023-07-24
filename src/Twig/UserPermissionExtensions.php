<?php

namespace App\Twig;

use App\Entity\Calendar;
use App\Entity\User;
use App\Entity\UserPermissions;
use App\Repository\UserPermissionsRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserPermissionExtensions extends AbstractExtension
{
    public function __construct(private UserPermissionsRepository $userPermissionsRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'can_create',
                function(int $cid, ?User $user)
                {
                    $permissions = $this->getPermissions($cid, $user);
                    return $permissions->canCreate();
                }
            ),
            new TwigFunction(
                'can_read',
                function(int $cid, ?User $user)
                {
                    $permissions = $this->getPermissions($cid, $user);
                    return $permissions->canRead();
                }
            ),
            new TwigFunction(
                'can_admin',
                function(int $cid, ?User $user)
                {
                    $permissions = $this->getPermissions($cid, $user);
                    return $permissions->canAdmin();
                }
            ),
            new TwigFunction(
                'can_moderate',
                function(int $cid, ?User $user)
                {
                    $permissions = $this->getPermissions($cid, $user);
                    return $permissions->canModerate();
                }
            ),
            new TwigFunction(
                'can_update',
                function(int $cid, ?User $user)
                {
                    $permissions = $this->getPermissions($cid, $user);
                    return $permissions->canUpdate();
                }
            ),
        ];
    }

    private function getPermissions(int $cid, ?User $user): UserPermissions {
        return $this->userPermissionsRepository->getUserPermissions($cid, $user);
    }
}