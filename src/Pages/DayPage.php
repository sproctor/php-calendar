<?php
/*
 * Copyright 2016 Sean Proctor
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

/*
   This file has the functions for the day displays of the calendar
*/

namespace PhpCalendar;

use Symfony\Component\HttpFoundation\Response;

class DayPage extends Page
{
    // View for a single day
    /**
     * @param Context $context
     * @return Response
     * @throws \Exception
     */
    public function action(Context $context)
    {
        $occurrences = $context->db->getOccurrencesByDate(
            $context->calendar->getCid(),
            $context->getYear(),
            $context->getMonth(),
            $context->getDay()
        );
        
        $today = (new \DateTime())->setDate($context->getYear(), $context->getMonth(), $context->getDay());
        $yesterday = (clone $today)->sub(new \DateInterval("P1D"));
        $tomorrow = $today->add(new \DateInterval("P1D"));
        return new Response($context->render(
            "day_page.html.twig",
            array('occurrences' => $occurrences, 'yesterday' => $yesterday, 'tomorrow' => $tomorrow)
        ));
    }
}
