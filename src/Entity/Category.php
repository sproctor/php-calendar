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

namespace PhpCalendar;

/**
 * @Entity
 * @Table("categories")
 */
class Category
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $catid;

    /**
     * @ManyToOne(targetEntity="Calendar")
     * @JoinColumn(name="cid", referencedColumnName="cid")
     */
    private $calendar;

    /**
     * @Column(type="string", length=255)
     */
    private $name;

    /**
     * @Column(type="string", length=255)
     */
    private $color;
}
