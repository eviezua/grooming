<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use App\Entity\Clients;
use App\Entity\Masters;
use App\Entity\Pets;
use App\Entity\Services;
use App\Service\BookingValidationService;
use App\Service\EntityLoaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfonycasts\MicroMapper\MicroMapperInterface;

class BookingStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingValidationService $validationService,
        private MicroMapperInterface $microMapper,
        private EntityLoaderHelper $entityLoaderHelper,
        private ValidatorInterface $validator
    ) {
    }
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof BookingsApi) {
            return $data;
        }

        return match (true) {
            $operation instanceof Post => $this->handleCreate($data),
            $operation instanceof Put, $operation instanceof Patch => $this->handleUpdate($data, $uriVariables['id'], $operation instanceof Put),
            $operation instanceof Delete => $this->handleDelete($uriVariables['id']),
            default => $data,
        };
    }

    private function handleCreate(BookingsApi $data): BookingsApi
    {
        $booking = new Bookings();
        return $this->saveBooking($data, $booking, true);
    }

    private function handleUpdate(BookingsApi $data, int $id, bool $isPut): BookingsApi
    {
        $booking = $this->entityLoaderHelper->load(Bookings::class, $id, 'Booking');
        return $this->saveBooking($data, $booking, $isPut);
    }

    private function handleDelete(int $id): void
    {
        $booking = $this->entityLoaderHelper->load(Bookings::class, $id, 'Booking');

        try {
            $this->entityManager->remove($booking);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            throw new BadRequestHttpException('Failed to delete booking.');
        }
    }

    private function saveBooking(BookingsApi $data, Bookings $booking, bool $isPut): BookingsApi
    {
        $updatedFields = [];

        $this->updateEntityField($booking, 'masterId', Masters::class, 'setIdMaster', $data, $updatedFields, $isPut);
        $this->updateEntityField($booking, 'petId', Pets::class, 'setPet', $data, $updatedFields, $isPut, true);
        $this->updateEntityField($booking, 'clientId', Clients::class, 'setIdClient', $data, $updatedFields, $isPut, true);
        $this->updateServices($booking, $data->services ?? [], $updatedFields, $isPut);
        $this->updateDateField($booking, 'date', 'setDate', $data, $updatedFields, $isPut);
        $this->updateDateField($booking, 'timeStart', 'setTimeStart', $data, $updatedFields, $isPut);
        $this->updateDateField($booking, 'timeStop', 'setTimeStop', $data, $updatedFields, $isPut);

        $this->validationService->validateBooking($booking, $updatedFields);

        $violations = $this->validator->validate($booking);
        if (count($violations) > 0) {
            throw new BadRequestHttpException('Validation failed: ' . (string) $violations);
        }

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $this->microMapper->map($booking, BookingsApi::class);
    }

    private function updateEntityField(Bookings $booking, string $field, string $entityClass, string $setter, BookingsApi $data, array &$updatedFields, bool $isPut, bool $required = false): void
    {
        if ($isPut || isset($data->$field)) {
            $entity = isset($data->$field) ? $this->entityLoaderHelper->load($entityClass, $data->$field, ucfirst($field)) : null;

            if ($required && $entity === null) {
                throw new BadRequestHttpException("$field is required and must be valid.");
            }

            $booking->$setter($entity);
            $updatedFields[] = $field;
        }
    }

    private function updateDateField(Bookings $booking, string $field, string $setter, BookingsApi $data, array &$updatedFields, bool $isPut): void
    {
        if ($isPut || !empty($data->$field)) {
            $dateValue = null;

            if ($data->$field instanceof \DateTimeInterface) {
                $dateValue = $data->$field instanceof \DateTimeImmutable
                    ? \DateTime::createFromImmutable($data->$field)
                    : $data->$field;
            } elseif (is_string($data->$field)) {
                if ($field === 'timeStart' || $field === 'timeStop') {
                    if (!empty($data->date)) {
                        $date = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $data->date);
                        if ($date === false) {
                            throw new BadRequestHttpException("Invalid date format for 'date'. Expected 'Y-m-d\TH:i:sP'.");
                        }

                        $timeValue = \DateTime::createFromFormat('H:i:s', $data->$field);
                        if ($timeValue === false) {
                            throw new BadRequestHttpException("Invalid time format for $field. Expected 'H:i:s'.");
                        }

                        $dateValue = $date->setTime($timeValue->format('H'), $timeValue->format('i'), $timeValue->format('s'));

                        $dateValue->setTimezone(new \DateTimeZone('UTC'));

                    } else {
                        throw new BadRequestHttpException("'date' must be provided for $field.");
                    }
                } else {
                    $dateValue = \DateTime::createFromFormat('Y-m-d H:i:s', $data->$field);
                    if ($dateValue === false) {
                        throw new BadRequestHttpException("Invalid date format for $field. Expected 'Y-m-d H:i:s'.");
                    }
                }
            }

            if ($dateValue) {
                $booking->$setter($dateValue);
                $updatedFields[] = $field;
            } else {
                throw new BadRequestHttpException("Date for $field cannot be null or empty.");
            }
        }
    }

    private function updateServices(Bookings $booking, array $serviceIds, array &$updatedFields, bool $isPut): void
    {
        if ($isPut || !empty($serviceIds)) {
            $newServices = $this->entityLoaderHelper->loadMultiple(Services::class, $serviceIds, 'Services');

            if ($booking->getIdServices()->toArray() !== $newServices) {
                $booking->getIdServices()->clear();
                foreach ($newServices as $service) {
                    $booking->addIdService($service);
                }
                $updatedFields[] = 'services';
            }
        }
    }
}
