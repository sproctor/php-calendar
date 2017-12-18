<?php
/*
 * Copyright 2017 Sean Proctor
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
   This file has the functions for the main displays of the calendar
*/

namespace PhpCalendar;

use Symfony\Component\HttpFoundation\Response;

class EventPage extends Page
{
	/**
	 * Display for a single event
	 * 
	 * @param Context $context
	 * @return Response
	 */
	function action(Context $context)
	{	
		if ($context->request->get('oid') !== null) {
			$event = $context->db->get_event_by_oid($context->request->get('oid'));
			if(!$event) {
				throw new InvalidInputException(__('There is no event for that OID.'));
			}
		} elseif($context->request->get('eid') !== null) {
			$event = $context->db->get_event_by_eid($context->request->get('eid'));
			if(!$event) {
				throw new InvalidInputException(__('There is no event with that EID.'));
			}
		} else {
			throw new InvalidInputException(__("Invalid arguments."));
		}

		if(!$event->canRead($context->getUser())) {
			throw new InvalidInputException(__("You do not have permission to read this event."));
		}

		return new Response($context->twig->render('event_page.html.twig', array('event' => $event)));
	}
}