<?php

namespace App\Entity;

use App\Enum\Weekdays;
use App\Repository\ScheduleRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, enumType: Weekdays::class)]
    private ?Weekdays $dayOfweek = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?DateTimeInterface $start_time = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?DateTimeInterface $stop_time = null;

    #[ORM\ManyToOne(inversedBy: 'schedules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Masters $master = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfweek(): ?Weekdays
    {
        return $this->dayOfweek;
    }

    public function setDayOfweek(Weekdays $dayOfweek): static
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

  //  #[Groups(["schedule:read"])]
    public function getMasterId(): ?int
    {
        return $this->master ? $this->master->getId() : null;
    }

    public function setMaster(?Masters $master): static
    {
        $this->master = $master;

        return $this;
    }
}
