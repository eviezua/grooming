<?php

namespace App\Entity;

use App\Repository\MastersServicesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MastersServicesRepository::class)]
class MastersServices
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mastersServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Masters $master = null;

    #[ORM\ManyToOne(inversedBy: 'mastersServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Services $service = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaster(): ?Masters
    {
        return $this->master;
    }

    #[Groups(["ms:read"])]
    public function getMasterId(): ?int
    {
        return $this->master?->getId();
    }


    public function setMaster(?Masters $master): static
    {
        $this->master = $master;

        return $this;
    }

    public function getService(): ?Services
    {
        return $this->service;
    }

    #[Groups(["ms:read"])]
    public function getServiceId(): ?int
    {
        return $this->service?->getId();
    }

    public function setService(?Services $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }
}
