<?php

namespace App\Security;

use App\Entity\Masters;
use App\Enum\Status;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user){
        if (!$user instanceof Masters){
            return;
        }
        match ($user->getStatus()){
            Status::Approved => null,
            Status::Awaiting => throw new CustomUserMessageAccountStatusException('User is awaiting approval.'),
            Status::Inactive => throw new CustomUserMessageAccountStatusException('User is inactive.'),
            Status::Rejected => throw new CustomUserMessageAccountStatusException('User is rejected.'),
            default => throw new CustomUserMessageAccountStatusException('Unknown status.'),
        };
    }
    public function checkPostAuth(UserInterface $user){

    }
}