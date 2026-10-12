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
use App\Mail\Mailing;
use App\Repository\UserRepository;
use App\Security\AccountLinks;
use App\Service\LocaleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        TranslatorInterface         $translator,
        UserRepository              $userRepository,
        Mailing                     $mailing,
        AccountLinks                $links,
    ): Response
    {
        /* @var ?User $user */
        $user = $this->getUser();
        if ($user === null) {
            throw $this->createAccessDeniedException();
        }

        $passwordForm = $this->createForm(ChangePasswordFormType::class);

        $settingsData = [
            'email' => $user->getEmail(),
            'timezone' => $user->getTimezone(),
            'locale' => $user->getLocale(),
        ];
        $settingsForm = $this->createForm(
            UserSettingsFormType::class,
            $settingsData,
            ['locales' => $localeService->getLocaleChoices(), 'mail_enabled' => $mailing->isConfigured()],
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
            $email = (new User())->setEmail($data['email'])->getEmail();
            $emailChanged = mb_strtolower((string) $email) !== mb_strtolower((string) $user->getEmail());
            if ($emailChanged && $email !== null && array_filter(
                $userRepository->findLoginConflicts(['email' => $email]),
                fn(User $other) => !$other->isSameUser($user),
            ) !== []) {
                $settingsForm->get('email')->addError(new FormError($translator->trans('email-taken-error', [], 'validators')));
            } else {
                $user->setTimezone($data['timezone']);
                $user->setLocale($data['locale']);
                $confirming = $emailChanged && $email !== null && $mailing->isConfigured();
                if ($emailChanged && !$confirming) {
                    // Removing the address, or no mail to prove it with
                    $user->setEmail($email);
                }
                $entityManager->flush();
                $this->addFlash('success', 'settings-saved-message');
                if ($confirming) {
                    try {
                        $mailing->send($email, $user, 'confirm-email-subject', 'confirm_email', [
                            'user' => $user,
                            'url' => $links->confirmEmailUrl($user, $email),
                        ]);
                        $this->addFlash('info', new TranslatableMessage('confirm-email-sent-message', ['%email%' => $email], 'account'));
                    } catch (TransportExceptionInterface) {
                        $this->addFlash('danger', new TranslatableMessage('email-send-failed-error', domain: 'account'));
                    }
                }

                return $this->redirectToRoute('user_settings');
            }
        }

        return $this->render('user/settings.html.twig', [
            'passwordForm' => $user->hasEditablePassword() ? $passwordForm : null,
            'settingsForm' => $settingsForm,
        ], new Response(status: $request->isMethod('POST') ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
