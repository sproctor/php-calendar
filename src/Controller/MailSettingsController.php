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
use App\Install\InstallState;
use App\Mail\Mailing;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * How the site sends email (password resets, confirming addresses), saved in .env.local.php
 */
#[Route('/{_locale}/admin/mail')]
#[IsGranted('ROLE_ADMIN')]
class MailSettingsController extends AbstractController
{
    private const NONE = 'none';
    private const SMTP = 'smtp';
    private const SENDMAIL = 'sendmail';

    public function __construct(
        private Mailing $mailing,
        private InstallState $installState,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/', name: 'mail_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request): Response
    {
        $editable = $this->installState->hasConfigFile();
        [$dsn, $from] = $this->currentSettings();
        $form = $this->createFormBuilder(self::fromDsn($dsn) + ['from' => $from], [
            'disabled' => !$editable,
            'translation_domain' => 'account',
        ])
            ->add('transport', ChoiceType::class, [
                'label' => 'mail-transport-label',
                'choices' => [
                    'mail-transport-none' => self::NONE,
                    'mail-transport-smtp' => self::SMTP,
                    'mail-transport-sendmail' => self::SENDMAIL,
                ],
                'expanded' => true,
            ])
            ->add('host', TextType::class, ['label' => 'smtp-host-label', 'required' => false])
            ->add('port', IntegerType::class, [
                'label' => 'smtp-port-label',
                'help' => 'smtp-port-help',
                'required' => false,
                'constraints' => [new Range(min: 1, max: 65535)],
            ])
            ->add('tls', ChoiceType::class, [
                'label' => 'smtp-security-label',
                'choices' => ['smtp-security-starttls' => false, 'smtp-security-tls' => true],
            ])
            ->add('username', TextType::class, ['label' => 'smtp-username-label', 'required' => false, 'attr' => ['autocomplete' => 'off']])
            ->add('password', PasswordType::class, [
                'label' => 'smtp-password-label',
                'help' => 'smtp-password-help',
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('from', EmailType::class, [
                'label' => 'mail-from-label',
                'help' => 'mail-from-help',
                'required' => false,
                'constraints' => [new Email()],
            ])
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')])
            ->getForm();

        $form->handleRequest($request);
        if ($editable && $form->isSubmitted()) {
            $data = $form->getData();
            if ($data['transport'] !== self::NONE && ($data['from'] ?? '') === '') {
                $form->get('from')->addError(new FormError($this->trans('mail-from-required-error')));
            }
            if ($data['transport'] === self::SMTP && trim($data['host'] ?? '') === '') {
                $form->get('host')->addError(new FormError($this->trans('smtp-host-required-error')));
            }
            if ($form->isValid()) {
                // A blank password keeps the saved one, as long as the server is the same
                $current = self::fromDsn($dsn);
                if (($data['password'] ?? '') === '' && ($current['host'] ?? null) === $data['host']) {
                    $data['password'] = $current['password'] ?? null;
                }
                $this->installState->updateConfig([
                    'MAILER_DSN' => self::toDsn($data),
                    'MAILER_FROM' => $data['transport'] === self::NONE ? '' : (string) $data['from'],
                ]);
                $this->addFlash('success', 'settings-saved-message');

                return $this->redirectToRoute('mail_settings');
            }
        }

        return $this->render('admin/mail.html.twig', [
            'form' => $form,
            'editable' => $editable,
            'configured' => $this->mailing->isConfigured(),
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /**
     * Sends a test email to the admin's own address and says whether the mail server accepted it
     */
    #[Route('/test', name: 'mail_test', methods: ['POST'])]
    public function test(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('mail-test', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getEmail() === null) {
            $this->addFlash('danger', new TranslatableMessage('test-email-no-address-error', domain: 'account'));
        } else {
            try {
                $this->mailing->send($user->getEmail(), $user, 'test-email-subject', 'test');
                $this->addFlash('success', new TranslatableMessage('test-email-sent-message', ['%email%' => $user->getEmail()], 'account'));
            } catch (TransportExceptionInterface $e) {
                // Only admins see this, and the server's message is what they need to fix it
                $this->addFlash('danger', new TranslatableMessage('test-email-failed-error', ['%error%' => $e->getMessage()], 'account'));
            }
        }

        return $this->redirectToRoute('mail_settings');
    }

    /**
     * MAILER_DSN and MAILER_FROM as saved in .env.local.php, else as the app is running with
     */
    private function currentSettings(): array
    {
        $saved = $this->installState->readConfig();

        return [
            $saved['MAILER_DSN'] ?? $this->mailing->getDsn(),
            $saved['MAILER_FROM'] ?? $this->mailing->getFrom(),
        ];
    }

    private function trans(string $key): string
    {
        return $this->translator->trans($key, [], 'account');
    }

    /**
     * The form's fields from a MAILER_DSN
     */
    private static function fromDsn(string $value): array
    {
        try {
            $dsn = Dsn::fromString($value);
        } catch (\Throwable) {
            return ['transport' => self::NONE, 'tls' => false];
        }

        return match ($dsn->getScheme()) {
            'smtp', 'smtps' => [
                'transport' => self::SMTP,
                'host' => $dsn->getHost(),
                'port' => $dsn->getPort(),
                'tls' => $dsn->getScheme() === 'smtps',
                'username' => $dsn->getUser(),
                'password' => $dsn->getPassword(),
            ],
            'sendmail', 'native' => ['transport' => self::SENDMAIL, 'tls' => false],
            default => ['transport' => self::NONE, 'tls' => false],
        };
    }

    private static function toDsn(array $data): string
    {
        return match ($data['transport']) {
            self::SMTP => sprintf('%s://%s%s%s',
                $data['tls'] ? 'smtps' : 'smtp',
                ($data['username'] ?? '') !== '' ? rawurlencode($data['username'])
                    . (($data['password'] ?? '') !== '' ? ':' . rawurlencode($data['password']) : '') . '@' : '',
                trim($data['host']),
                $data['port'] ? ':' . $data['port'] : ''),
            self::SENDMAIL => 'sendmail://default',
            default => Mailing::UNCONFIGURED_DSN,
        };
    }
}
