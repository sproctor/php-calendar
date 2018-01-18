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

namespace PhpCalendar;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Response;

class SearchPage extends Page
{
    /**
     * Full display for a month
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        /*$form = $this->createSearchForm($context);
        $form->handleRequest($context->request);
        if ($form->isSubmitted() && $form->isValid()) {
            $results = $this->processForm($context, $form->getData());*/
        if ($context->request->get('query') != null) {
            $results = $context->db->search($context->calendar->getCid(), $context->request->get('query'));
        } else {
            $results = null;
        }
        
        // else
        return new Response($context->twig->render(
            "search.html.twig",
            [/*'form' => $form->createView(),*/ 'results' => $results]
        ));
    }

    private function createSearchForm(Context $context)
    {
        $builder = $context->getFormFactory()->createBuilder();
        $builder
            ->add('query', SearchType::class)
            ->add('start', DateType::class, ['label' => __('from-label'), 'required' => false, 'widget' => 'single_text'])
            ->add('end', DateType::class, ['label' => __('to-label'), 'required' => false, 'widget' => 'single_text'])
            ->add(
                'sort_by',
                ChoiceType::class,
                [
                    'label' => __('sort-by-label'),
                    'choices' => [__('start-date-label') => 'start', __('subject-label') => 'subject']
                ]
            )
            ->add(
                'order',
                ChoiceType::class,
                [
                    'label' => __('order-label'),
                    'choices' => [__('ascending-label') => 'ASC', __('descending-label') => 'DESC']
                ]
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) {
                    $form = $event->getForm();
                    $data = $form->getData();
                    if (!empty($data['start']) && !empty($data['end'])) {
                        if ($data['end']->getTimestamp() < $data['start']->getTimestamp()) {
                            $form->get('end')->addError(
                                new FormError(__('end-before-start-date-time-error'))
                            );
                        }
                    }
                }
            );

            return $builder->getForm();
    }

    private function processForm(Context $context, $data)
    {
        $occurrences = $context->db->search(
            $context->calendar->getCid(),
            $$data['query'],
            $data['start'],
            $data['end'],
            $data['sort_by'],
            $data['order']
        );

        $results = array();
        foreach ($occurrences as $occurrence) {
            if (!$occurrence->canRead($context->user)) {
                continue;
            }

            $results[] = [
                'name' => $occurrence->getAuthor(),
                'subject' => $occurrence->getSubject(),
                'description' => $occurrence->getDescription(),
                'when' => $occurrence->getDatetimeString(),
                'eid' => $occurrence->getEid()
            ];
        }

        return $results;
    }
}
