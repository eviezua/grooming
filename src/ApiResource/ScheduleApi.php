<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Schedule;
use App\Enum\Weekdays;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Schedule',
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
    denormalizationContext: ['groups' => ['schedule:write']],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Schedule::class)
)]
#[ApiFilter(NumericFilter::class, properties: ['master.id'])]
#[ApiFilter(SearchFilter::class, properties: ['dayOfweek' => 'exact'])]
class ScheduleApi
{
    #[Groups(["schedule:read"])]
    public ?int $id = null;

    #[Groups(["schedule:read", "schedule:write"])]
    #[Assert\Choice(callback: [Weekdays::class, 'values'])]
    public ?string $dayOfweek = null;

    #[Groups(["schedule:read", "schedule:write"])]
    #[Assert\NotBlank(message: "Start time cannot be empty.")]
    #[Assert\Regex(pattern: "/^\d{2}:\d{2}:\d{2}$/", message: "The start time must be in the format HH:MM:SS.")]
    public ?string $start_time = null;

    #[Groups(["schedule:read", "schedule:write"])]
    #[Assert\NotBlank(message: "Stop time cannot be empty.")]
    #[Assert\Regex(pattern: "/^\d{2}:\d{2}:\d{2}$/", message: "The stop time must be in the format HH:MM:SS.")]
    public ?string $stop_time = null;

    #[Groups(["schedule:read", "schedule:write"])]
    #[Assert\NotBlank(message: "Master ID cannot be empty.")]
    public ?int $masterId = null;
}