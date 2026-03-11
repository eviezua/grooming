<?php

namespace App\Entity;

use App\Repository\DistrictsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DistrictsRepository::class)]
class Districts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'districts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cities $city = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCity(): ?Cities
    {
        return $this->city;
    }

    public function getCityId(): ?int
    {
        return $this->city ? $this->city->getId() : null;
    }

    public function setCity(?Cities $city): static
    {
        $this->city = $city;

        return $this;
    }
}
