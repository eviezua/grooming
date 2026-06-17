<?php
namespace App\EventListener;

use App\Entity\Clients;
use App\Entity\Districts;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Services;
use App\Entity\Cities;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
class ElasticsearchListener
{
    private const ENTITY_MAP = [
        Cities::class => [
            'index' => 'cities',
            'fields' => ['city' => 'getCity']
        ],
        Districts::class => [
            'index' => 'districts',
            'fields' => ['name' => 'getName']
        ],
        Clients::class => [
            'index' => 'clients',
            'fields' => ['name' => 'getName', 'surname' => 'getSurname']
        ],
        Masters::class => [
            'index' => 'masters',
            'fields' => ['name' => 'getName', 'surname' => 'getSurname']
        ],
        Pets::class => [
            'index' => 'pets',
            'fields' => ['breed' => 'getBreed']
        ],
        Services::class => [
            'index' => 'services',
            'fields' => ['name' => 'getName']
        ],
    ];

    public function __construct(
        private readonly Client $client,
        private readonly LoggerInterface $logger
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->index($args->getObject(), 'Create');
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $class = get_class($entity);

        if (!isset(self::ENTITY_MAP[$class])) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($entity);
        $config = self::ENTITY_MAP[$class];

        if (empty($changeSet)) {
            return;
        }

        $shouldUpdateElastic = false;

        foreach ($changeSet as $propertyName => $values) {
            $expectedGetter = 'get' . ucfirst($propertyName);

            if (in_array($expectedGetter, $config['fields'], true)) {
                $shouldUpdateElastic = true;
                break;
            }
        }

        if (!$shouldUpdateElastic) {
            return;
        }

        $this->index($entity, 'Update');
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $class = get_class($entity);

        if (!isset(self::ENTITY_MAP[$class])) {
            return;
        }

        $config = self::ENTITY_MAP[$class];
        $id = method_exists($entity, 'getId') ? $entity->getId() : null;

        if (!$id) {
            return;
        }

        try {
            if ($this->client->indices()->exists(['index' => $config['index']])->asBool()) {
                $this->client->delete([
                    'index' => $config['index'],
                    'id' => $id,
                ]);
                $shortClass = (new \ReflectionClass($entity))->getShortName();
                $this->logger->info("🗑️ [ElasticListener] Successful Pre-Delete for {class} ID {id} from index '{index}'", [
                    'class' => $shortClass,
                    'id' => $id,
                    'index' => $config['index']
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error("Elasticsearch Sync Failed (PreRemove): {msg}", [
                'msg' => $e->getMessage(),
                'index' => $config['index'],
                'id' => $id
            ]);
        }
    }

    private function index(object $entity, string $actionContext): void
    {
        $class = get_class($entity);
        if (!isset(self::ENTITY_MAP[$class])) {
            return;
        }

        $config = self::ENTITY_MAP[$class];
        $id = method_exists($entity, 'getId') ? $entity->getId() : null;

        if (!$id) return;

        $body = [];
        foreach ($config['fields'] as $elasticField => $method) {
            if (method_exists($entity, $method)) {
                $body[$elasticField] = $entity->$method();
            }
        }
        try {
            $this->client->index([
                'index' => $config['index'],
                'id' => $id,
                'body'  => $body,
            ]);
            $shortClass = (new \ReflectionClass($entity))->getShortName();
            $this->logger->info("✅ [ElasticListener] Successful Sync ({context}) for {class} ID {id}", [
                'context' => $actionContext,
                'class' => $shortClass,
                'id' => $id,
                'data' => $body
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("Elasticsearch Sync Failed (Index): {msg}", [
                'msg' => $e->getMessage(),
                'index' => $config['index'],
                'id' => $id
            ]);
        }
    }
}