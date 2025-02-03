<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ServicesRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ServicesRepository::class)]
#[ApiResource(
    description: 'Set up your services!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch()
    ],
    normalizationContext: ['groups' => ['services:read']],
    denormalizationContext: ['groups' => ['services:write']]
)]
class Services
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["services:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["services:read", "services:write"])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(["services:read", "services:write"])]
    private ?int $cost = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(["services:read", "services:write"])]
    private ?DateTimeImmutable $default_time = null;

    /**
     * @var Collection<int, Masters>
     */
    #[ORM\ManyToMany(targetEntity: Masters::class, mappedBy: 'id_services')]
    #[Groups(["services:read"])]
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
}
