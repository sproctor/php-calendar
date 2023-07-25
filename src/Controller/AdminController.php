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

use App\Entity\User;
use App\Repository\CalendarRepository;
use App\Repository\OccurrenceRepository;
use App\Repository\UserPermissionsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/admin")]
class AdminController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
    )
    {
    }

    #[Route("/", name: "admin")]
    public function settings(): Response
    {
        /* @var User $user */
        $user = $this->getUser();
        if ($user == null || !$user->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('admin.html.twig');
    }

    #[Route("/user/{uid}/disable", name: "disable_user")]
    public function disableUser(
        int                    $uid,
        UserRepository         $userRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        /* @var User $current_user */
        $current_user = $this->getUser();
        if (!$current_user?->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        /* @var User $user */
        $user = $userRepository->find($uid);
        $user->setIsDisabled(true);
        $entityManager->persist($user);
        $entityManager->flush();
        // TODO: message that the user was created
        // TODO: handle exception
        return $this->redirectToRoute('admin', ['_fragment' => 'users']);
    }

    #[Route("/user/{uid}/enable", name: "enable_user")]
    public function enableUser(
        int                    $uid,
        UserRepository         $userRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        /* @var User $current_user */
        $current_user = $this->getUser();
        if (!$current_user?->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        /* @var User $user */
        $user = $userRepository->find($uid);
        $user->setIsDisabled(false);
        $entityManager->persist($user);
        $entityManager->flush();
        // TODO: message that the user was created
        // TODO: handle exception
        return $this->redirectToRoute('admin', ['_fragment' => 'users']);
    }
}