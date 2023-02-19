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

namespace App\Controller;

use App\Repository\CalendarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to redirect to the month view.
 *
 * @author Sean Proctor <sproctor@gmail.com>
 */
class DefaultController extends AbstractController
{

    /**
     * @Route("/", name="default")
     */
    public function index(CalendarRepository $repository): Response
    {
        $calendars = $repository->findAll();

        $template_variables["calendars"] = $calendars;

        if (empty($calendars)) {
            return $this->redirectToRoute('setup');
        } else {
            return new Response($this->render("month_page.html.twig", $template_variables));
        }
    }
}