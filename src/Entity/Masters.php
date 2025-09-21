<?php

namespace App\Entity;

use App\Enum\Status;
use App\Repository\MastersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MastersRepository::class)]
class Masters
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $surname = null;

    #[ORM\Column(type: 'float', options: ['default' => 0])]
    private float $avgRating = 0;

    #[ORM\ManyToOne(inversedBy: 'masters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cities $id_city = null;

    #[ORM\ManyToOne(targetEntity: Districts::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Districts $district = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    /**
     * @var Collection<int, Pets>
     */
    #[ORM\ManyToMany(targetEntity: Pets::class, inversedBy: 'masters')]
    private Collection $id_pets;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    /**
     * @var Collection<int, Schedule>
     */
    #[ORM\OneToMany(targetEntity: Schedule::class, mappedBy: 'master')]
    private Collection $schedules;

    /**
     * @var Collection<int, Bookings>
     */
    #[ORM\OneToMany(targetEntity: Bookings::class, mappedBy: 'id_master')]
    private Collection $bookings;

    #[ORM\Column(length: 255, enumType: Status::class)]
    private ?Status $status = null;

    /**
     * @var Collection<int, MastersServices>
     */
    #[ORM\OneToMany(targetEntity: MastersServices::class, mappedBy: 'master')]
    private Collection $mastersServices;

    public function __construct()
    {
        $this->id_pets = new ArrayCollection();
        $this->schedules = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->status = Status::Awaiting;
        $this->mastersServices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getAvgRating(): float
    {
        return $this->avgRating;
    }

    public function setAvgRating(float $avgRating): static
    {
        $this->avgRating = $avgRating;
        return $this;
    }

    public function getIdCity(): ?Cities
    {
        return $this->id_city;
    }

    public function getCityId(): ?int
    {
        return $this->id_city ? $this->id_city->getId() : null;
    }

    public function setIdCity(?Cities $id_city): static
    {
        $this->id_city = $id_city;

        return $this;
    }

    public function getDistrict(): ?Districts
    {
        return $this->district;
    }

    public function getDistrictId(): ?int
    {
        return $this->district ? $this->district->getId() : null;
    }

    public function setDistrict(?Districts $district): static
    {
        $this->district = $district;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection<int, Pets>
     */
    public function getIdPets(): Collection
    {
        return $this->id_pets;
    }

    public function getPetsId(): array
    {
        return array_map(fn(Pets $pet) => $pet->getId(), $this->id_pets->toArray());
    }

    public function clearPets(): static
    {
        $this->id_pets->clear();

        return $this;
    }

    public function addIdPet(Pets $idPet): static
    {
        if (!$this->id_pets->contains($idPet)) {
            $this->id_pets->add($idPet);
        }

        return $this;
    }

    public function removeIdPet(Pets $idPet): static
    {
        $this->id_pets->removeElement($idPet);

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * @return Collection<int, Schedule>
     */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function getSchedulesId(): array
    {
        return array_map(fn(Schedule $schedule) => $schedule->getId(), $this->schedules->toArray());
    }

    public function addSchedule(Schedule $schedule): static
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules->add($schedule);
            $schedule->setMaster($this);
        }

        return $this;
    }

    public function removeSchedule(Schedule $schedule): static
    {
        if ($this->schedules->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getMaster() === $this) {
                $schedule->setMaster(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Bookings>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function getBookingsId(): array
    {
        return array_map(fn(Bookings $booking) => $booking->getId(), $this->bookings->toArray());
    }

    public function addBooking(Bookings $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setIdMaster($this);
        }

        return $this;
    }

    public function removeBooking(Bookings $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getIdMaster() === $this) {
                $booking->setIdMaster(null);
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
     * @return Collection<int, MastersServices>
     */
    public function getMastersServices(): Collection
    {
        return $this->mastersServices;
    }

    public function getServicesId(): array
    {
        return array_map(
            fn(MastersServices $ms) => $ms->getService()->getId(),
            $this->mastersServices->toArray()
        );
    }

    public function addMastersService(MastersServices $mastersService): static
    {
        if (!$this->mastersServices->contains($mastersService)) {
            $this->mastersServices->add($mastersService);
            $mastersService->setMaster($this);
        }

        return $this;
    }

    public function removeMastersService(MastersServices $mastersService): static
    {
        if ($this->mastersServices->removeElement($mastersService)) {
            // set the owning side to null (unless already changed)
            if ($mastersService->getMaster() === $this) {
                $mastersService->setMaster(null);
            }
        }

        return $this;
    }

    public function clearServices(): static
    {
        $this->mastersServices->clear();
        return $this;
    }
}
