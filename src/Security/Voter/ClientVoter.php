<?php

namespace App\Security\Voter;

use App\ApiResource\ClientsApi;
use App\Entity\Bookings;
use App\Entity\Masters;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ClientVoter extends Voter
{
    public const VIEW = 'CLIENT_VIEW';
    public const EDIT = 'CLIENT_EDIT';

    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT]) && $subject instanceof ClientsApi;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Masters) return false;

        if ($this->security->isGranted('ROLE_ADMIN')) return true;

        if ($attribute === self::EDIT) return false;

        if ($attribute === self::VIEW) {
        $user = $token->getUser();

        $hasBooking = $this->entityManager->getRepository(Bookings::class)
            ->findOneBy([
                'id_master' => $user,
                'id_client' => $subject->id
            ]);

        return $hasBooking !== null;
        }

        return false;
    }
}
