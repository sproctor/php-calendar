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
use App\Form\CalendarFormType;
use App\Form\UserFormType;
use App\Repository\CalendarRepository;
use App\Repository\OccurrenceRepository;
use App\Repository\UserPermissionsRepository;
use App\Repository\UserRepository;
use App\Service\LocaleService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/admin")]
class AdminController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
    )
    {
    }

    #[Route("/", name: "admin")]
    public function settings(): Response
    {
        /* @var User $user */
        $user = $this->getUser();
        if ($user == null || !$user->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('admin/index.html.twig');
    }

    #[Route("/user/{uid}/disable", name: "disable_user")]
    public function disableUser(
        int                    $uid,
        UserRepository         $userRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        /* @var User $current_user */
        $current_user = $this->getUser();
        if (!$current_user?->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        /* @var User $user */
        $user = $userRepository->find($uid);
        $user->setIsDisabled(true);
        $entityManager->persist($user);
        $entityManager->flush();
        // TODO: message that the user was created
        // TODO: handle exception
        return $this->redirectToRoute('admin', ['_fragment' => 'users']);
    }

    #[Route("/user/{uid}/enable", name: "enable_user")]
    public function enableUser(
        int                    $uid,
        UserRepository         $userRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        /* @var User $current_user */
        $current_user = $this->getUser();
        if (!$current_user?->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        /* @var User $user */
        $user = $userRepository->find($uid);
        $user->setIsDisabled(false);
        $entityManager->persist($user);
        $entityManager->flush();
        // TODO: message that the user was created
        // TODO: handle exception
        return $this->redirectToRoute('admin', ['_fragment' => 'users']);
    }

    #[Route("/user/create", name: "create_user")]
    public function createUser(
        Request                     $request,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response
    {
        if (!$this->getUser()->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        $user = new User();
        $form = $this->createForm(UserFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setHash($hashedPassword);
            $entityManager->persist($user);
            $entityManager->flush();
            // TODO add message
            return $this->redirectToRoute('admin', ['_fragment' => 'users']);
        }

        // else
        return $this->render("admin/create_user.html.twig", ['form' => $form]);
    }

    #[Route("/calendar/create", name: "create_calendar")]
    public function createCalendar(
        Request                $request,
        EntityManagerInterface $entityManager,
    ): Response
    {
        if (!$this->getUser()->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        $calendar = new Calendar();
        $form = $this->createForm(
            CalendarFormType::class,
            $calendar,
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($calendar);
            $entityManager->flush();
            // TODO add message
            return $this->redirectToRoute('admin', ['_fragment' => 'calendars']);
        }

        // else
        return $this->render("admin/create_calendar.html.twig", ['form' => $form]);
    }

    #[Route("/calendar/{cid}/delete", name: "delete_calendar")]
    public function deleteCalendar(
        int                    $cid,
        CalendarRepository         $calendarRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        /* @var User $current_user */
        $current_user = $this->getUser();
        if (!$current_user?->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        /* @var Calendar $calendar */
        $calendar = $calendarRepository->find($cid);
        $entityManager->remove($calendar);
        $entityManager->flush();
        // TODO: message that the user was created
        // TODO: handle exception
        return $this->redirectToRoute('admin', ['_fragment' => 'calendars']);
    }
}