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

namespace App;

use App\Exception\NoCalendarsException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use App\Entity\Calendar;
use App\Entity\User;
use Locale;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Context
{
    private Calendar $calendar;
    public User $user;
    private FormFactoryInterface $formFactory;
    public Request $request;
    public Session $session;
    private RequestStack $requestStack;
    public EntityManager $entityManager;
    public Translator $translator;
    private Environment $twig;

    /**
     * Context constructor.
     *
     * @param RequestStack $requestStack
     * @param EntityManager $entityManager
     *
     * @throws Exception
     */
    public function __construct(
        RequestStack           $requestStack,
        EntityManagerInterface $entityManager,
        Environment            $twig
    )
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $requestStack->getSession();

        $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
        $vendorTwigBridgeDir = dirname($appVariableReflection->getFileName());
        $this->twig = $twig;

        /* Replace this with Doctrine Migrations
        include_once __DIR__ . '/schema.php';
        if ($this->db->getConfig('version') < PHPC_DB_VERSION) {
            return;
        }

        if ($this->db->getConfig('version') > PHPC_DB_VERSION) {
            throw new InvalidConfigException();
        }
        */

        // Validate user
        $this->readLoginToken();
        if (!isset($this->user)) {
            $this->user = User::createAnonymous($this);
        }

        $this->initTimezone();
        $this->initLocale();

        $this->initTwig();
    }

    private function initTwig()
    {
        $csrfGenerator = new UriSafeTokenGenerator();
        $csrfStorage = new SessionTokenStorage($this->requestStack);
        $csrfManager = new CsrfTokenManager($csrfGenerator, $csrfStorage);

//        $formTheme = 'bootstrap_4_layout.html.twig';
//        $formEngine = new TwigRendererEngine(array($formTheme), $this->twig);
//        $this->twig->addRuntimeLoader(
//            new \Twig_FactoryRuntimeLoader(
//                array(
//                    FormRenderer::class => function () use ($formEngine, $csrfManager) {
//                        return new FormRenderer($formEngine, $csrfManager);
//                    },
//                )
//            )
//        );
//        $this->twig->addExtension(new TranslationExtension($this->translator));
//        $this->twig->addExtension(new FormExtension());
//        $this->twig->addExtension(new \Twig_Extension_Debug());
//        $this->twig->addExtension(new \Twig_Extensions_Extension_Intl());

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new CsrfExtension($csrfManager))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();

        $this->twig->addGlobal('context', $this);
        $this->twig->addGlobal('locale', \Locale::getDefault());
        if ($this->entityManager != null) {
            $this->twig->addGlobal('calendar', $this->getCalendar());
//            $this->twig->addGlobal('calendars', $this->findAllCalendars());
            $this->twig->addGlobal('user', $this->user);
        }
        $this->twig->addGlobal('script', $this->request->getScriptName());
        $this->twig->addGlobal('embed', $this->request->get("content") == "embed");
//        $this->twig->addGlobal('messages', $this->getMessages());
        //'theme' => $context->getCalendar()->get_theme(),
        $this->twig->addGlobal('minified', defined('PHPC_DEBUG') ? '' : '.min');
        $this->twig->addGlobal('query_string', $this->request->getQueryString());
//        $this->twig->addGlobal('languages', get_language_mappings());

        $this->twig->addFunction(new TwigFunction(
            'dropdown',
            '\PhpCalendar\create_dropdown',
            array('is_safe' => array('html'))
        ));

        $this->twig->addFilter(new TwigFilter('day_name', '\PhpCalendar\day_name'));
        $this->twig->addFilter(new TwigFilter('day_abbr', '\PhpCalendar\short_day_name'));
        $this->twig->addFilter(new TwigFilter('month_name', '\PhpCalendar\month_name'));
        $this->twig->addFilter(new TwigFilter('month_abbr', '\PhpCalendar\short_month_name'));
        $this->twig->addFilter(new TwigFilter('date_index', '\PhpCalendar\date_index'));
        $this->twig->addFilter(
            new TwigFilter(
                'week_link',
                function (\DateTimeInterface $date) {
                    $week = week_of_year($date);
                    $year = year_of_week_of_year($date);
                    $url = $this->createUrl('display_week', ['week' => $week, 'year' => $year]);
                    return "<a href=\"$url\">$week</a>";
                },
                array('is_safe' => array('html'))
            )
        );
        $this->twig->addFunction(
            new TwigFunction(
                'add_days',
                function (\DateTimeInterface $date, $days) {
                    $next_date = new \DateTime('@' . $date->getTimestamp());
                    return $next_date->add(new \DateInterval("P{$days}D"));
                }
            )
        );
        $this->twig->addFunction(
            new TwigFunction(
                'is_date_in_month',
                function (Context $context, \DateTimeInterface $date) {
                    return $context->getAction() == 'display_month'
                        && $date->format('m') == $context->getMonth()
                        && $date->format('Y') == $context->getYear();
                }
            )
        );
        $this->twig->addFunction(new TwigFunction('is_today', '\PhpCalendar\is_today'));
        $this->twig->addFunction(new TwigFunction('append_parameter_url', '\PhpCalendar\append_parameter_url'));
        $this->twig->addFunction(
            new TwigFunction(
                'day',
                function (\DateTimeInterface $date) {
                    return $date->format('j');
                }
            )
        );
        $this->twig->addFunction(
            new TwigFunction(
                'month',
                function (\DateTimeInterface $date) {
                    return $date->format('n');
                }
            )
        );
        $this->twig->addFunction(
            new TwigFunction(
                'can_write',
                function (User $user, Calendar $calendar) {
                    return $calendar->canWrite($user);
                }
            )
        );
        $this->twig->addFunction(
            new TwigFunction(
                'occurrences_for_date',
                function ($occurrences, \DateTimeInterface $date) {
                    $key = date_index($date);
                    if (array_key_exists($key, $occurrences)) {
                        return $occurrences[date_index($date)];
                    }
                    return null;
                }
            )
        );
        $this->twig->addFunction(new TwigFunction(
            'menu_item',
            '\PhpCalendar\menu_item',
            array('is_safe' => array('html'))
        ));
    }

    /**
     * @return string
     */
    public function getAction(): mixed
    {
        return $this->request->get('action', 'display_month');
    }

    /**
     * @param string $key
     * @return string
     */
    public function getConfig(string $key)
    {
        $config = $this->entityManager->find('App:Config', $key);

        return empty($config) ? null : $config->getValue();
    }

    /**
     * @return ?Calendar
     * @throws Exception
     */
    public function getCalendar(): ?Calendar
    {
        if (isset($this->calendar)) {
            return $this->calendar;
        }

        $phpcid = $this->request->get('phpcid');
        if (isset($phpcid)) {
            if (!is_numeric($phpcid)) {
                throw new InvalidInputException(__('invalid-calendar-id-error'));
            }
            $this->calendar = $this->findCalendar($phpcid);
            return $this->calendar;
        }

        $eid = $this->request->get('eid');
        if (isset($eid)) {
            if (is_array($eid)) {
                $eid = $eid[0];
            }
            $event = $this->findEvent($eid);
            if ($event != null) {
                $this->calendar = $event->getCalendar();
                return $this->calendar;
            }
        }

        $default_calendar = $this->user->defaultCalendar();
        if (!empty($default_calendar)) {
            $this->calendar = $default_calendar;
            return $this->calendar;
        }

        return null;
//        $this->calendar = $this->getDefaultCalendar();
//        return $this->calendar;
    }

    private function initTimezone()
    {
        // Set timezone
        $tz = $this->user->getTimezone();
        if (empty($tz) && isset($this->calendar)) {
            $tz = $this->calendar->getTimezone();
        }

        if (!empty($tz)) {
            date_default_timezone_set($tz);
        }
    }

    public function findOccurrences(\DateTimeInterface $from, \DateTimeInterface $to)
    {
        $qb = $this->entityManager->createQueryBuilder();

        //$qb->select('e')
        //->from('Event', 'e')
        //->where('e.
    }

    private function initLocale()
    {
        $request = $this->requestStack->getCurrentRequest();

        // setup translation stuff
        $locale = $this->user->getLocale();
        if (empty($locale)) {
            $locale = $this->getCalendar()?->getLocale();
            if (empty($locale)) {
                // TODO search through valid locales for a match
                $locale = $request->getLocale();
                if (empty($locale)) {
                    $locale = 'en';
                }
            }
        }

        // default to 'en' on invalid locale
        if (1 !== preg_match('/^[a-z0-9@_\\.\\-]*$/i', $locale)) {
            $locale = 'en';
        }

        Locale::setDefault($locale);

        $this->translator = new Translator($locale);
        $this->translator->addLoader('mo', new MoFileLoader());
        if ($locale != 'en') {
            $this->addLocale($locale);
        }
        $this->addLocale('en');
        $this->translator->setFallbackLocales(array('en'));
    }

    /**
     * Loads a new locale
     *
     * @param string $locale
     */
    private function addLocale($locale)
    {
        try {
            $this->translator->addResource('mo', __DIR__ . "/../translations/$locale.mo", $locale);
        } catch (NotFoundResourceException $e) {
            $this->addMessage("Could not find a translation for locale \"$locale\".");
        }
    }

    /**
     * @return FormFactoryInterface
     */
    public function getFormFactory()
    {
        return $this->formFactory;
    }

    /**
     * @return int
     */
    private function readLoginToken()
    {
        if (isset($_COOKIE["identity"])) {
            try {
                $decoded = JWT::decode($_COOKIE["identity"], $this->getConfig("token_key"), array('HS256'));
                $decoded_array = (array)$decoded;
                $data = (array)$decoded_array["data"];

                return $data["uid"];
            } catch (SignatureInvalidException $e) {
                // TODO: log this event
                setcookie('identity', "", time() - 3600);
            }
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function loginUser($username, $password)
    {
        $user = $this->db->getUserByName($username);
        //echo "<pre>"; var_dump($user); echo "</pre>";
        if (!$user) {
            return false;
        }

        $password_hash = $user->getPasswordHash();

        // migrate old passwords
        if ($password_hash[0] != '$' && md5($password) == $password_hash) {
            $this->db->setPassword($user->getUid(), $password);
        } else { // otherwise use the normal password verifier
            if (!password_verify($password, $password_hash)) {
                return false;
            }
        }

        $this->user = $user;
        $this->setLoginToken($user);

        return true;
    }

    /**
     * @param User $user
     */
    private function setLoginToken(User $user)
    {
        $issuedAt = time();
        // expire credentials in 30 days.
        $expires = $issuedAt + 30 * 24 * 60 * 60;
        $protocol = $this->request->isSecure() ? 'https' : 'http';
        $token = array(
            "iss" => $protocol . "://" . $this->request->getHost(),
            "iat" => $issuedAt,
            "exp" => $expires,
            "data" => array("uid" => $user->getUid())
        );
        $jwt = JWT::encode($token, $this->getConfig('token_key'));

        // TODO: Add a remember me checkbox to the login form, and have the
        //    cookies expire at the end of the session if it's not checked

        setcookie('identity', $jwt, $expires);
    }

    public function getPage()
    {
        /* FIXME: update this section for doctrine
        if ($this->getConfig('version') < PHPC_DB_VERSION) {
            return new UpdatePage;
        }
        */
        switch ($this->getAction()) {
            case 'event_form':
                return new EventFormPage;
            case 'display_event':
                return new EventPage;
            case 'display_month':
                return new MonthPage;
            case 'display_day':
                return new DayPage;
            case 'display_week':
                return new WeekPage;
            case 'login':
                return new LoginPage;
            case 'logout':
                return new LogoutPage;
            case 'event_delete':
                return new EventDeletePage;
            case 'admin':
                return new AdminPage;
            case 'calendar_create':
                return new CreateCalendarPage;
            case 'calendar_delete':
                return new CalendarDeletePage;
            case 'default_calendar':
                return new DefaultCalendarPage;
            case 'create_user':
                return new CreateUserPage;
            case 'update':
                return new UpdatePage;
            case 'search':
                return new SearchPage;
            default:
                throw new InvalidInputException(__('invalid-action-error'));
        }
    }

    /**
     * @param string $action
     * @param \DateTimeInterface|null $date
     * @return string
     */
    public function createDateUrl($action, \DateTimeInterface $date = null)
    {
        if ($date == null) {
            $date = $this->getDate();
        }
        return $this->createUrl(
            $action,
            ['year' => $date->format('Y'), 'month' => $date->format('n'), 'day' => $date->format('j')]
        );
    }

    /**
     * @param string $action
     * @param string $eid
     * @return string
     */
    public function createEventUrl($action, $eid)
    {
        return $this->createUrl($action, array("eid" => $eid));
    }

    /**
     * @param string $action
     * @param string $oid
     * @return string
     */
    public function createOccurrenceUrl($action, $oid)
    {
        return $this->createUrl($action, array("oid" => $oid));
    }

    /**
     * @param string|null $action
     * @param string[] $parameters
     * @param string|null $hash
     * @return string
     */
    public function createUrl($action = null, $parameters = array(), $hash = null)
    {
        if (!empty($this->calendar)) {
            $parameters['phpcid'] = $this->calendar->getCid();
        }
        $url = $this->request->getScriptName();
        $first = true;
        if ($action !== null) {
            $url .= "?action={$action}";
            $first = false;
        }
        foreach ($parameters as $key => $value) {
            $url .= ($first ? '?' : '&') . "$key=$value";
            $first = false;
        }
        if ($hash !== null) {
            $url .= '#' . $hash;
        }
        return $url;
    }

    public function persist($entity)
    {
        $this->entityManager->persist($entity);
    }

    public function flush()
    {
        $this->entityManager->flush();
    }
}
