<?php

namespace App\Entity;

use App\Repository\BookingsRepository;
use App\Validator\Booking\BookingAvailability;
use App\Validator\FutureDateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BookingsRepository::class)]
class Bookings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Masters $id_master = null;

    /**
     * @var Collection<int, Services>
     */
    #[ORM\ManyToMany(targetEntity: Services::class)]
    private Collection $id_services;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[BookingAvailability]
    private ?DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[BookingAvailability]
    #[FutureDateTime]
    private ?DateTimeInterface $time_start = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[BookingAvailability]
    private ?DateTimeInterface $time_stop = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pets $pet = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Clients $id_client = null;

    public function __construct()
    {
        $this->id_services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdMaster(): ?Masters
    {
        return $this->id_master;
    }
   #[Groups(['booking:read'])]
    public function getMasterId(): ?int
    {
        return $this->id_master?->getId();
    }

    public function setIdMaster(?Masters $id_master): static
    {
        $this->id_master = $id_master;

        return $this;
    }

    /**
     * @return Collection<int, Services>
     */
    public function getIdServices(): Collection
    {
        return $this->id_services;
    }

    #[Groups(["booking:read"])]
    public function getServices(): array
    {
        return array_map(fn(Services $service) => $service->getId(), $this->id_services->toArray());
    }


    public function addIdService(Services $idService): static
    {
        if (!$this->id_services->contains($idService)) {
            $this->id_services->add($idService);
        }

        return $this;
    }

    public function removeIdService(Services $idService): static
    {
        $this->id_services->removeElement($idService);

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }
    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getTimeStart(): ?DateTimeInterface
    {
        return $this->time_start;
    }

    public function setTimeStart(?\DateTimeInterface $time_start): static
    {
        $this->time_start = $time_start;
        return $this;
    }

    public function getTimeStop(): ?DateTimeInterface
    {
        return $this->time_stop;
    }

    public function setTimeStop(?\DateTimeInterface $time_stop): static
    {
        $this->time_stop = $time_stop;
        return $this;
    }

    public function getPet(): ?Pets
    {
        return $this->pet;
    }

    #[Groups(["booking:read"])]
    public function getPetId(): ?int
    {
        return $this->pet ? $this->pet->getId() : null;
    }

    public function setPet(?Pets $pet): static
    {
        $this->pet = $pet;

        return $this;
    }

    public function getIdClient(): ?Clients
    {
        return $this->id_client;
    }
   #[Groups(["booking:read"])]
    public function getClientId(): ?int
    {
        return $this->id_client ? $this->id_client->getId() : null;
    }

    public function setIdClient(?Clients $id_client): static
    {
        $this->id_client = $id_client;

        return $this;
    }
}
