<?php

namespace App\Controller;

use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use App\Entity\Clients;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Services;
use App\Service\EntityLoaderHelper;
use App\Service\TimeFormatter;
use App\Service\ValidationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class CreateBookingWithNewClientController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EntityLoaderHelper $loader,
        private TimeFormatter $timeFormatter,
        private ValidationHelper $validationHelper
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new BookingsApi();
        $dto->masterId = $data['masterId'] ?? null;
        $dto->services = $data['services'] ?? [];
        $dto->date = $data['date'] ?? null;
        $dto->timeStart = $data['timeStart'] ?? null;
        $dto->timeStop = $data['timeStop'] ?? null;
        $dto->petId = $data['petId'] ?? null;
        $dto->clientName = $data['clientName'] ?? null;
        $dto->clientSurname = $data['clientSurname'] ?? null;
        $dto->clientEmail = $data['clientEmail'] ?? null;
        $dto->clientPhone = $data['clientPhone'] ?? null;

        $errors = $this->validationHelper->validate($dto, ['booking:new-client:write']);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $violation) {
                $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }
            return new JsonResponse(['errors' => $messages], 400);
        }

        $this->em->beginTransaction();

        try {
            $client = new Clients();
            $client->setName($data['clientName'] ?? '');
            $client->setSurname($data['clientSurname'] ?? '');
            $client->setEmail($data['clientEmail']);
            $client->setPhone($data['clientPhone'] ?? '');
            $client->addPet($this->loader->load(Pets::class, $data['petId'], 'Pets'));

            $this->em->persist($client);

            $booking = new Bookings();
            $booking->setDate(new \DateTime($data['date']));
            $booking->setTimeStart($this->timeFormatter->parseTime($data['timeStart']));
            $booking->setTimeStop($this->timeFormatter->parseTime($data['timeStop']));

            $booking->setIdClient($client);
            $booking->setIdMaster($this->loader->load(Masters::class, $data['masterId'], 'Masters'));
            $booking->setPet($this->loader->load(Pets::class, $data['petId'], 'Pets'));

            foreach ($this->loader->loadMultiple(Services::class, $data['services'], 'Services') as $service) {
                $booking->addIdService($service);
            }

            $this->em->persist($booking);

            $this->em->flush();
            $this->em->commit();

            return new JsonResponse([
                'status' => 'success',
                'clientId' => $client->getId(),
                'bookingId' => $booking->getId(),
            ], 201);

        } catch (\Throwable $e) {
            $this->em->rollback();
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
