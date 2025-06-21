<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomepageController extends AbstractController
{
    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('app_homepage', ['_locale' => 'ua']);
    }
    #[Route('/{_locale<%app.supported_locales%>}/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }
}
