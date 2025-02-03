<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ClientsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ClientsRepository::class)]
#[ApiResource(
    description: 'Our dear clients!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
    ],
    normalizationContext: ['groups' => ['client:read']],
    denormalizationContext: ['groups' => ['client:write']]
)]
class Clients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["client:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client:read", "client:write"])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client:read", "client:write"])]
    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    #[Groups(["client:read", "client:write"])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["client:read", "client:write"])]
    private ?string $phone = null;

    /**
     * @var Collection<int, Pets>
     */
    #[ORM\ManyToMany(targetEntity: Pets::class)]
    #[Groups(["client:read", "client:write"])]
    private Collection $pets;

    /**
     * @var Collection<int, Bookings>
     */
    #[ORM\OneToMany(targetEntity: Bookings::class, mappedBy: 'id_client')]
    #[Groups(["client:read"])]
    private Collection $bookings;

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
            // set the owning side to null (unless already changed)
            if ($booking->getIdClient() === $this) {
                $booking->setIdClient(null);
            }
        }

        return $this;
    }
}
