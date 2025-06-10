<?php

namespace App\Entity;

use App\Enum\Status;
use App\Repository\ServicesRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ServicesRepository::class)]
class Services
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $cost = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?DateTimeImmutable $default_time = null;

    /**
     * @var Collection<int, Masters>
     */
    #[ORM\ManyToMany(targetEntity: Masters::class, mappedBy: 'id_services')]
    private Collection $masters;

    #[ORM\Column(length: 255, enumType: Status::class)]
    private ?Status $status = null;

    public function __construct()
    {
        $this->masters = new ArrayCollection();
        $this->status = Status::Awaiting;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(int $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getDefaultTime(): ?DateTimeImmutable
    {
        return $this->default_time;
    }

    public function setDefaultTime(DateTimeImmutable $default_time): static
    {
        $this->default_time = $default_time;

        return $this;
    }

    /**
     * @return Collection<int, Masters>
     */
    public function getMasters(): Collection
    {
        return $this->masters;
    }

    #[Groups(["services:read"])]
    public function getMastersId(): array
    {
        return array_map(fn(Masters $master) => $master->getId(), $this->masters->toArray());
    }

    public function addMaster(Masters $master): static
    {
        if (!$this->masters->contains($master)) {
            $this->masters->add($master);
            $master->addIdService($this);
        }

        return $this;
    }

    public function removeMaster(Masters $master): static
    {
        if ($this->masters->removeElement($master)) {
            $master->removeIdService($this);
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
