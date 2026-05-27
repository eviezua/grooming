<?php

namespace App\Entity;

use App\Repository\ClientsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientsRepository::class)]
class Clients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $surname = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $telegramChatId = null;

    /**
     * @var Collection<int, Pets>
     */
    #[ORM\ManyToMany(targetEntity: Pets::class)]
    public Collection $pets;

    /**
     * @var Collection<int, Bookings>
     */
    #[ORM\OneToMany(targetEntity: Bookings::class, mappedBy: 'id_client')]
    public Collection $bookings;

    public function __construct()
    {
        $this->pets = new ArrayCollection();
        $this->bookings = new ArrayCollection();
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

    public function getTelegramChatId(): ?string
    {
        return $this->telegramChatId;
    }

    public function setTelegramChatId(?string $telegramTelegramChatId): static
    {
        $this->telegramChatId = $telegramTelegramChatId;

        return $this;
    }

    /**
     * @return Collection<int, Pets>
     */
    public function getPets(): Collection
    {
        return $this->pets;
    }

    public function addPet(Pets $pet): static
    {
        if (!$this->pets->contains($pet)) {
            $this->pets->add($pet);
        }

        return $this;
    }

    public function removePet(Pets $pet): static
    {
        $this->pets->removeElement($pet);

        return $this;
    }
    public function setPets(Collection $pets): static
    {
        $this->pets = $pets;
        return $this;
    }
    public function clearPets(): static
    {
        $this->pets->clear();

        return $this;
    }

    /**
     * @return Collection<int, Bookings>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Bookings $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setIdClient($this);
        }

        return $this;
    }

    public function removeBooking(Bookings $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getIdClient() === $this) {
                $booking->setIdClient(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name . ' ' . $this->surname;
    }
}
