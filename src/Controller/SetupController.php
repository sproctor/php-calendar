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
use App\Form\CalendarType;
use App\Form\DatabaseFormType;
use App\Form\UserType;
use App\Import\Import20;
use App\Install\InstallState;
use App\Repository\UserRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The web installer. In order, it: asks for the database connection and saves it; creates the tables;
 * then creates the first calendar and admin, or imports a PHP-Calendar 2.0 database. After an upgrade,
 * it lets a site admin bring the database up to date.
 */
class SetupController extends AbstractController
{
    public function __construct(
        private InstallState $installState,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/setup', name: 'setup', methods: ['GET', 'POST'])]
    public function setup(
        Request $request,
        UserRepository $userRepository,
        Security $security,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if (!$this->installState->isConfigured()) {
            return $this->databaseStep($request);
        }

        $hasUsers = $this->hasUsers($userRepository);
        if ($this->installState->hasPendingMigrations()) {
            if ($hasUsers) {
                // An upgrade: only a site admin may update the database
                if (!$this->isGranted('ROLE_ADMIN')) {
                    return $this->redirectToRoute('app_login', ['target_path' => $this->generateUrl('setup')]);
                }

                return $this->render('setup/update.html.twig');
            }
            // A new installation: create the tables
            $this->installState->migrate();

            return $this->redirectToRoute('setup');
        }
        if ($hasUsers) {
            return $this->redirectToRoute('default');
        }

        // Setup creates the first admin, so it's only open until someone has an account
        $data = ['calendar' => new Calendar(), 'user' => new User()];
        $data['calendar']->setTimezone(date_default_timezone_get());
        $form = $this->createFormBuilder($data)
            ->add('calendar', CalendarType::class)
            ->add('user', UserType::class)
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Calendar $calendar */
            $calendar = $data['calendar'];
            /** @var User $user */
            $user = $data['user'];
            $user->setHash($passwordHasher->hashPassword($user, $form->get('user')->get('password')->getData()));
            $user->setIsAdmin(true);
            $this->entityManager->persist($calendar);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $security->login($user, 'form_login', 'main');

            return $this->redirectToRoute('default');
        }

        return $this->render('setup.html.twig', [
            'form' => $form,
            'import_prefixes' => $this->importPrefixes(),
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/setup/update', name: 'setup_update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('setup-update', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        $this->installState->migrate();
        $this->addFlash('success', 'database-updated-message');

        return $this->redirectToRoute('default');
    }

    #[Route('/setup/import', name: 'setup_import', methods: ['POST'])]
    public function import(Request $request, UserRepository $userRepository): Response
    {
        if (!$this->installState->isConfigured() || $this->installState->hasPendingMigrations()
            || $this->hasUsers($userRepository)) {
            return $this->redirectToRoute('setup');
        }
        if (!$this->isCsrfTokenValid('setup-import', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        $prefix = $request->getPayload()->getString('prefix');
        if (!in_array($prefix, $this->importPrefixes(), true)) {
            throw $this->createNotFoundException();
        }

        $import = new Import20($this->entityManager, $this->entityManager->getConnection(), $prefix);
        $counts = $import->run();

        return $this->render('setup/imported.html.twig', [
            'counts' => $counts,
            'warnings' => $import->getWarnings(),
        ]);
    }

    private function databaseStep(Request $request): Response
    {
        $requirements = array_filter($this->installState->requirements());
        $form = $this->createForm(DatabaseFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $requirements === []) {
            $url = $this->testConnection($form);
            if ($url !== null) {
                $this->installState->writeConfig($url, $request->getSchemeAndHttpHost() . $request->getBasePath());

                return $this->redirectToRoute('setup');
            }
        }

        return $this->render('setup/database.html.twig', [
            'form' => $form,
            'requirements' => $requirements,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /**
     * Connects with the submitted settings. Returns the DATABASE_URL to save, or null after adding the
     * connection error to the form.
     */
    private function testConnection(FormInterface $form): ?string
    {
        $data = $form->getData();
        $params = [
            'driver' => 'pdo_mysql',
            'host' => $data['host'],
            'port' => $data['port'],
            'dbname' => $data['name'],
            'user' => $data['user'],
            'password' => (string) $data['password'],
        ];
        try {
            $connection = DriverManager::getConnection($params);
            $version = (string) $connection->fetchOne('SELECT VERSION()');
            $connection->close();
        } catch (DBALException $e) {
            $form->addError(new FormError($this->translator->trans('database-connection-error', [
                '%error%' => $e->getMessage(),
            ])));

            return null;
        }
        // DBAL wants e.g. "10.11.6-MariaDB"; MySQL versions can be used as they are
        if (preg_match('/^(\d+\.\d+\.\d+)-.*mariadb/i', $version, $matches)) {
            $version = $matches[1] . '-MariaDB';
        } elseif (preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches)) {
            $version = $matches[1];
        }
        $host = str_contains($data['host'], ':') ? '[' . $data['host'] . ']' : $data['host'];

        return sprintf(
            'mysql://%s:%s@%s:%d/%s?serverVersion=%s&charset=utf8mb4',
            rawurlencode($data['user']),
            rawurlencode((string) $data['password']),
            $host,
            $data['port'],
            rawurlencode($data['name']),
            rawurlencode($version),
        );
    }

    private function hasUsers(UserRepository $userRepository): bool
    {
        try {
            return $userRepository->count() > 0;
        } catch (DBALException) {
            return false;
        }
    }

    /**
     * Table prefixes of PHP-Calendar 2.0 installations in this database, which can be imported
     *
     * @return string[]
     */
    private function importPrefixes(): array
    {
        return array_values(array_filter(
            Import20::findPrefixes($this->entityManager->getConnection()),
            fn(string $prefix) => $prefix !== '',
        ));
    }
}
