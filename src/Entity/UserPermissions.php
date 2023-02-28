<?php

namespace App\Entity;

use App\Repository\UserPermissionsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPermissionsRepository::class)]
class UserPermissions
{
    public function __construct(
        #[ORM\Id, ORM\Column(type: "integer")]
        private int $cid,
        #[ORM\Id, ORM\Column(type: "integer", nullable: true)]
        private int|null $uid
    ) {
    }

    #[ORM\Column(type: "boolean")]
    private bool $read = false;

    #[ORM\Column(type: "boolean")]
    private bool $create = false;

    #[ORM\Column(type: "boolean")]
    private bool $update = false;

    #[ORM\Column(type: "boolean")]
    private bool $moderate = false;

    #[ORM\Column(type: "boolean")]
    private bool $admin = false;

    public function getCid(): int
    {
        return $this->cid;
    }

    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function canRead(): ?bool
    {
        return $this->read;
    }

    public function setRead(bool $read): self
    {
        $this->read = $read;

        return $this;
    }

    public function canCreate(): bool
    {
        return $this->create;
    }

    public function setCreate(bool $create): self
    {
        $this->create = $create;

        return $this;
    }

    public function canUpdate(): bool
    {
        return $this->update;
    }

    public function setUpdate(bool $update): self
    {
        $this->update = $update;

        return $this;
    }

    public function canModerate(): bool
    {
        return $this->moderate;
    }

    public function setModerate(bool $moderate): self
    {
        $this->moderate = $moderate;

        return $this;
    }

    public function canAdmin(): bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }
}
