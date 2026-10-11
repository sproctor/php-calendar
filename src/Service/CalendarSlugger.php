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

namespace App\Service;

use App\Entity\Calendar;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Makes URL slugs for calendars from their titles
 */
class CalendarSlugger
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    /**
     * A slug for $title, e.g. "Club Events 2026" becomes "club-events-2026". Falls back to "calendar"
     * for titles with nothing usable in them.
     */
    public function slugify(string $title): string
    {
        $slug = $this->slugger->slug($title)->lower()->truncate(Calendar::SLUG_MAX_LENGTH)->trim('-')->toString();

        return $slug !== '' ? $slug : 'calendar';
    }

    /**
     * A slug for $title that isn't in $taken, adding -2, -3, … when needed
     *
     * @param callable(string): bool $taken tells whether a slug is already used
     */
    public function uniqueSlug(string $title, callable $taken): string
    {
        $base = $this->slugify($title);
        $slug = $base;
        for ($n = 2; $taken($slug); $n++) {
            $suffix = "-$n";
            $slug = rtrim(substr($base, 0, Calendar::SLUG_MAX_LENGTH - strlen($suffix)), '-') . $suffix;
        }

        return $slug;
    }
}
