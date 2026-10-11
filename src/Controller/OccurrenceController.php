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

use App\Entity\Event;
use App\Entity\Occurrence;
use App\Form\OccurrenceTimesType;
use App\Security\Voter\EventVoter;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Adds, changes and removes single occurrences of an event
 */
#[Route('/{_locale}')]
class OccurrenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/event/{eid}/occurrence/new', name: 'create_occurrence', requirements: ['eid' => '\d+'], methods: ['GET', 'POST'])]
    public function create(#[MapEntity(id: 'eid')] Event $event, Request $request): Response
    {
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        $last = $event->getOccurrences()->last();
        $defaults = $last
            ? OccurrenceTimesType::fromOccurrence($last)
            : OccurrenceTimesType::defaults(new DateTimeImmutable('today'));

        return $this->occurrenceForm($request, $event, null, $defaults);
    }

    #[Route('/occurrence/{oid}/edit', name: 'edit_occurrence', requirements: ['oid' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(#[MapEntity(id: 'oid')] Occurrence $occurrence, Request $request): Response
    {
        $event = $occurrence->getEvent();
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);

        return $this->occurrenceForm($request, $event, $occurrence, OccurrenceTimesType::fromOccurrence($occurrence));
    }

    #[Route('/occurrence/{oid}/delete', name: 'delete_occurrence', requirements: ['oid' => '\d+'], methods: ['POST'])]
    public function delete(#[MapEntity(id: 'oid')] Occurrence $occurrence, Request $request): Response
    {
        $event = $occurrence->getEvent();
        $this->denyAccessUnlessGranted(EventVoter::EDIT, $event);
        if (!$this->isCsrfTokenValid('delete-occurrence-' . $occurrence->getOid(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $event->removeOccurrence($occurrence);
        // An event without occurrences can't be seen anywhere, so remove it with its last occurrence
        if ($event->getOccurrences()->isEmpty()) {
            $slug = $event->getCalendar()->getSlug();
            $this->entityManager->remove($event);
            $this->entityManager->flush();
            $this->addFlash('success', 'event-deleted-message');

            return $this->redirectToRoute('default_view', ['slug' => $slug]);
        }
        $event->markModified();
        $this->entityManager->flush();
        $this->addFlash('success', 'occurrence-removed-message');

        return $this->redirectToRoute('event_view', ['eid' => $event->getEid()]);
    }

    private function occurrenceForm(Request $request, Event $event, ?Occurrence $occurrence, array $times): Response
    {
        $form = $this->createFormBuilder(['times' => $times])
            ->add('times', OccurrenceTimesType::class)
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            [$start, $end, $timeType] = OccurrenceTimesType::toTimes($form->get('times')->getData());
            if ($occurrence === null) {
                new Occurrence($event, $start, $end, $timeType);
            } else {
                $occurrence->setTimes($start, $end, $timeType);
            }
            $event->markModified();
            $this->entityManager->flush();
            $this->addFlash('success', 'occurrence-saved-message');

            return $this->redirectToRoute('event_view', ['eid' => $event->getEid()]);
        }

        return $this->render('occurrence/form.html.twig', [
            'form' => $form,
            'event' => $event,
            'calendar' => $event->getCalendar(),
            'occurrence' => $occurrence,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
