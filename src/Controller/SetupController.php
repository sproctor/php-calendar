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

use App\Entity\Calendar;
use App\Form\Type\CalendarType;
use App\Repository\CalendarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SetupController extends AbstractController
{
    /**
     * @Route("/setup", name="setup")
     */
    public function setup(Request $request, CalendarRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $calendars = $repository->findAll();

        if (empty($calendars)) {
            $calendar = new Calendar();
            $form = $this->createForm(CalendarType::class, $calendar);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($calendar);
                $entityManager->flush();
                return $this->redirectToRoute('default_month_display', ['cid' => $calendar->getCid()]);
            }
            return $this->renderForm('calendar_create.html.twig', ['form' => $form]);
        }

        throw $this->createAccessDeniedException();
    }
}
