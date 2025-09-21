<?php

namespace App\Entity;

use App\Enum\Status;
use App\Repository\CitiesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CitiesRepository::class)]
class Cities
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $city = null;

    /**
     * @var Collection<int, Masters>
     */
    #[ORM\OneToMany(targetEntity: Masters::class, mappedBy: 'id_city')]
    private Collection $masters;

    #[ORM\Column(length: 255, enumType: Status::class)]
    private ?Status $status = null;

    /**
     * @var Collection<int, Districts>
     */
    #[ORM\OneToMany(targetEntity: Districts::class, mappedBy: 'city')]
    private Collection $districts;

    public function __construct()
    {
        $this->masters = new ArrayCollection();
        $this->status = Status::Awaiting;
        $this->districts = new ArrayCollection();
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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = mb_convert_case($city, MB_CASE_TITLE, "UTF-8");

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
            $master->setIdCity($this);
        }

        return $this;
    }

    public function removeMaster(Masters $master): static
    {
        if ($this->masters->removeElement($master)) {
            // set the owning side to null (unless already changed)
            if ($master->getIdCity() === $this) {
                $master->setIdCity(null);
            }
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

    /**
     * @return Collection<int, Districts>
     */
    public function getDistricts(): Collection
    {
        return $this->districts;
    }

    public function addDistrict(Districts $district): static
    {
        if (!$this->districts->contains($district)) {
            $this->districts->add($district);
            $district->setCity($this);
        }

        return $this;
    }

    public function removeDistrict(Districts $district): static
    {
        if ($this->districts->removeElement($district)) {
            // set the owning side to null (unless already changed)
            if ($district->getCity() === $this) {
                $district->setCity(null);
            }
        }

        return $this;
    }
}