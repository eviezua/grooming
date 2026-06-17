<?php
namespace App\EventListener;

use App\Entity\Cities;
use App\Entity\Masters;
use App\Repository\CitiesRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preRemove)]
class CityRemoveListener
{
    public function __construct(
        private readonly CitiesRepository $citiesRepository
    ) {}

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Cities) {
            return;
        }

        $em = $args->getObjectManager();

        $firstApprovedCity = $this->citiesRepository->findFirstFallbackCity($entity->getId());

        if (!$firstApprovedCity) {
            throw new \RuntimeException(
                "Cannot delete the city. No alternative city found to reassign the active masters."
            );
        }

        $masters = $em->getRepository(Masters::class)->findBy(['id_city' => $entity]);

        foreach ($masters as $master) {
            $master->setIdCity($firstApprovedCity);
            $master->setDistrict(null);
            $em->persist($master);
        }
    }
}