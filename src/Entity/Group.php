<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $gid = null;

    #[ORM\Column]
    private ?bool $can_read = null;

    #[ORM\Column]
    private ?bool $can_create = null;

    #[ORM\Column]
    private ?bool $can_update = null;

    #[ORM\Column]
    private ?bool $can_moderate = null;

    #[ORM\Column]
    private ?bool $can_admin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function canRead(): ?bool
    {
        return $this->can_read;
    }

    public function setRead(bool $can_read): self
    {
        $this->can_read = $can_read;

        return $this;
    }

    public function canCreate(): ?bool
    {
        return $this->can_create;
    }

    public function setCreate(bool $can_create): self
    {
        $this->can_create = $can_create;

        return $this;
    }

    public function canUpdate(): ?bool
    {
        return $this->can_update;
    }

    public function setUpdate(bool $can_update): self
    {
        $this->can_update = $can_update;

        return $this;
    }

    public function canModerate(): ?bool
    {
        return $this->can_moderate;
    }

    public function setModerate(bool $can_moderate): self
    {
        $this->can_moderate = $can_moderate;

        return $this;
    }

    public function canAdmin(): ?bool
    {
        return $this->can_admin;
    }

    public function setAdmin(bool $can_admin): self
    {
        $this->can_admin = $can_admin;

        return $this;
    }
}
