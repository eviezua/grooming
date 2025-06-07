<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityLoaderHelper
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * Load entity by its class and id
     *
     * @param string $class
     * @param mixed $id
     * @param string $entityName
     * @return object
     * @throws NotFoundHttpException
     */
    public function load(string $class, mixed $id, string $entityName): object
    {
        $entity = $this->entityManager->getRepository($class)->find($id);
        if (!$entity) {
            throw new NotFoundHttpException(sprintf('%s with ID %s not found.', $entityName, $id));
        }
        return $entity;
    }
    public function loadMultiple(string $entityClass, array $ids, string $name = 'Entities'): array
    {
        $entities = $this->entityManager->getRepository($entityClass)->findBy(['id' => $ids]);

        if (empty($entities)) {
            throw new NotFoundHttpException("No valid $name found for given IDs.");
        }

        return $entities;
    }
}
