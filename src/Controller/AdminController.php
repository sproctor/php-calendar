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
use App\Form\UserType;
use App\Repository\CalendarRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Site administration: calendars and user accounts
 */
#[Route('/{_locale}/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: 'admin', methods: ['GET'])]
    public function index(CalendarRepository $calendarRepository, UserRepository $userRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'calendars' => $calendarRepository->findBy([], ['title' => 'ASC']),
            'users' => $userRepository->findBy([], ['username' => 'ASC']),
        ]);
    }

    #[Route('/calendar/create', name: 'create_calendar', methods: ['GET', 'POST'])]
    public function createCalendar(Request $request): Response
    {
        $calendar = new Calendar();
        $calendar->setTimezone(date_default_timezone_get());
        $form = $this->createForm(CalendarFormType::class, $calendar);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($calendar);
            $this->entityManager->flush();
            $this->addFlash('success', 'calendar-created-message');

            return $this->redirectToRoute('admin', ['_fragment' => 'calendars']);
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'heading' => 'create-calendar-label',
            'tab' => 'calendars',
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/calendar/{slug}/delete', name: 'delete_calendar', requirements: ['slug' => Calendar::SLUG_PATTERN], methods: ['POST'])]
    public function deleteCalendar(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar, Request $request): Response
    {
        $this->checkToken($request, 'delete-calendar-' . $calendar->getCid());
        // Events, categories, roles, fields and permissions go with it through foreign keys
        $this->entityManager->remove($calendar);
        $this->entityManager->flush();
        $this->addFlash('success', 'calendar-deleted-message');

        return $this->redirectToRoute('admin', ['_fragment' => 'calendars']);
    }

    #[Route('/user/create', name: 'create_user', methods: ['GET', 'POST'])]
    #[Route('/user/{uid}', name: 'edit_user', requirements: ['uid' => '\d+'], methods: ['GET', 'POST'])]
    public function user(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        ?int $uid = null,
    ): Response {
        $user = $uid === null ? new User() : $userRepository->find($uid);
        if ($user === null) {
            throw $this->createNotFoundException();
        }
        $creating = $uid === null;
        $form = $this->createForm(UserType::class, $user, [
            'password_required' => $creating,
            'include_admin' => true,
        ])->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();
            if ($password !== null && $password !== '') {
                $user->setHash($passwordHasher->hashPassword($user, $password));
            }
            if ($user === $this->getUser() && !$user->isAdmin()) {
                // Don't let admins lock themselves out of this page
                $user->setIsAdmin(true);
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', $creating ? 'user-created-message' : 'user-saved-message');

            return $this->redirectToRoute('admin', ['_fragment' => 'users']);
        }

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'heading' => $creating ? 'create-user-label' : 'edit-user-title',
            'tab' => 'users',
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/user/{uid}/disable', name: 'disable_user', requirements: ['uid' => '\d+'], methods: ['POST'])]
    public function disableUser(#[MapEntity(id: 'uid')] User $user, Request $request): Response
    {
        return $this->setDisabled($user, true, $request);
    }

    #[Route('/user/{uid}/enable', name: 'enable_user', requirements: ['uid' => '\d+'], methods: ['POST'])]
    public function enableUser(#[MapEntity(id: 'uid')] User $user, Request $request): Response
    {
        return $this->setDisabled($user, false, $request);
    }

    #[Route('/user/{uid}/delete', name: 'delete_user', requirements: ['uid' => '\d+'], methods: ['POST'])]
    public function deleteUser(#[MapEntity(id: 'uid')] User $user, Request $request): Response
    {
        $this->checkToken($request, 'delete-user-' . $user->getUid());
        $this->protectSelf($user);
        // Their events stay, without an owner; permissions and role memberships go
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'user-deleted-message');

        return $this->redirectToRoute('admin', ['_fragment' => 'users']);
    }

    private function setDisabled(User $user, bool $disabled, Request $request): Response
    {
        $this->checkToken($request, 'toggle-user-' . $user->getUid());
        $this->protectSelf($user);
        $user->setIsDisabled($disabled);
        $this->entityManager->flush();
        $this->addFlash('success', $disabled ? 'user-disabled-message' : 'user-enabled-message');

        return $this->redirectToRoute('admin', ['_fragment' => 'users']);
    }

    private function protectSelf(User $user): void
    {
        if ($user === $this->getUser()) {
            throw $this->createAccessDeniedException('Admins can\'t disable or delete their own account');
        }
    }

    private function checkToken(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}
