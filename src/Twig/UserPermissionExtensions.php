<?php

namespace App\Twig;

use App\Entity\Calendar;
use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserPermissionExtensions extends AbstractExtension
{
    private UserPermissionsRepository $user_permissions_repository;

    public function __construct()
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                'can_write',
                function(Calendar $calendar, ?User $user)
                {

                }
            ),
        ];
    }

}