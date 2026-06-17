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

    #[ORM\Column(length: 255, enumType: Status::class)]
    private ?Status $status = null;

    /**
     * @var Collection<int, MastersServices>
     */
    #[ORM\OneToMany(targetEntity: MastersServices::class, mappedBy: 'service', cascade: ['remove'], orphanRemoval: true)]
    private Collection $mastersServices;

    public function __construct()
    {
        $this->status = Status::Awaiting;
        $this->mastersServices = new ArrayCollection();
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
     * @return Collection<int, MastersServices>
     */
    public function getMastersServices(): Collection
    {
        return $this->mastersServices;
    }

    public function addMastersService(MastersServices $mastersService): static
    {
        if (!$this->mastersServices->contains($mastersService)) {
            $this->mastersServices->add($mastersService);
            $mastersService->setService($this);
        }

        return $this;
    }

    public function removeMastersService(MastersServices $mastersService): static
    {
        if ($this->mastersServices->removeElement($mastersService)) {
            // set the owning side to null (unless already changed)
            if ($mastersService->getService() === $this) {
                $mastersService->setService(null);
            }
        }

        return $this;
    }

    #[Groups(["services:read"])]
    public function getMastersId(): array
    {
        return array_map(
            fn(MastersServices $ms) => $ms->getMaster()->getId(),
            $this->mastersServices->toArray()
        );
    }

    public function __toString(): string
    {
        return $this->name . ' ' . $this->default_time->format('H:i:s');
    }
}
