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

namespace App\Security\Voter;

use App\Entity\Calendar;
use App\Entity\User;
use App\Entity\View;
use App\Security\PermissionResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Calendar-wide permissions. For a view, reading and creating events are allowed when they are on at
 * least one of its calendars; the rest only apply to calendars.
 *
 * @extends Voter<string, Calendar|View>
 */
class CalendarVoter extends Voter
{
    public const READ = 'CALENDAR_READ';
    public const CREATE = 'CALENDAR_CREATE';
    public const UPDATE = 'CALENDAR_UPDATE';
    public const MODERATE = 'CALENDAR_MODERATE';
    public const ADMIN = 'CALENDAR_ADMIN';

    public function __construct(private PermissionResolver $resolver)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($subject instanceof View) {
            return in_array($attribute, [self::READ, self::CREATE], true);
        }

        return $subject instanceof Calendar
            && in_array($attribute, [self::READ, self::CREATE, self::UPDATE, self::MODERATE, self::ADMIN], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        $user = $user instanceof User ? $user : null;
        if ($subject instanceof View) {
            foreach ($subject->getCalendars() as $calendar) {
                $permissions = $this->resolver->getPermissions($calendar, $user);
                if ($attribute === self::READ ? $permissions->read : $permissions->create) {
                    return true;
                }
            }

            return false;
        }
        $permissions = $this->resolver->getPermissions($subject, $user);

        return match ($attribute) {
            self::READ => $permissions->read,
            self::CREATE => $permissions->create,
            self::UPDATE => $permissions->update,
            self::MODERATE => $permissions->moderate,
            self::ADMIN => $permissions->admin,
        };
    }
}
