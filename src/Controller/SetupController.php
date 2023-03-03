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
use App\Repository\CalendarRepository;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Application;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SetupController extends AbstractController
{
    #[Route("/setup", name: "setup")]
    public function setup(
        Request                     $request,
        CalendarRepository          $repository,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        KernelInterface $kernel,
    ): Response
    {
        $calendars = null;
        try {
            $calendars = $repository->findAll();
        } catch (ConnectionException) {
            $application = new Application($kernel);
        }

//        if (empty($calendars)) {
        $data = [
            'calendar' => new Calendar(),
            'user' => new User()
        ];
        $form = $this->createFormBuilder($data)
            ->add('calendar', CalendarType::class)
            ->add('user', UserType::class)
            ->add('save', SubmitType::class, ['label' => 'save-label'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($data['calendar']);
            /* @var User $user */
            $user = $data['user'];
            $user->setDefaultCalendar($data['calendar']);
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('user')->get('password')->getData()
            );
            $user->setHash($hashedPassword);
            $user->setIsAdmin(true);
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('default_month_display', ['cid' => $data['calendar']->getCid()]);
        }
        return $this->render('setup.html.twig', ['form' => $form]);
//        }

//        throw $this->createAccessDeniedException();
    }
}
