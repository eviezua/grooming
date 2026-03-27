<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use App\Controller\FindClientByEmailController;
use App\Entity\Clients;
use App\Filter\ClientSearchFilter;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Client',
    description: 'Our dear clients!',
    operations: [
        new Get(security: "is_granted('CLIENT_VIEW', object)"),
        new GetCollection(security: "is_granted('ROLE_MASTER')"),
        new Post(security: "is_granted('ROLE_MASTER')"),
        new Put(security: "is_granted('CLIENT_EDIT', object)"),
        new Patch(security: "is_granted('CLIENT_EDIT', object)"),
        new Get(
            uriTemplate: '/v1/clients/find-by-email',
            controller: FindClientByEmailController::class,
            openapi: new Operation(
                summary: 'Find client by email',
                parameters: [
                    [
                        'name' => 'email',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                        'description' => 'Email to find the client by',
                    ],
                ]
            ),
            security: "is_granted('PUBLIC_ACCESS')",
            read: false,
            deserialize: false,
            name: 'find_by_email'
        )
    ],
    normalizationContext: ['groups' => ['client:read']],
    denormalizationContext: ['groups' => ['client:write']],
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Clients::class)
)]
#[ApiFilter(ClientSearchFilter::class)]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'exact'])]
#[ApiFilter(NumericFilter::class, properties: ['id'])]
class ClientsApi
{
    #[Groups(["client:read"])]
    public ?int $id = null;

    #[Groups(["client:read", "client:write"])]
    #[Assert\NotBlank(message: "Name can't be empty")]
    public ?string $name = null;

    #[Groups(["client:read", "client:write"])]
    #[Assert\NotBlank(message: "Surname can't be empty")]
    public ?string $surname = null;

    #[Groups(["client:read", "client:write"])]
    #[Assert\NotBlank(message: "Email can't be empty")]
    #[Assert\Email(message: "Not a valid email address.")]
    public ?string $email = null;

    #[Groups(["client:read", "client:write"])]
    #[Assert\NotBlank(message: "Phone can't be empty")]
    #[Assert\Regex(pattern: "/^\+?[0-9]{7,15}$/", message: "Invalid phone number.")]
    public ?string $phone = null;

    #[Groups(["client:read", "client:write"])]
    public array $pets = [];

    #[Groups(["client:read"])]
    public array $bookings = [];

}