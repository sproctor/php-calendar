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
        #[Autowire('%env(DEFAULT_URI)%')]
        private string $siteUri,
    ) {
    }

    public function resetPasswordUrl(User $user): string
    {
        return $this->link('reset_password', [
            'uid' => $user->getUid(),
            'h' => $this->passwordFingerprint($user),
        ], self::RESET_LIFETIME);
    }

    public function isValidResetRequest(Request $request, User $user): bool
    {
        return $this->isSigned($request)
            && hash_equals($this->passwordFingerprint($user), $request->query->getString('h'));
    }

    public function confirmEmailUrl(User $user, string $email): string
    {
        return $this->link('confirm_email', [
            'uid' => $user->getUid(),
            'email' => $email,
        ], self::CONFIRM_LIFETIME);
    }

    /**
     * The new address from a valid confirmation link, or null
     */
    public function confirmedEmail(Request $request): ?string
    {
        return $this->isSigned($request) ? $request->query->getString('email') : null;
    }

    /**
     * On the site's address saved by the installer, never the request's Host header: anyone can send that,
     * and would get the link to someone else's account mailed to their own server. The signature leaves
     * out the scheme and host, so a link still works after a redirect to https or another host name.
     */
    private function link(string $route, array $parameters, int $lifetime): string
    {
        $path = $this->signer->sign($this->urlGenerator->generate($route, $parameters),
            new \DateInterval('PT' . $lifetime . 'S'));

        return $this->siteOrigin() . $path;
    }

    private function isSigned(Request $request): bool
    {
        $query = $request->server->getString('QUERY_STRING');

        return $this->signer->check($request->getBaseUrl() . $request->getPathInfo() . ($query !== '' ? '?' . $query : ''));
    }

    /**
     * The scheme, host and port of DEFAULT_URI
     */
    private function siteOrigin(): string
    {
        $url = parse_url($this->siteUri);
        if (!isset($url['scheme'], $url['host'])) {
            throw new \LogicException('DEFAULT_URI must be the site\'s address, e.g. https://example.com/calendar');
        }

        return $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
    }

    /**
     * Changes whenever the password does, without revealing anything about it
     */
    private function passwordFingerprint(User $user): string
    {
        return substr(hash_hmac('sha256', $user->getUid() . "\0" . $user->getPassword(), $this->secret), 0, 32);
    }
}
