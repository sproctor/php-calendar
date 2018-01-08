<?php
/*
 * Copyright 2017 Sean Proctor
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

use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Validator\Validation;

class Context
{
    /**
     * @var Calendar $calendar
     */
    public $calendar;
    /**
     * @var User $user
     */
    public $user;
    public $session;
    private $formFactory;
    /**
     * @var Request $request
     */
    public $request;
    public $config;
    /**
     * @var Database $db
     */
    public $db;
    public $translator;
    /**
     * @var  \Twig_Environment
     */
    public $twig;

    /**
     * Context constructor.
     */
    public function __construct(Request $request)
    {

        //ini_set('arg_separator.output', '&amp;');
        //mb_internal_encoding('UTF-8');
        //mb_http_output('pass');

        if (defined('PHPC_DEBUG')) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('html_errors', 1);
        }

        $this->request = $request;

        $this->session = new Session();
        $this->session->start();

        $this->config = $this->loadConfig(PHPC_CONFIG_FILE);
        $this->db = new Database($this->config);

        $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
        $vendorTwigBridgeDir = dirname($appVariableReflection->getFileName());
        $this->twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(
                array(
                realpath(__DIR__ . '/../templates'),
                $vendorTwigBridgeDir.'/Resources/views/Form')
            ),
            array(
                //'cache' => __DIR__ . '/cache',
                'debug' => true
            )
        );

        include_once __DIR__ . '/schema.php';
        if ($this->db->getConfig('version') < PHPC_DB_VERSION) {
            return;
        }

        if ($this->db->getConfig('version') > PHPC_DB_VERSION) {
            throw new InvalidConfigException();
        }

        // Validate user
        $this->readLoginToken();
        if (!isset($this->user)) {
            $this->user = User::createAnonymous($this);
        }

        $this->initCurrentCalendar();

        $this->initTimezone();
        $this->initLocale($request);

        $this->initTwig();
    }

    /**
     * @param string $filename
     * @return string[]
     */
    private function loadConfig($filename)
    {
        // Run the installer if we have no config file
        // This doesn't work when embedded from outside
        if (!file_exists($filename)) {
            throw new InvalidConfigException();
        }
        $config = include $filename;

        if (!isset($config["sql_host"])) {
            throw new InvalidConfigException();
        }

        return $config;
    }

    private function initTwig()
    {
        $csrfGenerator = new UriSafeTokenGenerator();
        $csrfStorage = new SessionTokenStorage($this->session);
        $csrfManager = new CsrfTokenManager($csrfGenerator, $csrfStorage);

        $formTheme = 'bootstrap_4_layout.html.twig';
        $formEngine = new TwigRendererEngine(array($formTheme), $this->twig);
        $this->twig->addRuntimeLoader(
            new \Twig_FactoryRuntimeLoader(
                array(
                FormRenderer::class => function () use ($formEngine, $csrfManager) {
                    return new FormRenderer($formEngine, $csrfManager);
                },
                )
            )
        );
        $this->twig->addExtension(new TranslationExtension($this->translator));
        $this->twig->addExtension(new FormExtension());
        $this->twig->addExtension(new \Twig_Extension_Debug());
        $this->twig->addExtension(new \Twig_Extensions_Extension_Intl());

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new CsrfExtension($csrfManager))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
        
        $this->twig->addGlobal('context', $this);
        $this->twig->addGlobal('locale', \Locale::getDefault());
        $this->twig->addGlobal('calendar', $this->calendar);
        $this->twig->addGlobal('calendars', $this->db->getCalendars());
        $this->twig->addGlobal('user', $this->user);
        $this->twig->addGlobal('script', $this->request->getScriptName());
        $this->twig->addGlobal('embed', $this->request->get("content") == "embed");
        $this->twig->addGlobal('messages', $this->getMessages());
        //'theme' => $context->getCalendar()->get_theme(),
        $this->twig->addGlobal('minified', defined('PHPC_DEBUG') ? '' : '.min');
        $this->twig->addGlobal('query_string', $this->request->getQueryString());
        $this->twig->addGlobal('languages', get_language_mappings());

        $this->twig->addFunction(new \Twig_SimpleFunction(
            'dropdown',
            '\PhpCalendar\create_dropdown',
            array('is_safe' => array('html'))
        ));
        
        $this->twig->addFilter(new \Twig_SimpleFilter('day_name', '\PhpCalendar\day_name'));
        $this->twig->addFilter(new \Twig_SimpleFilter('day_abbr', '\PhpCalendar\short_day_name'));
        $this->twig->addFilter(new \Twig_SimpleFilter('month_name', '\PhpCalendar\month_name'));
        $this->twig->addFilter(new \Twig_SimpleFilter('month_abbr', '\PhpCalendar\short_month_name'));
        $this->twig->addFilter(new \Twig_SimpleFilter('date_index', '\PhpCalendar\date_index'));
        $this->twig->addFilter(
            new \Twig_SimpleFilter(
                'week_link',
                function (\DateTimeInterface $date, Context $context) {
                    $week = week_of_year($date);
                    $year = year_of_week_of_year($date);
                    $url = $this->createUrl('display_week', ['week' => $week, 'year' => $year]);
                    return "<a href=\"$url\">$week</a>";
                },
                array('is_safe' => array('html'))
            )
        );
        $this->twig->addFunction(
            new \Twig_SimpleFunction(
                'add_days',
                function (\DateTimeInterface $date, $days) {
                    $next_date = new \DateTime('@'.$date->getTimestamp());
                    return $next_date->add(new \DateInterval("P{$days}D"));
                }
            )
        );
        $this->twig->addFunction(
            new \Twig_SimpleFunction(
                'is_date_in_month',
                function (Context $context, \DateTimeInterface $date) {
                    $currentDate = new \DateTime();
                    return $context->getAction() == 'display_month'
                    && $date->format('m') == $context->getMonth()
                    && $date->format('Y') == $context->getYear();
                }
            )
        );
        $this->twig->addFunction(new \Twig_SimpleFunction('is_today', '\PhpCalendar\is_today'));
        $this->twig->addFunction(new \Twig_SimpleFunction('append_parameter_url', '\PhpCalendar\append_parameter_url'));
        $this->twig->addFunction(
            new \Twig_SimpleFunction(
                'day',
                function (\DateTimeInterface $date) {
                    return $date->format('j');
                }
            )
        );
        $this->twig->addFunction(
            new \Twig_SimpleFunction(
                'month',
                function (\DateTimeInterface $date) {
                    return $date->format('n');
                }
            )
        );
        $this->twig->addFunction(
            new \Twig_SimpleFunction(
                'can_write',
                function (User $user, Calendar $calendar) {
                    return $calendar->canWrite($user);
                }
            )
        );
        $this->twig->addFunction(
            new \Twig_SimpleFunction(
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
        $this->twig->addFunction(new \Twig_SimpleFunction(
            'menu_item',
            '\PhpCalendar\menu_item',
            array('is_safe' => array('html'))
        ));
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->request->get('action', 'display_month');
    }

    private function initCurrentCalendar()
    {
        // Find current calendar
        $current_cid = $this->getCurrentCid();

        $this->calendar = $this->db->getCalendar($current_cid);
    }

    /**
     * @return int
     * @throws \Exception
     */
    private function getCurrentCid()
    {
        $phpcid = $this->request->get('phpcid');
        if (isset($phpcid)) {
            if (!is_numeric($phpcid)) {
                throw new InvalidInputException(__('invalid-calendar-id-error'));
            }
            return $phpcid;
        }
        
        $eid = $this->request->get('eid');
        if (isset($eid)) {
            if (is_array($eid)) {
                $eid = $eid[0];
            }
            $event = $this->db->getEvent($eid);
            if ($event != null) {
                return $event->getCalendar()->getCid();
            }
        }
        
        $calendars = $this->db->getCalendars();
        if (empty($calendars)) {
            throw new \Exception("There are no calendars.");
            // TODO: create a page to fix this
        } else {
            if ($this->user->defaultCid() !== false) {
                $default_cid = $this->user->defaultCid();
            } else {
                $default_cid = $this->db->getConfig('default_cid');
            }
            if (!empty($calendars[$default_cid])) {
                return $default_cid;
            } else {
                return reset($calendars)->getCid();
            }
        }
    }

    private function initTimezone()
    {
        // Set timezone
        $tz = $this->user->getTimezone();
        if (empty($tz)) {
            $tz = $this->calendar->getTimezone();
        }

        if (!empty($tz)) {
            date_default_timezone_set($tz);
        }
    }

    /**
     * @param string $message
     */
    public function addMessage($message)
    {
        $this->session->getFlashBag()->add('message', $message);
    }

    /**
     * @return string[]
     */
    public function getMessages()
    {
        return $this->session->getFlashBag()->get('message');
    }

    /**
     * @return int
     */
    public function getYear()
    {
        $year = $this->request->get('year');
        if (is_numeric($year)) {
            return $year;
        } else {
            return date('Y');
        }
    }

    /**
     * @return int
     */
    public function getMonth()
    {
        $month = $this->request->get('month');
        if (is_numeric($month) && $month > 0 && $month <= 12) {
            return $month;
        } else {
            return date('n');
        }
    }

    /**
     * @return int
     */
    public function getDay()
    {
        $day = $this->request->get('day');
        if (is_numeric($day) && $day > 0 && $day <= days_in_month($this->getMonth(), $this->getYear())) {
            return $day;
        } else {
            if ($this->getMonth() == date('n') && $this->getYear() == date('Y')) {
                return date('j');
            } else {
                return 1;
            }
        }
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return create_datetime($this->getMonth(), $this->getDay(), $this->getYear());
    }

    private function initLocale(Request $request)
    {
        // setup translation stuff
        $lang = $this->user->getLocale();
        if (empty($lang)) {
            $lang = $this->calendar->getLocale();
            if (empty($lang)) {
                $lang = substr($request->getLocale(), 0, 2);
                if (empty($lang)) {
                    $lang = 'en';
                }
            }
        }

        // Require a 2 letter language
        if (!preg_match('/^\w+$/', $lang, $matches)) {
            $lang = 'en';
        }

        \Locale::setDefault($lang);

        $this->translator = new Translator($lang, new MessageSelector());
        $this->translator->addLoader('mo', new MoFileLoader());
        if ($lang != 'en') {
            $this->translator->addResource('mo', __DIR__ . "/../translations/$lang.mo", $lang);
        }
        $this->translator->addResource('mo', __DIR__ . "/../translations/en.mo", "en");
        $this->translator->setFallbackLocales(array('en'));
    }

    public function getFormFactory()
    {
        return $this->formFactory;
    }

    private function readLoginToken()
    {
        if (isset($_COOKIE["identity"])) {
            try {
                $decoded = \Firebase\JWT\JWT::decode($_COOKIE["identity"], $this->config["token_key"], array('HS256'));
                $decoded_array = (array) $decoded;
                $data = (array) $decoded_array["data"];

                $uid = $data["uid"];
                $user = $this->db->getUser($uid);
                $this->user = $user;
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
        $jwt = \Firebase\JWT\JWT::encode($token, $this->config['token_key']);

        // TODO: Add a remember me checkbox to the login form, and have the
        //    cookies expire at the end of the session if it's not checked

        setcookie('identity', $jwt, $expires);
    }

    public function getPage()
    {
        if ($this->db->getConfig('version') < PHPC_DB_VERSION) {
            return new UpdatePage;
        }
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
            case 'update':
                return new UpdatePage;
            default:
                throw new InvalidInputException(__('invalid-action-error'));
        }
    }

    /**
     * @param string             $action
     * @param \DateTimeInterface $date
     * @return string
     */
    public function createDateUrl($action, \DateTimeInterface $date)
    {
        return $this->createUrl(
            $action,
            ['year' => $date->format('Y'), 'month' => $date->format('n'), 'day' => $date->format('j')]
        );
    }

    /**
     * @param string  $action
     * @param string  $eid
     * @return string
     */
    public function createEventUrl($action, $eid)
    {
        return $this->createUrl($action, array("eid" => $eid));
    }

    /**
     * @param string  $action
     * @param string  $eid
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
            $url .= ($first ? '?' : '&')."$key=$value";
            $first = false;
        }
        if ($hash !== null) {
            $url .= '#'.$hash;
        }
        return $url;
    }
}