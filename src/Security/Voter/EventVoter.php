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
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use App\Security\PermissionResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Per-event permissions.
 *
 * Viewing needs read access to the calendar and, when the event's category is limited to a role,
 * membership in that role. Events with a future publish time are only shown to people who can edit them.
 * Editing (which includes deleting and changing occurrences) is allowed for calendar admins, the event's
 * owner, and users with update permission unless the event is read-only.
 *
 * @extends Voter<string, Event>
 */
class EventVoter extends Voter
{
    public const VIEW = 'EVENT_VIEW';
    public const EDIT = 'EVENT_EDIT';

    public function __construct(private PermissionResolver $resolver)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Event && in_array($attribute, [self::VIEW, self::EDIT], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        $user = $user instanceof User ? $user : null;

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
        };
    }

    public function canView(Event $event, ?User $user): bool
    {
        return $this->canSeeCategory($event->getCalendar(), $event->getCategory(), $user) && ($event->isPublished() || $this->canEdit($event, $user));
    }

    public function canEdit(Event $event, ?User $user): bool
    {
        $permissions = $this->resolver->getPermissions($event->getCalendar(), $user);
        if ($permissions->admin) {
            return true;
        }
        if (!$this->canSeeCategory($event->getCalendar(), $event->getCategory(), $user)) {
            return false;
        }

        return $event->isOwner($user) || ($permissions->update && !$event->isReadonly());
    }

    /**
     * The calendar's categories whose events $user can see: none without read access, all of them for
     * calendar admins, else those not limited to a role they aren't in
     *
     * @return Category[]
     */
    public function visibleCategories(Calendar $calendar, ?User $user): array
    {
        return array_values(array_filter(
            $calendar->getCategories()->toArray(),
            fn(Category $category) => $this->canSeeCategory($calendar, $category, $user),
        ));
    }

    private function canSeeCategory(Calendar $calendar, ?Category $category, ?User $user): bool
    {
        $permissions = $this->resolver->getPermissions($calendar, $user);
        if (!$permissions->read) {
            return false;
        }
        $role = $category?->getRole();

        return $role === null || $permissions->admin || $role->hasUser($user);
    }
}
