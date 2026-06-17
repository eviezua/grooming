<?php

namespace App\Security\Voter;

use App\ApiResource\CitiesApi;
use App\ApiResource\DistrictsApi;
use App\ApiResource\PetsApi;
use App\ApiResource\ServicesApi;
use App\Entity\Cities;
use App\Entity\Districts;
use App\Entity\Pets;
use App\Entity\Services;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class AdminWriteVoter extends Voter
{
    public const WRITE_RESOURCE = 'WRITE_APPROVED_RESOURCE';

    public function __construct(private readonly Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== self::WRITE_RESOURCE) {
            return false;
        }

        $restrictedClasses = [
            Cities::class,
            CitiesApi::class,
            Districts::class,
            DistrictsApi::class,
            Pets::class,
            PetsApi::class,
            Services::class,
            ServicesApi::class
        ];

        $class = is_object($subject) ? get_class($subject) : $subject;

        return in_array($class, $restrictedClasses, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return false;
    }
}