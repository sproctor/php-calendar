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
 * Field represents an user defined field for an event.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
class Field
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $fid;

    /**
     * @ManyToOne(targetEntity="Calendar")
     * @JoinColumn(name="did", referencedColumnName="did")
     */
    private $calendar;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $is_required;

    /**
     * @ORM\Column(type="text")
     */
    private $format;
}
