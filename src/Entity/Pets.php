<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\PetsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PetsRepository::class)]
#[ApiResource(
    description: 'Our little friends!.',
    operations: [
        new Get(),
        new GetCollection(),
        new Post()
    ],
    normalizationContext: ['groups' => ['pets:read']],
    denormalizationContext: ['groups' => ['pets:write']]
)]
class Pets
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["pets:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["pets:read", "pets:write"])]
    private ?string $spice = null;

    #[ORM\Column(length: 255)]
    #[Groups(["pets:write"])]
    private ?string $hair = null;

    #[ORM\Column(length: 255)]
    #[Groups(["pets:read", "pets:write"])]
    private ?string $breed = null;

    #[ORM\Column(length: 255)]
    #[Groups(["pets:write"])]
    private ?string $size = null;

    #[ORM\Column]
    #[Groups(["services:read"])]
    private ?float $cost_coficient = null;

    /**
     * @var Collection<int, Masters>
     */
    #[ORM\ManyToMany(targetEntity: Masters::class, mappedBy: 'id_pets')]
    private Collection $masters;

    public function __construct()
    {
        $this->masters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getSpice(): ?string
    {
        return $this->spice;
    }

    public function setSpice(string $spice): static
    {
        $this->spice = $spice;

        return $this;
    }

    public function getHair(): ?string
    {
        return $this->hair;
    }

    public function setHair(string $hair): static
    {
        $this->hair = $hair;

        return $this;
    }

    public function getBreed(): ?string
    {
        return $this->breed;
    }

    public function setBreed(string $breed): static
    {
        $this->breed = $breed;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getCostCoficient(): ?float
    {
        return $this->cost_coficient;
    }

    public function setCostCoficient(float $cost_coficient): static
    {
        $this->cost_coficient = $cost_coficient;

        return $this;
    }

    /**
     * @return Collection<int, Masters>
     */
    public function getMasters(): Collection
    {
        return $this->masters;
    }

    public function addMaster(Masters $master): static
    {
        if (!$this->masters->contains($master)) {
            $this->masters->add($master);
            $master->addIdPet($this);
        }

        return $this;
    }

    public function removeMaster(Masters $master): static
    {
        if ($this->masters->removeElement($master)) {
            $master->removeIdPet($this);
        }

        return $this;
    }
}
