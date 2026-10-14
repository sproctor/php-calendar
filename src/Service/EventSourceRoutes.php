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

use App\Entity\EventSource;

/**
 * Route names for the pages of a calendar or a view, which have the same pages under different URLs
 * (/calendar/{slug}/… and /view/{slug}/…)
 */
final class EventSourceRoutes
{
    private const CALENDAR = [
        'default' => 'default_view',
        'month' => 'month_view',
        'mini' => 'mini_view',
        'mini_month' => 'mini_month_view',
        'day' => 'day_view',
        'search' => 'search',
        'create' => 'create_event',
        'feed' => 'calendar_feed',
        'personal_feed' => 'calendar_personal_feed',
        'entry' => 'calendar_entry_slash',
    ];

    private const VIEW = [
        'default' => 'view_default',
        'month' => 'view_month',
        'mini' => 'view_mini',
        'mini_month' => 'view_mini_month',
        'day' => 'view_day',
        'search' => 'view_search',
        'create' => 'view_create_event',
        'feed' => 'view_feed',
        'personal_feed' => 'view_personal_feed',
        'entry' => 'view_entry',
    ];

    /**
     * @param string $page default, month, mini, mini_month, day, search, create, feed, personal_feed or entry
     */
    public static function route(EventSource $source, string $page): string
    {
        return ($source->isView() ? self::VIEW : self::CALENDAR)[$page]
            ?? throw new \InvalidArgumentException("No $page page");
    }

    /**
     * The route parameters naming $source, plus $parameters
     */
    public static function parameters(EventSource $source, array $parameters = []): array
    {
        return ['slug' => $source->getSlug()] + $parameters;
    }
}
