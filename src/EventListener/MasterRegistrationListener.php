<?php

namespace App\EventListener;

use App\Entity\Masters;
use App\Message\AuthMessage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Masters::class)]
class MasterRegistrationListener
{
    public function __construct(
        private MessageBusInterface $bus,
        #[Target('auth_token_pool')] private CacheInterface $authTokenPool
    ) {
    }

    public function postPersist(Masters $master): void
    {
        $token = bin2hex(random_bytes(32));

        $cacheKey = 'auth_token_' . $token;

        $this->authTokenPool->get($cacheKey, function () use ($master) {
            return $master->getId();
        });

        $this->bus->dispatch(new AuthMessage($master->getId(), $token),
            [new AmqpStamp('auth')]
        );
    }
}
