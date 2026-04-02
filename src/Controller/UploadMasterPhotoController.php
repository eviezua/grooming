<?php

namespace App\Controller;

use App\ApiResource\MastersApi;
use App\Repository\MastersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfonycasts\MicroMapper\MicroMapperInterface;

#[AsController]
class UploadMasterPhotoController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MastersRepository $mastersRepository,
        private MicroMapperInterface $microMapper
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $master = $this->mastersRepository->find($id);
        if (!$master) {
            throw new NotFoundHttpException('Master not found');
        }

        $this->denyAccessUnlessGranted('MASTER_EDIT', $master);

        $file = $request->files->get('file');

        if ($file) {
            $master->setPhotoFile($file);
            $this->entityManager->flush();

            $master->setPhotoFile(null);
            $request->files->remove('file');
        }


        $dto = $this->microMapper->map($master, MastersApi::class);

        return $this->json($dto, 200, [], ['groups' => ['master:read']]);
    }
}