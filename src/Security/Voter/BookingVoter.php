<?php

namespace App\Security\Voter;

use App\ApiResource\BookingsApi;
use App\Entity\Bookings;
use App\Entity\Masters;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class BookingVoter extends Voter
{
    public const CREATE = 'BOOKING_CREATE';
    public const VIEW = 'BOOKING_VIEW';
    public const EDIT = 'BOOKING_EDIT';
    public const DELETE = 'BOOKING_DELETE';

    public function __construct(
        private Security $security
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof BookingsApi;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $dto = $subject;

        if ($attribute === self::CREATE) {
            if (!$user instanceof Masters) {
                return true;
            }

            if ($this->security->isGranted('ROLE_ADMIN')) {
                return true;
            }

            if ($dto->masterId !== null && $dto->masterId !== $user->getId()) {
                return false;
            }

            return true;
        }

        if (!$user instanceof Masters) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return $dto->masterId === $user->getId();
    }
}