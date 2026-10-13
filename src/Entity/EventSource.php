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
namespace App\Entity;

/**
 * Something whose events are shown together on the month, day and search pages: a calendar, or a view
 * of several calendars. Pages about an event source use its display settings.
 */
interface EventSource
{
    public function getTitle(): string;

    public function getSlug(): string;

    public function getTimezone(): string;

    /** Language for visitors who haven't chosen one, or null for the browser's */
    public function getLocale(): ?string;

    /** Theme ID (ThemeRegistry), or null for the site's default */
    public function getTheme(): ?string;

    /** Events shown per day in the month view before "more events" */
    public function getEventsMax(): int;

    /**
     * The calendars whose events are shown
     *
     * @return Calendar[]
     */
    public function getCalendars(): array;

    public function isView(): bool;

    /** The color marking $calendar's events here, or null when they aren't marked */
    public function getCalendarColor(Calendar $calendar): ?string;
}
