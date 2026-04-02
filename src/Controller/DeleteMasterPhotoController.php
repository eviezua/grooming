<?php

namespace App\Controller;
use App\ApiResource\MastersApi;
use App\Repository\MastersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfonycasts\MicroMapper\MicroMapperInterface;

class DeleteMasterPhotoController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MastersRepository $mastersRepository,
        private MicroMapperInterface $microMapper
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        $master = $this->mastersRepository->find($id);

        if (!$master) {
            throw new NotFoundHttpException('Master not found');
        }

        $this->denyAccessUnlessGranted('MASTER_EDIT', $master);

        $master->setPhotoFile(null);
        $master->setPhoto(null);

        $this->entityManager->flush();

        $dto = $this->microMapper->map($master, MastersApi::class);

        return $this->json($dto, 200, [], ['groups' => ['master:read']]);
    }
}