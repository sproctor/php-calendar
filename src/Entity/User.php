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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
#[ORM\Entity]
#[ORM\Table('users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $uid;

    #[ORM\Column(type: 'string', length: 255)]
    private string $username;

    #[ORM\Column(type: 'string', length: 255)]
    private string $hash;

    #[ORM\Column(type: 'boolean')]
    private bool $is_admin = false;

    #[ORM\Column(type: 'boolean')]
    private bool $password_is_editable = true;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $timezone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: 'boolean')]
    private bool $is_disabled = false;

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->hash;
    }

    public function setPassword(string $password): self
    {
        $this->hash = $password;

        return $this;
    }

    public function hasEditablePassword(): bool
    {
        return $this->password_is_editable;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function isDisabled(): bool
    {
        return $this->is_disabled;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = [];
        // guarantee every user at least has ROLE_USER
        if (!$this->isDisabled()) {
            $roles[] = 'ROLE_USER';
        }
        if ($this->isAdmin()) {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_unique($roles);
    }

    /**
     * Returning a salt is only needed if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function setIsAdmin(bool $is_admin): self
    {
        $this->is_admin = $is_admin;

        return $this;
    }

    public function isPasswordIsEditable(): ?bool
    {
        return $this->password_is_editable;
    }

    public function setPasswordIsEditable(bool $password_is_editable): self
    {
        $this->password_is_editable = $password_is_editable;

        return $this;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function setIsDisabled(bool $is_disabled): self
    {
        $this->is_disabled = $is_disabled;

        return $this;
    }
}
