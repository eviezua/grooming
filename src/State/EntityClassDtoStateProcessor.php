<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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

            return $this->updateEntity($data, $uriVariables, $entityClass);
        }

        throw new \InvalidArgumentException("Unsupported method: " . $operation->getMethod());
    }
    private function updateEntity(object $dto, array $uriVariables, string $entityClass): object
    {
        $entity = $this->loadEntity($uriVariables['id'], $entityClass);

        foreach (get_object_vars($dto) as $property => $value) {
            if ($value !== null && property_exists($entity, $property)) {
                $setter = 'set' . ucfirst($property);
                $getter = 'get' . ucfirst($property);

                if (is_array($value) && method_exists($entity, $getter)) {
                    $collection = new ArrayCollection($this->loadRelatedEntities($entityClass, $property, $value));
                    $clearMethod = method_exists($entity, 'clear' . ucfirst($property)) ? 'clear' . ucfirst($property) : null;

                    if ($clearMethod) {
                        $entity->$clearMethod();
                    }
                    if (method_exists($entity, $setter)) {
                        $entity->$setter($collection);
                    }
                } elseif (method_exists($entity, $setter)) {
                    $entity->$setter($value);
                }
            }
        }

        $this->entityManager->flush();

        return $entity;
    }
    private function loadRelatedEntities(string $entityClass, string $property, array $ids): array
    {
        $associationMapping = $this->entityManager->getClassMetadata($entityClass)->getAssociationMappings();

        if (!isset($associationMapping[$property])) {
            throw new \RuntimeException("Property '$property' is not a valid association in '$entityClass'.");
        }

        $targetEntity = $associationMapping[$property]['targetEntity'];

        return $this->entityManager->getRepository($targetEntity)->findBy(['id' => $ids]);
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
