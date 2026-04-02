<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfonycasts\MicroMapper\MicroMapperInterface;

class EntityClassDtoStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)] private ProcessorInterface $persistProcessor,
        #[Autowire(service: RemoveProcessor::class)] private ProcessorInterface $removeProcessor,
        private MicroMapperInterface $microMapper,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {
    }
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $entityClass = $operation->getStateOptions()->getEntityClass();

        if ($operation instanceof DeleteOperationInterface) {
            $this->handleDelete($entityClass, $uriVariables);
            return null;
        }

        if ($operation->getMethod() === 'POST') {
            return $this->createEntity($data, $entityClass);
        }

        if ($operation->getMethod() === 'PUT' || $operation->getMethod() === 'PATCH') {
            if (!isset($uriVariables['id'])) {
                throw new \InvalidArgumentException('ID is required for PUT/PATCH requests');
            }
            $previousData = $context['previous_data'] ?? null;

            return $this->updateEntity($data, $uriVariables, $entityClass, $previousData);
        }

        throw new \InvalidArgumentException("Unsupported method: " . $operation->getMethod());
    }
    private function updateEntity(object $dto, array $uriVariables, string $entityClass): object
    {
        $entity = $this->entityManager->find($entityClass, $uriVariables['id']);

        if (!$entity) {
            throw new NotFoundHttpException("Entity not found");
        }

        if (method_exists($this->microMapper, 'populate')) {
            $this->microMapper->populate($dto, $entity);
        } else {
            $this->microMapper->map($dto, $entityClass, ['target_object' => $entity]);
        }

        $this->entityManager->flush();

        $fullClassName = get_class($entity);

        $shortName = strtolower(substr($fullClassName, strrpos($fullClassName, '\\') + 1));

        $attributeKey = sprintf('_refreshed_%s_entity', $shortName);

        $this->requestStack->getCurrentRequest()?->attributes->set($attributeKey, $entity);

        return $entity;
    }

    private function createEntity(object $data, string $entityClass): object
    {
        try {
            $this->logger->info('Attempting to create new entity', ['entityClass' => $entityClass]);

            $entity = $this->microMapper->map($data, $entityClass);

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->logger->info('Entity created successfully', ['entity' => get_class($entity)]);

            return $entity;
        } catch (\Exception $e) {
            $this->logger->error('Error while creating entity', ['exception' => $e->getMessage()]);
            throw new \RuntimeException('Failed to create entity: ' . $e->getMessage());
        }
    }

    private function loadEntity(mixed $id, string $entityClass): object
    {
        $entity = $this->entityManager->find($entityClass, $id);
        if (!$entity) {
            throw new NotFoundHttpException("Entity of class $entityClass with ID $id not found.");
        }
        return $entity;
    }

    private function handleDelete(string $entityClass, array $uriVariables): void
    {
        if (!isset($uriVariables['id'])) {
            throw new \InvalidArgumentException('ID is required for DELETE requests');
        }

        $entity = $this->loadEntity($uriVariables['id'], $entityClass);
        $this->logger->info('Deleting entity', ['entity' => get_class($entity)]);

        try {
            $this->removeProcessor->process($entity, new class extends Operation implements DeleteOperationInterface {}, $uriVariables, []);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to delete entity: ' . $e->getMessage());
        }
    }
}
