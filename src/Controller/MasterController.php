<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MasterController extends AbstractController
{
    #[Route('/{_locale<%app.supported_locales%>}/profile/account', name: 'app_master_account')]
    public function account(): Response
    {
        return $this->render('pages/master/account.html.twig');
    }

    #[Route('/{_locale<%app.supported_locales%>}/profile/bookings', name: 'app_master_bookings')]
    public function booking(): Response
    {
        return $this->render('pages/master/bookings.html.twig');
    }

    #[Route('/{_locale<%app.supported_locales%>}/profile/clients', name: 'app_master_clients')]
    public function client(): Response
    {
        return $this->render('pages/master/clients.html.twig');
    }

    #[Route('/{_locale<%app.supported_locales%>}/profile/services', name: 'app_master_services')]
    public function service(): Response
    {
        return $this->render('pages/master/services.html.twig');
    }

    #[Route('/{_locale<%app.supported_locales%>}/profile/schedules', name: 'app_master_schedules')]
    public function schedule(): Response
    {
        return $this->render('pages/master/schedules.html.twig');
    }
}
