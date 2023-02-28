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

namespace old;

use App\Entity\User;
use DateTimeInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Locale;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Context
{
    private ?User $user;
    private FormFactoryInterface $formFactory;
    public Request $request;
    public Session $session;
    public Translator $translator;

    /**
     * Context constructor.
     *
     * @param EntityManager $entityManager
     * @throws Exception
     */
    public function __construct(
        private RequestStack           $requestStack,
        public EntityManagerInterface $entityManager,
        private Environment            $twig,
        Security               $security,
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $requestStack->getSession();

        $appVariableReflection = new \ReflectionClass('\\' . \Symfony\Bridge\Twig\AppVariable::class);
        $vendorTwigBridgeDir = dirname($appVariableReflection->getFileName());

        $this->user = $security->getUser();

        /* Replace this with Doctrine Migrations
        include_once __DIR__ . '/schema.php';
        if ($this->db->getConfig('version') < PHPC_DB_VERSION) {
            return;
        }

        if ($this->db->getConfig('version') > PHPC_DB_VERSION) {
            throw new InvalidConfigException();
        }
        */

        $this->initTimezone();
        $this->initLocale();

        $this->initTwig();
    }

    private function initTwig()
    {
        $csrfGenerator = new UriSafeTokenGenerator();
        $csrfStorage = new SessionTokenStorage($this->requestStack);
        $csrfManager = new CsrfTokenManager($csrfGenerator, $csrfStorage);
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new CsrfExtension($csrfManager))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();

        $this->twig->addGlobal('context', $this);
        $this->twig->addGlobal('locale', \Locale::getDefault());
        $this->twig->addGlobal('script', $this->request->getScriptName());
        $this->twig->addGlobal('embed', $this->request->get("content") == "embed");
//        $this->twig->addGlobal('messages', $this->getMessages());
        //'theme' => $context->getCalendar()->get_theme(),
        $this->twig->addGlobal('minified', defined('PHPC_DEBUG') ? '' : '.min');
        $this->twig->addGlobal('languages', $this->getLanguageMappings());

        $this->twig->addFilter(new TwigFilter('date_index', '\date_index'));
        $this->twig->addFunction(
            new TwigFunction(
                'add_days',
                function (\DateTimeInterface $date, $days) {
                    $next_date = new \DateTime('@' . $date->getTimestamp());
                    return $next_date->add(new \DateInterval("P{$days}D"));
                }
            )
        );
        $this->twig->addFunction(new TwigFunction('is_today', '\is_today'));
        $this->twig->addFunction(new TwigFunction('append_parameter_url', '\append_parameter_url'));
        $this->twig->addFunction(
            new TwigFunction(
                'day',
                fn(\DateTimeInterface $date) => $date->format('j')
            )
        );
        $this->twig->addFunction(
            new TwigFunction(
                'month',
                fn(\DateTimeInterface $date) => $date->format('n')
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
//        $this->twig->addFunction(new TwigFunction(
//            'menu_item',
//            '\PhpCalendar\menu_item',
//            array('is_safe' => array('html'))
//        ));
    }

    /**
     * @return string
     */
    public function getConfig(string $key)
    {
        $config = $this->entityManager->find('App:Config', $key);

        return empty($config) ? null : $config->getValue();
    }

    private function initTimezone()
    {
        // Set timezone
        $tz = $this->user?->getTimezone();

        if (!empty($tz)) {
            date_default_timezone_set($tz);
        }
    }

    private function initLocale()
    {
        $request = $this->requestStack->getCurrentRequest();

        // setup translation stuff
        $locale = $this->user?->getLocale();
        if (empty($locale)) {
            // TODO search through valid locales for a match
            $locale = $request->getLocale();
            if (empty($locale)) {
                $locale = 'en';
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
        $this->translator->setFallbackLocales(['en']);
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
        } catch (NotFoundResourceException) {
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

    public function createDateUrl(string $action, ?DateTimeInterface $date = null): string
    {
        if ($date == null) {
            $date = new \DateTimeImmutable();
        }
        return $this->createUrl(
            $action,
            ['year' => $date->format('Y'), 'month' => $date->format('n'), 'day' => $date->format('j')]
        );
    }

    private ?array $mappings = null;



    /**
     * @param string $action
     * @param string $eid
     * @return string
     */
    public function createEventUrl($action, $eid)
    {
        return $this->createUrl($action, ["eid" => $eid]);
    }

    /**
     * @param string $action
     * @param string $oid
     * @return string
     */
    public function createOccurrenceUrl($action, $oid)
    {
        return $this->createUrl($action, ["oid" => $oid]);
    }

    /**
     * @param string|null $action
     * @param string[] $parameters
     * @param string|null $hash
     * @return string
     */
    public function createUrl($action = null, $parameters = [], $hash = null)
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
