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
use App\Entity\CalendarRole;
use App\Entity\Category;
use App\Entity\FieldDefinition;
use App\Form\CalendarFormType;
use App\Form\CalendarRoleFormType;
use App\Form\CategoryFormType;
use App\Form\FieldDefinitionFormType;
use App\Form\PermissionLabels;
use App\Repository\UserPermissionsRepository;
use App\Repository\UserRepository;
use App\Security\Voter\CalendarVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A calendar's settings, user permissions, roles, categories and custom fields. Needs admin
 * permission on the calendar.
 */
#[Route('/{_locale}/calendar/{slug}/admin', requirements: ['slug' => Calendar::SLUG_PATTERN])]
class CalendarAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'calendar_admin', methods: ['GET', 'POST'])]
    public function settings(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        Request $request,
        UserRepository $userRepository,
        UserPermissionsRepository $permissionsRepository,
    ): Response {
        $this->denyAccessUnlessGranted(CalendarVoter::ADMIN, $calendar);

        $form = $this->createForm(CalendarFormType::class, $calendar);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'calendar-saved-message');

            return $this->redirectToRoute('calendar_admin', ['slug' => $calendar->getSlug()]);
        }
        if ($form->isSubmitted()) {
            // The form shows what was submitted; the rest of the page (links, title) uses the saved calendar
            $this->entityManager->refresh($calendar);
        }

        $permissions = [];
        foreach ($permissionsRepository->findBy(['calendar' => $calendar]) as $row) {
            $permissions[$row->getUser()->getUid()] = $row;
        }

        return $this->render('calendar/admin.html.twig', [
            'calendar' => $calendar,
            'calendar_form' => $form,
            'users' => $userRepository->findBy([], ['username' => 'ASC']),
            'permissions' => $permissions,
            'permission_labels' => PermissionLabels::LABELS,
            'permission_help' => PermissionLabels::HELP,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/permissions', name: 'calendar_permissions', methods: ['POST'])]
    public function permissions(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        Request $request,
        UserRepository $userRepository,
        UserPermissionsRepository $permissionsRepository,
    ): Response {
        $this->denyAccessUnlessGranted(CalendarVoter::ADMIN, $calendar);
        $this->checkToken($request, 'calendar-permissions-' . $calendar->getCid());

        $submitted = $request->getPayload()->all('perm');
        foreach ($userRepository->findAll() as $user) {
            $values = $submitted[$user->getUid()] ?? [];
            $row = $permissionsRepository->findOrCreate($calendar, $user);
            $row->setRead(!empty($values['read']))
                ->setCreate(!empty($values['create']))
                ->setUpdate(!empty($values['update']))
                ->setModerate(!empty($values['moderate']))
                ->setAdmin(!empty($values['admin']));
            $granted = $row->canRead() || $row->canCreate() || $row->canUpdate() || $row->canModerate() || $row->canAdmin();
            if ($granted) {
                $this->entityManager->persist($row);
            } elseif ($this->entityManager->contains($row)) {
                $this->entityManager->remove($row);
            }
        }
        $this->entityManager->flush();
        $this->addFlash('success', 'permissions-saved-message');

        return $this->redirectToRoute('calendar_admin', ['slug' => $calendar->getSlug(), '_fragment' => 'users']);
    }

    #[Route('/role/new', name: 'create_role', methods: ['GET', 'POST'])]
    #[Route('/role/{rid}', name: 'edit_role', requirements: ['rid' => '\d+'], methods: ['GET', 'POST'])]
    public function role(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        Request $request,
        ?int $rid = null,
    ): Response {
        $this->denyAccessUnlessGranted(CalendarVoter::ADMIN, $calendar);
        $role = $rid === null
            ? new CalendarRole($calendar)
            : $this->findOnCalendar(CalendarRole::class, $rid, $calendar);

        $form = $this->createForm(CalendarRoleFormType::class, $role);

        return $this->handleItemForm($request, $form, $calendar, $role, 'roles', 'role-saved-message', [
            'heading' => $rid === null ? 'create-role-title' : 'edit-role-title',
        ]);
    }

    #[Route('/role/{rid}/delete', name: 'delete_role', requirements: ['rid' => '\d+'], methods: ['POST'])]
    public function deleteRole(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar, int $rid, Request $request): Response
    {
        return $this->deleteItem($request, $calendar, CalendarRole::class, $rid, 'role', 'roles');
    }

    #[Route('/category/new', name: 'create_category', methods: ['GET', 'POST'])]
    #[Route('/category/{catid}', name: 'edit_category', requirements: ['catid' => '\d+'], methods: ['GET', 'POST'])]
    public function category(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        Request $request,
        ?int $catid = null,
    ): Response {
        $this->denyAccessUnlessGranted(CalendarVoter::ADMIN, $calendar);
        $category = $catid === null
            ? new Category($calendar)
            : $this->findOnCalendar(Category::class, $catid, $calendar);

        $form = $this->createForm(CategoryFormType::class, $category, [
            'roles' => $calendar->getRoles()->toArray(),
        ]);

        return $this->handleItemForm($request, $form, $calendar, $category, 'categories', 'category-saved-message', [
            'heading' => $catid === null ? 'create-category-title' : 'edit-category-title',
        ]);
    }

    #[Route('/category/{catid}/delete', name: 'delete_category', requirements: ['catid' => '\d+'], methods: ['POST'])]
    public function deleteCategory(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar, int $catid, Request $request): Response
    {
        return $this->deleteItem($request, $calendar, Category::class, $catid, 'category', 'categories');
    }

    #[Route('/field/new', name: 'create_field', methods: ['GET', 'POST'])]
    #[Route('/field/{fid}', name: 'edit_field', requirements: ['fid' => '\d+'], methods: ['GET', 'POST'])]
    public function field(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        Request $request,
        ?int $fid = null,
    ): Response {
        $this->denyAccessUnlessGranted(CalendarVoter::ADMIN, $calendar);
        $field = $fid === null
            ? new FieldDefinition($calendar)
            : $this->findOnCalendar(FieldDefinition::class, $fid, $calendar);

        $form = $this->createForm(FieldDefinitionFormType::class, $field);

        return $this->handleItemForm($request, $form, $calendar, $field, 'fields', 'field-saved-message', [
            'heading' => $fid === null ? 'create-field-title' : 'edit-field-title',
        ]);
    }

    #[Route('/field/{fid}/delete', name: 'delete_field', requirements: ['fid' => '\d+'], methods: ['POST'])]
    public function deleteField(#[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar, int $fid, Request $request): Response
    {
        return $this->deleteItem($request, $calendar, FieldDefinition::class, $fid, 'field', 'fields');
    }

    private function handleItemForm(
        Request $request,
        FormInterface $form,
        Calendar $calendar,
        object $item,
        string $tab,
        string $message,
        array $parameters,
    ): Response {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($item);
            $this->entityManager->flush();
            $this->addFlash('success', $message);

            return $this->redirectToRoute('calendar_admin', ['slug' => $calendar->getSlug(), '_fragment' => $tab]);
        }

        return $this->render('calendar/admin_item.html.twig', $parameters + [
            'calendar' => $calendar,
            'form' => $form,
            'tab' => $tab,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /**
     * @param class-string $class
     */
    private function deleteItem(Request $request, Calendar $calendar, string $class, int $id, string $kind, string $tab): Response
    {
        $this->denyAccessUnlessGranted(CalendarVoter::ADMIN, $calendar);
        $this->checkToken($request, "delete-$kind-$id");
        $this->entityManager->remove($this->findOnCalendar($class, $id, $calendar));
        $this->entityManager->flush();
        $this->addFlash('success', "$kind-deleted-message");

        return $this->redirectToRoute('calendar_admin', ['slug' => $calendar->getSlug(), '_fragment' => $tab]);
    }

    /**
     * Loads a role, category or field definition, making sure it belongs to $calendar
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function findOnCalendar(string $class, int $id, Calendar $calendar): object
    {
        $item = $this->entityManager->find($class, $id);
        if ($item === null || $item->getCalendar() !== $calendar) {
            throw $this->createNotFoundException();
        }

        return $item;
    }

    private function checkToken(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}
