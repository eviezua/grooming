<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class MeController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        if ($user instanceof InMemoryUser) {
            return $this->json([
                'id' => -1,
                'roles' => $user->getRoles()
            ]);
        }

        return $this->json([
            'id' => $user->getId(),
            'roles' => $user->getRoles()
        ]);
    }
}