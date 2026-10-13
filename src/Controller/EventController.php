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
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use App\Form\EventFormType;
use App\Form\OverlapGuard;
use App\Form\OccurrenceTimesType;
use App\Repository\EventRepository;
use App\Security\PermissionResolver;
use App\Security\Voter\CalendarVoter;
use App\Security\Voter\EventVoter;
use App\Service\CalendarDates;
use App\Service\OccurrenceGenerator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}')]
class EventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OccurrenceGenerator $occurrenceGenerator,
        private PermissionResolver $permissionResolver,
        private TranslatorInterface $translator,
        private CalendarDates $dates,
        private OverlapGuard $overlapGuard,
    ) {
    }

    #[Route('/event/{eid}', name: 'event_view', requirements: ['eid' => '\d+'], methods: ['GET'])]
    public function view(#[MapEntity(id: 'eid')] Event $event): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::VIEW, $event);

        return $this->render('event/view.html.twig', [
            'event' => $event,
            'calendar' => $event->getCalendar(),
            'date' => $event->getOccurrences()->first() ? $event->getOccurrences()->first()->getStart() : null,
        ]);
    }

    #[Route(
        '/calendar/{slug}/event/new/{year}/{month}/{day}',
        name: 'create_event',
        requirements: ['slug' => Calendar::SLUG_PATTERN, 'year' => '\d{4}', 'month' => '\d{1,2}', 'day' => '\d{1,2}'],
        defaults: ['year' => null, 'month' => null, 'day' => null],
        methods: ['GET', 'POST'],
    )]
    public function create(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        Request $request,
        ?int $year = null,
        ?int $month = null,
        ?int $day = null,
    ): Response {
        $this->denyAccessUnlessGranted(CalendarVoter::CREATE, $calendar);

        $date = new DateTimeImmutable('today');
        if ($year !== null && $month !== null && $day !== null && checkdate($month, $day, $year)) {
            $date = $this->dates->date($year, $month, $day);
        }

        return $this->eventForm($request, new Event($calendar, $this->currentUser()), OccurrenceTimesType::defaults($date));
    }

    #[Route('/event/{eid}/edit', name: 'modify_event', requirements: ['eid' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(#[MapEntity(id: 'eid')] Event $event, Request $request): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $first = $event->getOccurrences()->first();
        $times = $first ? OccurrenceTimesType::fromOccurrence($first) : OccurrenceTimesType::defaults(new DateTimeImmutable('today'));

        return $this->eventForm($request, $event, $times);
    }

    #[Route('/event/{eid}/delete', name: 'delete_event', requirements: ['eid' => '\d+'], methods: ['POST'])]
    public function delete(#[MapEntity(id: 'eid')] Event $event, Request $request): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);
        if (!$this->isCsrfTokenValid('delete-event-' . $event->getEid(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $calendar = $event->getCalendar();
        $this->entityManager->remove($event);
        $this->entityManager->flush();
        $this->addFlash('success', 'event-deleted-message');

        return $this->redirectToRoute('default_view', ['slug' => $calendar->getSlug()]);
    }

    #[Route('/calendar/{slug}/events/delete', name: 'delete_events', requirements: ['slug' => Calendar::SLUG_PATTERN], methods: ['POST'])]
    public function deleteEvents(
        #[MapEntity(mapping: ['slug' => 'slug'])] Calendar $calendar,
        Request $request,
        EventRepository $eventRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('delete-events', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $events = [];
        foreach ($request->getPayload()->all('eid') as $eid) {
            $event = $eventRepository->find((int) $eid);
            // Only events on this calendar that the user can edit; anything else rejects the whole request
            if ($event === null || $event->getCalendar() !== $calendar || !$this->isGranted(EventVoter::EDIT, $event)) {
                throw $this->createAccessDeniedException();
            }
            $events[] = $event;
        }
        foreach ($events as $event) {
            $this->entityManager->remove($event);
        }
        $this->entityManager->flush();
        if ($events !== []) {
            $this->addFlash('success', 'events-deleted-message');
        }

        return $this->redirect($request->headers->get('referer')
            ?: $this->generateUrl('default_view', ['slug' => $calendar->getSlug()]));
    }

    /**
     * @param array $times OccurrenceTimesType data to start the form with
     */
    private function eventForm(Request $request, Event $event, array $times): Response
    {
        $calendar = $event->getCalendar();
        $user = $this->currentUser();
        $modifying = $this->entityManager->contains($event);
        $permissions = $this->permissionResolver->getPermissions($calendar, $user);

        [$repeat, $frequency] = $this->occurrenceGenerator->inferRepeat($event->getOccurrences()->toArray());
        $occurrences = $event->getOccurrences();
        $form = $this->createForm(EventFormType::class, $event, [
            'maxlength' => $calendar->getSubjectMax(),
            'categories' => $this->visibleCategories($calendar, $user, $permissions->admin),
            'field_definitions' => $calendar->getFieldDefinitions()->toArray(),
            'can_moderate' => $permissions->moderate,
            'modifying' => $modifying,
            'times' => $times,
            'repeat' => $repeat,
            'frequency' => $frequency,
            'until' => $occurrences->count() > 1 ? $occurrences->last()->getStart()->setTime(0, 0) : null,
            'delay_publish' => $event->getPubtime() !== null,
            'calendar' => $calendar,
        ]);
        foreach ($calendar->getFieldDefinitions() as $definition) {
            $form->get('field_' . $definition->getFid())->setData($event->getFieldValue($definition));
        }

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            EventFormType::addCrossFieldErrors($form, $this->translator);
        }
        // The dates the form asks for, when it changes them
        $newTimes = null;
        if ($form->isSubmitted() && $form->isValid() && (!$modifying || $form->get('modify')->getData())) {
            [$start, $end, $timeType] = OccurrenceTimesType::toTimes($form->get('times')->getData());
            $newTimes = $this->occurrenceGenerator->times(
                $start,
                $end,
                $timeType,
                $form->get('repeats')->getData(),
                $form->get('frequency')->getData() ?? 1,
                $form->get('until')->getData(),
            );
        }
        if ($form->isSubmitted() && $form->isValid()
            && ($newTimes === null || $this->overlapGuard->allows($form, $calendar, $newTimes, $event))) {
            if (!$form->get('delay_publish')->getData()) {
                $event->setPubtime(null);
            }
            foreach ($calendar->getFieldDefinitions() as $definition) {
                $event->setFieldValue($definition, $form->get('field_' . $definition->getFid())->getData());
            }

            if ($newTimes !== null) {
                // Keeps the dates that stay, so calendar apps update them instead of replacing them
                $this->occurrenceGenerator->update(
                    $event,
                    $start,
                    $end,
                    $timeType,
                    $form->get('repeats')->getData(),
                    $form->get('frequency')->getData() ?? 1,
                    $form->get('until')->getData(),
                );
            }
            if ($modifying) {
                $event->markModified();
            }

            $this->entityManager->persist($event);
            $this->entityManager->flush();
            $this->addFlash('success', $modifying ? 'event-updated-message' : 'event-created-message');

            return $this->redirectToRoute('event_view', ['eid' => $event->getEid()]);
        }

        return $this->render('event/form.html.twig', [
            'form' => $form,
            'calendar' => $calendar,
            'event' => $event,
            'modifying' => $modifying,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /**
     * The calendar's categories that aren't limited to a role the user isn't in
     *
     * @return Category[]
     */
    private function visibleCategories(Calendar $calendar, ?User $user, bool $isAdmin): array
    {
        return array_values(array_filter(
            $calendar->getCategories()->toArray(),
            fn(Category $category) => $isAdmin || $category->getRole() === null || $category->getRole()->hasUser($user),
        ));
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
