<?php

namespace App\Controller;

use App\Repository\ClientsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class FindClientByEmailController extends AbstractController
{
    public function __construct(private ClientsRepository $clientsRepository) {}

    public function __invoke(Request $request): JsonResponse
    {
        $email = $request->query->get('email');

        if (!$email) {
            return $this->json(['error' => 'Email is required'], 400);
        }

        $client = $this->clientsRepository->findOneBy(['email' => $email]);

        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        return $this->json(['id' => $client->getId()]);
    }
}
