<?php

namespace App\Controller;

use App\Entity\Masters;
use App\Enum\Status;
use App\Message\AdminMessage;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

final class AuthNewClientController extends AbstractController
{
    #[Route('/confirm-email', name: 'app_confirm_email', methods: ['GET'])]
    public function __invoke(
        Request $request,
        #[Target('auth_token_pool')] CacheInterface $authTokenPool,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService,
        MessageBusInterface $bus
    ): Response {
        $token = $request->query->get('token');

        if (!$token) {
            return $this->json(['error' => 'Токен відсутній'], 400);
        }

        $cacheKey = 'auth_token_' . $token;

        $masterId = $authTokenPool->get($cacheKey, function () {
            return null;
        });

        if (!$masterId) {
            return $this->json(['error' => 'Посилання недійсне або термін дії (1 год) вичерпано'], 400);
        }

        $master = $entityManager->getRepository(Masters::class)->find($masterId);

        if (!$master) {
            return $this->json(['error' => 'Майстра не знайдено'], 404);
        }

        if ($master->getStatus() === Status::Awaiting) {
            $isTokenStillValid = $authTokenPool->delete($cacheKey);
            if ($isTokenStillValid) {
                $master->setStatus(Status::Approved);
                $entityManager->flush();

                $bus->dispatch(new AdminMessage([
                    'name' => $master->getName(),
                    'surname' => $master->getSurname(),
                    'email' => $master->getEmail(),
                    'phone' => $master->getPhone() ?? 'не вказано',
                    'city' => $master->getIdCity()?->getCity() ?? 'не вказано',
                    'district' => $master->getDistrict()?->getName() ?? 'не вказано',
                    'status' => $master->getStatus()->value
                ]),
                    [new AmqpStamp('default')]
                );

                return $this->redirectToRoute('app_join', [
                    'action' => 'login',
                    '_locale' => $request->getLocale(),
                    'success' => 1
                ]);
            }
        }

        return $this->redirectToRoute('app_join', [
            'action' => 'login',
            '_locale' => $request->getLocale(),
            'already' => 1
        ]);
    }
}
