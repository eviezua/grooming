<?php
namespace App\EventListener;

use App\Entity\Bookings;
use App\Entity\Masters;
use App\Entity\MastersServices;
use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Update;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
class MercureListener
{
    private LoggerInterface $logger;
    private HubRegistry $hub;

    public function __construct(
        LoggerInterface $logger,
        HubRegistry $hub
    ) {
        $this->logger = $logger;
        $this->hub = $hub;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->handle($args->getObject(), 'create');
    }
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->handle($args->getObject(), 'update');
    }
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->handle($args->getObject(), 'delete');
    }

    public function handle(object $entity, string $operation): void
    {
        $urlSlug = null;
        $entityName = null;

        if ($_ENV['APP_ENV'] === 'test') {
            return;
        }

        if ($entity instanceof Schedule) {
            $urlSlug = 'schedules';
            $entityName = 'schedule';
        } elseif ($entity instanceof Bookings) {
            $urlSlug = 'bookings';
            $entityName = 'bookings';
        } elseif ($entity instanceof Masters) {
            $urlSlug = 'masters';
            $entityName = 'masters';
        } elseif ($entity instanceof MastersServices) {
            $urlSlug = 'masters_services';
            $entityName = 'mastersservices';
        }

        if ($urlSlug === null) {
            return;
        }

        $id = method_exists($entity, 'getId') ? $entity->getId() : null;
        $masterId = method_exists($entity, 'getMaster') ? $entity->getMaster()?->getId() : (method_exists($entity, 'getIdMaster') ? $entity->getIdMaster() : null);

        if ($id === null) {
            return;
        }

        $payload = [
            'entity_type' => $entityName,
            'operation' => $operation,
            'id' => $id,
        ];

        if ($masterId) {
            $payload['master_id'] = $masterId;
        }

        $topic = '/api/v1/' . $urlSlug;
        $update = new Update($topic, json_encode($payload));

        try {
            $result = $this->hub->getHub()->publish($update);

            $this->logger->info("Mercure: Published to {topic}. Message ID: {res}", [
                'topic' => $topic,
                'res' => is_string($result) ? $result : 'OK'
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("Mercure: Failed to publish to {topic}. Error: {message}", [
                'topic' => $topic,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}

