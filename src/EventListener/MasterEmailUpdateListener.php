<?php

namespace App\EventListener;

use App\Entity\Masters;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Cookie\JWTCookieProvider;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
#[AsEventListener(event: KernelEvents::RESPONSE)]
final class MasterEmailUpdateListener
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly JWTCookieProvider $cookieProvider,
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $user = $request->attributes->get('_refreshed_masters_entity');

        if (!$user instanceof Masters || !$response->isSuccessful()) {
            return;
        }

        $jwt = $this->jwtManager->createFromPayload($user, [
            'username' => $user->getUserIdentifier(),
            'roles'    => $user->getRoles(),
        ]);

        $response->headers->setCookie(
            $this->cookieProvider->createCookie($jwt)
        );
    }
}