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

namespace App\Twig;

use App\Repository\CalendarRepository;
use App\Security\Voter\CalendarVoter;
use App\Service\LocaleService;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private CalendarRepository    $calendarRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
        private LocaleService         $languageService,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'menu_item',
                function (array $context, string $name, string $text, ?string $icon = null, array $options = []): string
                {
                    $app = $context['app'];
                    $request = $app->getRequest();
                    $active_route = $request->get('_route');
                    $active = $active_route === $name ? " active" : "";
                    $text = htmlspecialchars($text);
                    if ($icon != null) {
                        $text = '<i class="bi-' . htmlspecialchars($icon) . "\"></i> $text";
                    }
                    $url = htmlspecialchars($this->router->generate($name, $options));
                    return "<li class=\"nav-item\"><a class=\"nav-link$active\" href=\"$url\">$text</a></li>";
                },
                [
                    'needs_context' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'dropdown',
                [$this, 'createDropdown'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'in_same_month',
                fn(array $context, DateTimeInterface $date1, DateTimeInterface $date2) =>
                    $date1->format('n') === $date2->format('n')
                    && $date1->format('Y') === $date2->format('Y'),
                ['needs_context' => true]
            ),
            new TwigFunction(
                'add_days',
                fn(\DateTimeInterface $date, int $days) =>
                    \DateTimeImmutable::createFromInterface($date)->modify("$days days")
            ),
            new TwigFunction('is_today', '\is_today'),
            new TwigFunction(
                'change_locale',
                function (array $context, string $locale) {
                    $app = $context['app'];
                    /* @var Request $request */
                    $request = $app->getRequest();
                    $uri = $request->getRequestUri();
                    // replaces _locale in "/{_locale}/rest/of/path" with $locale
                    return "/$locale" . preg_replace('%^/([^/]+)%', '', $uri);
                },
                ['needs_context' => true]
            ),
            new TwigFunction(
                'get_calendars',
                fn() => array_values(array_filter(
                    $this->calendarRepository->findBy([], ['title' => 'ASC']),
                    fn($calendar) => $this->authorizationChecker->isGranted(CalendarVoter::READ, $calendar),
                )),
            ),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'month_name',
                [$this, 'monthName']
            ),
            new TwigFilter(
                'month_abbr',
                [$this, 'shortMonthName']
            ),
            new TwigFilter(
                'day_name',
                [$this, 'dayName']
            ),
            new TwigFilter(
                'day_abbr',
                [$this, 'shortDayName']
            ),
            new TwigFilter(
                'year',
                fn(DateTimeInterface $datetime): int => intval($datetime->format('Y'))
            ),
            new TwigFilter(
                'month',
                fn(DateTimeInterface $datetime): int => intval($datetime->format('m'))
            ),
            new TwigFilter(
                'day',
                fn(\DateTimeInterface $date) => intval($date->format('j'))
            ),
            new TwigFilter('week', fn(DateTimeInterface $date): int => \week_of_year($date)),
            new TwigFilter('week_year', fn(DateTimeInterface $date): int => \year_of_week_of_year($date)),
            new TwigFilter('date_index', fn(DateTimeInterface $date): string => \date_index($date)),
            new TwigFilter(
                'dateparts',
                function (DateTimeInterface $datetime): array {
                    return [
                        'year' => $datetime->format('Y'),
                        'month' => $datetime->format('m'),
                        'day' => $datetime->format('d'),
                    ];
                }
            )
        ];
    }

    public function getGlobals(): array
    {
        return ['languages' => $this->languageService->getLocaleMappings()];
    }

    /**
     * @param string[] $values Array of URL => title
     * @return string          Dropdown box that will change the page to the URL from $values when an element is selected
     */
    function createDropdown(string $title, array $values): string
    {
        // TODO: make this a template
        $output = "<div class=\"dropdown\">"
            . "<button class=\"btn btn-navlink dropdown-toggle\" data-bs-toggle=\"dropdown\" type=\"button\""
            . " aria-expanded=\"false\">" . htmlspecialchars($title) . "</button>"
            . "<ul class=\"dropdown-menu\">";
        foreach ($values as $key => $value) {
            $output .= '<li><a class="dropdown-item" href="' . htmlspecialchars($value) . '">'
                . htmlspecialchars((string) $key) . '</a></li>';
        }
        return $output . "</ul></div>";
    }

    /**
     * Takes a date, returns the full month name
     */
    function monthName(DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            "MMMM" // full month format
        );
        return $formatter->format($date);
    }

    function shortDayName(DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            "E"
        );
        return $formatter->format($date);
    }

    function dayName(DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            "EEEE"
        );
        return $formatter->format($date);
    }

    /**
     * @return string
     */
    function shortMonthName(DateTimeInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            "MMM" // short month format
        );
        return $formatter->format($date);
    }
}