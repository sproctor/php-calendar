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
use App\Entity\User;
use App\Form\CalendarType;
use App\Form\UserType;
use App\Repository\CalendarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SetupController extends AbstractController
{
    #[Route("/setup", name: "setup")]
    public function setup(
        Request                     $request,
        CalendarRepository          $repository,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response
    {
        $calendars = $repository->findAll();

//        if (empty($calendars)) {
        $data = [
            'calendar' => new Calendar(),
            'user' => new User()
        ];
        $form = $this->createFormBuilder($data)
            ->add('calendar', CalendarType::class)
            ->add('user', UserType::class)
            ->add('save', SubmitType::class, ['label' => 'save-label'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($data['calendar']);
            $data['user']->setDefaultCalendar($data['calendar']);
            $hashedPassword = $passwordHasher->hashPassword(
                $data['user'],
                $form->get('user')->get('password')->getData()
            );
            $data['user']->setHash($hashedPassword);
            $entityManager->persist($data['user']);
            $entityManager->flush();
            return $this->redirectToRoute('default_month_display', ['cid' => $data['calendar']->getCid()]);
        }
        return $this->renderForm('setup.html.twig', ['form' => $form]);
//        }

//        throw $this->createAccessDeniedException();
    }
}
