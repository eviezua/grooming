<?php

namespace App\Security\Voter;

use App\ApiResource\ScheduleApi;
use App\Entity\Masters;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ScheduleVoter extends Voter
{
    public const EDIT = 'SCHEDULE_EDIT';
    public const CREATE = 'SCHEDULE_CREATE';
    public const DELETE = 'SCHEDULE_DELETE';
    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::CREATE, self::DELETE])
            && $subject instanceof ScheduleApi;
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

        if ($attribute === self::CREATE) {
            if ($subject->masterId !== $user->getId()) {
                return false;
            }

            return true;
        }

        return $subject->masterId === $user->getId();
    }
}