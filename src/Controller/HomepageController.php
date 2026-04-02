<?php

namespace App\Controller;

use App\Enum\Species;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        return $this->render('pages/homepage.html.twig');
    }
    #[Route('/{_locale<%app.supported_locales%>}/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }
    #[Route('/{_locale<%app.supported_locales%>}/groomers', name: 'app_groomers')]
    public function groomers(): Response
    {
        return $this->render('pages/groomers.html.twig', [
            'speciesList' => Species::values(),
        ]);
    }
    #[Route('/{_locale<%app.supported_locales%>}/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('pages/faq.html.twig');
    }
    #[Route('/{_locale<%app.supported_locales%>}/join', name: 'app_join')]
    public function join(Request $request): Response
    {
        return $this->render('pages/join.html.twig', [
            'initialMode' => $request->query->get('action', 'register')
        ]);
    }
}
