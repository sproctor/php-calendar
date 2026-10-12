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
namespace App\Mail;

use App\Entity\User;
use App\Service\LocaleService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sends the app's emails, each in its recipient's language
 */
class Mailing
{
    /** The DSN shipped in .env and written by the installer, which sends nothing */
    public const UNCONFIGURED_DSN = 'null://null';

    public function __construct(
        private MailerInterface $mailer,
        private LocaleSwitcher $localeSwitcher,
        private LocaleService $localeService,
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
        #[Autowire('%env(MAILER_DSN)%')]
        private string $dsn,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $from,
    ) {
    }

    /**
     * Whether a mail server and From address are set up, so features that send email can be offered
     */
    public function isConfigured(): bool
    {
        return $this->dsn !== self::UNCONFIGURED_DSN && $this->dsn !== '' && $this->from !== '';
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Sends the email made from templates/emails/$template.{html,txt}.twig to $address, in $user's
     * language (or the current one). The subject is the translation of $subject in the "account" domain.
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface when the mail server fails
     */
    public function send(string $address, ?User $user, string $subject, string $template, array $context = []): void
    {
        $locale = $this->localeFor($user);
        $this->localeSwitcher->runWithLocale($locale, function () use ($address, $subject, $template, $context, $locale) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->from, 'PHP-Calendar'))
                ->to($address)
                ->subject($this->translator->trans($subject, [], 'account', $locale))
                ->htmlTemplate("emails/$template.html.twig")
                ->textTemplate("emails/$template.txt.twig")
                ->context($context + ['locale' => $locale]);
            $this->mailer->send($email);
        });
    }

    private function localeFor(?User $user): string
    {
        $chosen = $user?->getLocale();
        if ($chosen !== null && $chosen !== '' && ($translation = $this->localeService->translationFor($chosen)) !== null) {
            return $translation;
        }

        return $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';
    }
}
