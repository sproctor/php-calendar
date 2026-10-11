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
use Symfony\Component\Routing\Attribute\Route;

class UserSettingsController extends AbstractController
{
    #[Route('/{_locale}/settings', name: 'user_settings', methods: ['GET', 'POST'])]
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
            ['locales' => $localeService->getLocaleChoices()],
        );

        $passwordForm->handleRequest($request);
        $settingsForm->handleRequest($request);

        if ($user->hasEditablePassword() && $passwordForm->isSubmitted() && $passwordForm->isValid()) {
            /* @var ChangePassword $changePassword */
            $changePassword = $passwordForm->getData();
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $changePassword->newPassword
            );
            $user->setHash($hashedPassword);
            $entityManager->flush();
            $this->addFlash('success', 'password-changed-message');

            return $this->redirectToRoute('user_settings');
        }

        if ($settingsForm->isSubmitted() && $settingsForm->isValid()) {
            $data = $settingsForm->getData();
            $user->setTimezone($data['timezone']);
            $user->setLocale($data['locale']);
            $entityManager->flush();
            $this->addFlash('success', 'settings-saved-message');

            return $this->redirectToRoute('user_settings');
        }

        return $this->render('user/settings.html.twig', [
            'passwordForm' => $user->hasEditablePassword() ? $passwordForm : null,
            'settingsForm' => $settingsForm,
        ], new Response(status: $request->isMethod('POST') ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
