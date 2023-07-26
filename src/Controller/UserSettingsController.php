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
use App\Form\ChangePasswordFormType;
use App\Form\Model\ChangePassword;
use App\Form\UserSettingsFormType;
use App\Service\LocaleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserSettingsController extends AbstractController
{
    #[Route('/{_locale}/settings', name: 'user_settings')]
    public function form(
        Request                     $request,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        LocaleService               $localeService,
    ): Response
    {
        /* @var ?User $user */
        $user = $this->getUser();
        if ($user === null) {
            throw $this->createAccessDeniedException();
        }

        $passwordForm = $this->createForm(ChangePasswordFormType::class);

        $settingsData = [
            'timezone' => $user->getTimezone(),
            'locale' => $user->getLocale(),
        ];
        $settingsForm = $this->createForm(
            UserSettingsFormType::class,
            $settingsData,
            ['locales' => array_flip($localeService->getLocaleMappings())],
        );

        $passwordForm->handleRequest($request);
        $settingsForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            /* @var ChangePassword $changePassword */
            $changePassword = $passwordForm->getData();
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $changePassword->newPassword
            );
            $user->setHash($hashedPassword);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        if ($settingsForm->isSubmitted() && $settingsForm->isValid()) {
            $data = $settingsForm->getData();
            $user->setTimezone($data['timezone']);
            $user->setLocale($data['locale']);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('user/settings.html.twig', [
            'passwordForm' => $passwordForm,
            'settingsForm' => $settingsForm,
        ]);
    }
}
