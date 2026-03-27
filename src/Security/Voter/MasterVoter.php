<?php

namespace App\Security\Voter;

use App\ApiResource\MastersApi;
use App\Entity\Bookings;
use App\Entity\Masters;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class MasterVoter extends Voter
{
    public const EDIT = 'MASTER_EDIT';

    public function __construct(
        private Security $security
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::EDIT && $subject instanceof MastersApi;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Masters) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $dto = $subject;

        return $dto->id !== null && (int)$dto->id === (int)$user->getId();
    }
}