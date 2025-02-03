<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ScheduleRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
#[ApiResource(
    description: 'Choose your schedule!',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['schedule:read']],
    denormalizationContext: ['groups' => ['schedule:write']]
)]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["schedule:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["schedule:read", "schedule:write"])]
    private ?string $dayOfweek = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(["schedule:read", "schedule:write"])]
    private ?DateTimeInterface $start_time = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(["schedule:read", "schedule:write"])]
    private ?DateTimeInterface $stop_time = null;

    #[ORM\ManyToOne(inversedBy: 'schedules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Masters $master = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfweek(): ?string
    {
        return $this->dayOfweek;
    }

    public function setDayOfweek(string $dayOfweek): static
    {
        $this->dayOfweek = $dayOfweek;

        return $this;
    }

    public function getStartTime(): ?DateTimeInterface
    {
        return $this->start_time;
    }

    public function setStartTime(DateTimeInterface $start_time): static
    {
        $this->start_time = $start_time;

        return $this;
    }

    public function getStopTime(): ?DateTimeInterface
    {
        return $this->stop_time;
    }

    public function setStopTime(DateTimeInterface $stop_time): static
    {
        $this->stop_time = $stop_time;

        return $this;
    }

    public function getMaster(): ?Masters
    {
        return $this->master;
    }

    public function setMaster(?Masters $master): static
    {
        $this->master = $master;

        return $this;
    }
}
