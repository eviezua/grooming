<?php

namespace App\Entity;

use App\Enum\Hair;
use App\Enum\Size;
use App\Enum\Species;
use App\Enum\Status;
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

    #[ORM\Column(length: 255, enumType: Species::class)]
    private ?Species $spice = null;

    #[ORM\Column(length: 255, enumType: Hair::class)]
    private ?Hair $hair = null;

    #[ORM\Column(length: 255)]
    private ?string $breed = null;

    #[ORM\Column(length: 255, enumType: Size::class)]
    private ?Size $size = null;

    #[ORM\Column]
    private ?float $cost_coficient = null;

    /**
     * @var Collection<int, Masters>
     */
    #[ORM\ManyToMany(targetEntity: Masters::class, mappedBy: 'id_pets')]
    private Collection $masters;

    #[ORM\Column(length: 255, enumType: Status::class)]
    private ?Status $status = null;

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

    public function getSpice(): ?Species
    {
        return $this->spice;
    }

    public function setSpice(Species $spice): static
    {
        $this->spice = $spice;

        return $this;
    }

    public function getHair(): ?Hair
    {
        return $this->hair;
    }

    public function setHair(Hair $hair): static
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

    public function getSize(): ?Size
    {
        return $this->size;
    }

    public function setSize(Size $size): static
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

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;

        return $this;
    }
}
