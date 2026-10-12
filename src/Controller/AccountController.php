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
use App\Mail\Mailing;
use App\Repository\UserRepository;
use App\Security\AccountLinks;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Resetting a forgotten password, and confirming a new email address, through emailed links
 */
class AccountController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private AccountLinks $links,
        private Mailing $mailing,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * "Forgot your password?": emails a reset link to the account's address. Says the same thing whether
     * or not the account exists, so the form can't be used to find out who has one.
     */
    #[Route('/reset-password', name: 'forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        #[Autowire(service: 'limiter.password_reset_ip')] RateLimiterFactory $ipLimiter,
        #[Autowire(service: 'limiter.password_reset_account')] RateLimiterFactory $accountLimiter,
    ): Response {
        if (!$this->mailing->isConfigured()) {
            throw $this->createNotFoundException();
        }
        $form = $this->createFormBuilder()
            ->add('identifier', TextType::class, [
                'label' => new TranslatableMessage('username-or-email-label', domain: 'account'),
                'constraints' => [new NotBlank()],
                'attr' => ['autocomplete' => 'username', 'autofocus' => true],
            ])
            ->add('send', SubmitType::class, ['label' => new TranslatableMessage('send-reset-link-button', domain: 'account')])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$ipLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $this->addFlash('danger', new TranslatableMessage('too-many-requests-error', domain: 'account'));

                return $this->redirectToRoute('forgot_password');
            }
            $user = $this->userRepository->loadUserByIdentifier(trim($form->get('identifier')->getData()));
            if ($user !== null && $user->getEmail() !== null && !$user->isDisabled() && $user->hasEditablePassword()
                && $accountLimiter->create((string) $user->getUid())->consume()->isAccepted()) {
                try {
                    $this->mailing->send($user->getEmail(), $user, 'reset-password-email-subject', 'reset_password', [
                        'user' => $user,
                        'url' => $this->links->resetPasswordUrl($user),
                        'minutes' => intdiv(AccountLinks::RESET_LIFETIME, 60),
                    ]);
                } catch (TransportExceptionInterface $e) {
                    // Saying so would reveal that the account exists
                    $this->logger->error('Could not send a password reset email: {message}', ['message' => $e->getMessage()]);
                }
            }
            $this->addFlash('success', new TranslatableMessage('reset-link-sent-message', domain: 'account'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('account/forgot_password.html.twig', ['form' => $form],
            new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/reset-password/{uid}', name: 'reset_password', requirements: ['uid' => '\d+'], methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, int $uid, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->userRepository->find($uid);
        if ($user === null || $user->isDisabled() || !$user->hasEditablePassword() || !$this->links->isValidResetRequest($request, $user)) {
            return $this->render('account/invalid_link.html.twig', [
                'retry_url' => $this->mailing->isConfigured() ? $this->generateUrl('forgot_password') : null,
            ], new Response(status: Response::HTTP_GONE));
        }

        $form = $this->createFormBuilder(options: ['action' => $request->getRequestUri()])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => new TranslatableMessage('new-password-label'),
                    'attr' => ['autocomplete' => 'new-password', 'autofocus' => true],
                ],
                'second_options' => [
                    'label' => new TranslatableMessage('confirm-password-label'),
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'passwords-do-not-match-error',
                'constraints' => [new NotBlank(), new Length(min: 8, max: 4096)],
            ])
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('set-password-button', domain: 'account')])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setHash($passwordHasher->hashPassword($user, $form->get('password')->getData()));
            $this->entityManager->flush();
            $this->addFlash('success', 'password-changed-message');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('account/reset_password.html.twig', ['form' => $form, 'user' => $user],
            new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /**
     * The link emailed to a new address from the user settings page; saves the address once it's proven
     */
    #[Route('/confirm-email/{uid}', name: 'confirm_email', requirements: ['uid' => '\d+'], methods: ['GET'])]
    public function confirmEmail(Request $request, int $uid): Response
    {
        $user = $this->userRepository->find($uid);
        $email = $user === null ? null : $this->links->confirmedEmail($request);
        if ($user === null || $email === null) {
            return $this->render('account/invalid_link.html.twig', ['retry_url' => null],
                new Response(status: Response::HTTP_GONE));
        }

        $taken = array_filter($this->userRepository->findLoginConflicts(['email' => $email]), fn(User $other) => !$other->isSameUser($user));
        if ($taken !== []) {
            $this->addFlash('danger', new TranslatableMessage('email-taken-error', domain: 'validators'));
        } else {
            $user->setEmail($email);
            $this->entityManager->flush();
            $this->addFlash('success', new TranslatableMessage('email-confirmed-message', ['%email%' => $email], 'account'));
        }

        return $this->getUser() !== null ? $this->redirectToRoute('user_settings') : $this->redirectToRoute('app_login');
    }
}
