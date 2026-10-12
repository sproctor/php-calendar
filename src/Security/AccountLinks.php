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
namespace App\Security;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Links emailed to people: to reset a password, and to confirm a new email address. They're signed with
 * APP_SECRET and expire, so nothing needs storing. A reset link also stops working once the password
 * changes, so it can only be used once.
 */
class AccountLinks
{
    public const RESET_LIFETIME = 3600;
    public const CONFIRM_LIFETIME = 86400;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire(service: 'uri_signer')]
        private UriSigner $signer,
        #[Autowire('%kernel.secret%')]
        private string $secret,
    ) {
    }

    public function resetPasswordUrl(User $user): string
    {
        $url = $this->urlGenerator->generate('reset_password', [
            'uid' => $user->getUid(),
            'h' => $this->passwordFingerprint($user),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->signer->sign($url, new \DateInterval('PT' . self::RESET_LIFETIME . 'S'));
    }

    public function isValidResetRequest(Request $request, User $user): bool
    {
        return $this->signer->checkRequest($request)
            && hash_equals($this->passwordFingerprint($user), $request->query->getString('h'));
    }

    public function confirmEmailUrl(User $user, string $email): string
    {
        $url = $this->urlGenerator->generate('confirm_email', [
            'uid' => $user->getUid(),
            'email' => $email,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->signer->sign($url, new \DateInterval('PT' . self::CONFIRM_LIFETIME . 'S'));
    }

    /**
     * The new address from a valid confirmation link, or null
     */
    public function confirmedEmail(Request $request): ?string
    {
        return $this->signer->checkRequest($request) ? $request->query->getString('email') : null;
    }

    /**
     * Changes whenever the password does, without revealing anything about it
     */
    private function passwordFingerprint(User $user): string
    {
        return substr(hash_hmac('sha256', $user->getUid() . "\0" . $user->getPassword(), $this->secret), 0, 32);
    }
}
