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

/**
 * Someone's effective permissions on a calendar
 */
final class Permissions
{
    public function __construct(
        /** Can see the calendar and its events */
        public readonly bool $read = false,
        /** Can create events */
        public readonly bool $create = false,
        /** Can edit and delete other people's events, unless they're read-only */
        public readonly bool $update = false,
        /** Can mark events read-only */
        public readonly bool $moderate = false,
        /** Can edit anything on the calendar and change its settings */
        public readonly bool $admin = false,
    ) {
    }

    public static function all(): self
    {
        return new self(true, true, true, true, true);
    }

    /**
     * Combines two sets of permissions; each permission is granted if either grants it
     */
    public function merge(self $other): self
    {
        return new self(
            $this->read || $other->read,
            $this->create || $other->create,
            $this->update || $other->update,
            $this->moderate || $other->moderate,
            $this->admin || $other->admin,
        );
    }

    /**
     * Admins can do everything, and creating or editing implies reading
     */
    public function normalized(): self
    {
        if ($this->admin) {
            return self::all();
        }

        return new self(
            $this->read || $this->create || $this->update || $this->moderate,
            $this->create,
            $this->update,
            $this->moderate,
            false,
        );
    }
}
