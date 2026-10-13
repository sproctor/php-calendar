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
namespace App\Form;

use App\Entity\Calendar;
use App\Entity\View;
use App\Repository\ViewRepository;
use App\Service\CalendarSlugger;
use App\Service\LocaleService;
use App\View\AllCalendarsView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * A view's settings and calendars. Each calendar has an "in this view" checkbox (calendar_{cid}), and
 * optionally a color marking its events (colored_{cid} and color_{cid}); calendars() reads them back.
 */
class ViewFormType extends AbstractType
{
    public function __construct(
        private CalendarSlugger $slugger,
        private ViewRepository $viewRepository,
        private LocaleService $localeService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var View $view */
        $view = $options['data'];
        $builder
            ->add('title', TextType::class, ['label' => new TranslatableMessage('title-label')])
            ->add('slug', TextType::class, [
                'label' => new TranslatableMessage('slug-label'),
                'help' => new TranslatableMessage('view-slug-help'),
                'required' => false,
                'attr' => ['maxlength' => Calendar::SLUG_MAX_LENGTH, 'pattern' => Calendar::SLUG_PATTERN],
            ]);
        foreach (array_values($options['calendars']) as $i => $calendar) {
            $color = $view->getCalendarColor($calendar);
            $builder
                ->add('calendar_' . $calendar->getCid(), CheckboxType::class, [
                    'label' => $calendar->getTitle(),
                    'translation_domain' => false,
                    'required' => false,
                    'mapped' => false,
                    'data' => $view->hasCalendar($calendar),
                ])
                ->add('colored_' . $calendar->getCid(), CheckboxType::class, [
                    'label' => new TranslatableMessage('mark-with-color-label'),
                    'required' => false,
                    'mapped' => false,
                    'data' => $color !== null,
                ])
                ->add('color_' . $calendar->getCid(), ColorType::class, [
                    'label' => new TranslatableMessage('calendar-color-label', ['%calendar%' => $calendar->getTitle()]),
                    'mapped' => false,
                    'data' => $color ?? AllCalendarsView::PALETTE[$i % count(AllCalendarsView::PALETTE)],
                ]);
        }
        $builder
            ->add('timezone', TimezoneType::class, [
                'label' => new TranslatableMessage('default-timezone-label'),
                'help' => new TranslatableMessage('view-timezone-help'),
            ])
            ->add('locale', ChoiceType::class, [
                'label' => new TranslatableMessage('default-language-label'),
                'help' => new TranslatableMessage('default-language-help'),
                'required' => false,
                'placeholder' => new TranslatableMessage('browser-language-option'),
                'choices' => array_flip($this->localeService->getLocaleMappings()),
                'choice_translation_domain' => false,
            ])
            ->add('theme', ThemeChoiceType::class, [
                'label' => new TranslatableMessage('theme-label'),
                'site_default' => true,
            ])
            ->add('events_max', IntegerType::class, [
                'label' => new TranslatableMessage('max-events-display-label'),
                'attr' => ['min' => 1, 'max' => 255],
            ])
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) use ($view): void {
                // An empty URL name is made from the title
                $data = $event->getData();
                if (is_array($data) && trim((string) ($data['slug'] ?? '')) === '') {
                    $data['slug'] = $this->slugger->uniqueSlug((string) ($data['title'] ?? ''), function (string $slug) use ($view) {
                        $other = $this->viewRepository->findOneBy(['slug' => $slug]);

                        return $slug === View::ALL || ($other !== null && $other !== $view);
                    });
                    $event->setData($data);
                }
            });
    }

    /**
     * The calendars ticked on a submitted form, with their colors, for View::setCalendars()
     *
     * @param Calendar[] $calendars every calendar, as given to the form
     * @return array<int, array{0: Calendar, 1: ?string}>
     */
    public static function calendars(FormInterface $form, array $calendars): array
    {
        $chosen = [];
        foreach ($calendars as $calendar) {
            if ($form->get('calendar_' . $calendar->getCid())->getData()) {
                $colored = $form->get('colored_' . $calendar->getCid())->getData();
                $chosen[] = [$calendar, $colored ? (string) $form->get('color_' . $calendar->getCid())->getData() : null];
            }
        }

        return $chosen;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => View::class,
            'calendars' => [],
        ]);
    }
}
