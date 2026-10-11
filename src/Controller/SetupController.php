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
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

class SetupController extends AbstractController
{
    #[Route("/setup", name: "setup", methods: ['GET', 'POST'])]
    public function setup(
        Request                     $request,
        UserRepository              $userRepository,
        EntityManagerInterface      $entityManager,
        Security                    $security,
        UserPasswordHasherInterface $passwordHasher,
        KernelInterface             $kernel,
    ): Response
    {
        try {
            $hasUsers = $userRepository->count() > 0;
        } catch (ConnectionException) {
            $this->createDatabase($kernel);
            $this->createSchema($kernel);
            $hasUsers = false;
        } catch (TableNotFoundException) {
            $this->createSchema($kernel);
            $hasUsers = false;
        }

        // Setup creates the first admin, so it's only open until someone has an account
        if (!$hasUsers) {
            $data = [
                'calendar' => new Calendar(),
                'user' => new User()
            ];
            $form = $this->createFormBuilder($data)
                ->add('calendar', CalendarType::class)
                ->add('user', UserType::class)
                ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                /* @var Calendar $calendar */
                $calendar = $data['calendar'];
                $entityManager->persist($calendar);
                /* @var User $user */
                $user = $data['user'];
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('user')->get('password')->getData()
                );
                $user->setHash($hashedPassword);
                $user->setIsAdmin(true);
                $entityManager->persist($user);
                $entityManager->flush();
                $security->login($user, 'form_login', 'main');

                return $this->redirectToRoute('default');
            }

            return $this->render('setup.html.twig', ['form' => $form], new Response(
                status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
            ));
        }

        return $this->redirectToRoute('default');
    }

    private function createDatabase(KernelInterface $kernel): void
    {
        $this->runCommand($kernel, new ArrayInput(['command' => 'doctrine:database:create']));
    }

    private function createSchema(KernelInterface $kernel): void
    {
        $this->runCommand($kernel, new ArrayInput(['command' => 'doctrine:migrations:migrate', '--quiet' => true]));
    }

    private function runCommand(KernelInterface $kernel, InputInterface $input): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        try {
            $application->run($input, $output);
        } catch (Exception) {
            // TODO: output $output
            throw new Exception("Error running command: $input");
        }
    }
}
