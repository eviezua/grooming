<?php
namespace App\EventListener;
use App\Entity\Bookings;
use App\Enum\Status;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preUpdate)]
class AddPetForClientAndMasterListener
{
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $booking = $args->getObject();

        if (!$booking instanceof Bookings || $booking->getStatus() !== Status::Approved) {
            return;
        }

        $client = $booking->getIdClient();
        $master = $booking->getIdMaster();
        $pet = $booking->getPet();

        if (!$client || !$pet || !$master) {
            return;
        }

        $client->addPet($pet);
        $master->addIdPet($pet);

        $em = $args->getObjectManager();

        $em->getUnitOfWork()->computeChangeSet(
            $em->getClassMetadata($client::class),
            $client
        );

        $em->getUnitOfWork()->computeChangeSet(
            $em->getClassMetadata($master::class),
            $master
        );
    }
}