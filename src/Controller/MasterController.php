<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MasterController extends AbstractController
{
    #[Route('/profile/account', name: 'app_master_account')]
    public function account(): Response
    {
        return $this->render('pages/master/account.html.twig');
    }

    #[Route('/profile/bookings', name: 'app_master_bookings')]
    public function booking(): Response
    {
        return $this->render('pages/master/bookings.html.twig');
    }

    #[Route('/profile/clients', name: 'app_master_clients')]
    public function client(): Response
    {
        return $this->render('pages/master/clients.html.twig');
    }

    #[Route('/profile/services', name: 'app_master_services')]
    public function service(): Response
    {
        return $this->render('pages/master/services.html.twig');
    }

    #[Route('/profile/schedules', name: 'app_master_schedules')]
    public function schedule(): Response
    {
        return $this->render('pages/master/schedules.html.twig');
    }
}
