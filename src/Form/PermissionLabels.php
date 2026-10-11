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

namespace App\Form;

/**
 * Translation keys for the calendar permissions, in display order
 */
final class PermissionLabels
{
    public const LABELS = [
        'read' => 'read-label',
        'create' => 'write-label',
        'update' => 'modify-label',
        'moderate' => 'moderate-label',
        'admin' => 'admin-label',
    ];

    public const HELP = [
        'read' => 'read-permission-help',
        'create' => 'create-permission-help',
        'update' => 'update-permission-help',
        'moderate' => 'moderate-permission-help',
        'admin' => 'admin-permission-help',
    ];
}
