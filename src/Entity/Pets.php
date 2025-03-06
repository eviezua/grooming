<?php

namespace App\Entity;

use App\Repository\PetsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetsRepository::class)]
class Pets
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $spice = null;

    #[ORM\Column(length: 255)]
    private ?string $hair = null;

    #[ORM\Column(length: 255)]
    private ?string $breed = null;

    #[ORM\Column(length: 255)]
    private ?string $size = null;

    #[ORM\Column]
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
        $this->updateCostCoefficient();
        return $this;
    }

    public function getHair(): ?string
    {
        return $this->hair;
    }

    public function setHair(string $hair): static
    {
        $this->hair = $hair;
        $this->updateCostCoefficient();
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
        $this->updateCostCoefficient();
        return $this;
    }
    public function getHairCoefficient(): float
    {
        return match ($this->hair) {
            'short' => 1.1,
            'medium' => 1.2,
            'long' => 1.3,
            default => 1.0
        };
    }

    public function getSizeCoefficient(): float
    {
        return match ($this->size) {
            'small' => 1.0,
            'medium' => 1.2,
            'large' => 1.5,
            default => 1.0
        };
    }

    public function getTypeCoefficient(): float
    {
        return match ($this->spice) {
            'dog' => 1.5,
            'cat' => 1.2,
            'rabbit' => 1.1,
            default => 1.0
        };
    }

    public function getCostCoficient(): ?float
    {
        return $this->cost_coficient;
    }
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateCostCoefficient(): void
    {
        $this->cost_coficient = $this->calculateCoefficient();
    }

    private function calculateCoefficient(): float
    {
        $hairCoefficient = $this->getHairCoefficient();
        $sizeCoefficient = $this->getSizeCoefficient();
        $typeCoefficient = $this->getTypeCoefficient();

        return round(($hairCoefficient + $sizeCoefficient + $typeCoefficient), 2);
    }

    public function setCostCoficient(float $cost_coficient): static
    {
        throw new \LogicException('Cost coefficient is calculated automatically and cannot be set manually.');
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
