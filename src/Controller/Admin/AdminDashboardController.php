<?php

namespace App\Controller\Admin;

use App\Entity\Bookings;
use App\Entity\Cities;
use App\Entity\Clients;
use App\Entity\Districts;
use App\Entity\Masters;
use App\Entity\MastersServices;
use App\Entity\Pets;
use App\Entity\Review;
use App\Entity\Schedule;
use App\Entity\Services;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
{
    //#[IsGranted('ROLE_ADMIN')]

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $reviewRepo = $this->entityManager->getRepository(Review::class);
        $latestReviews = $reviewRepo->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'latest_reviews' => $latestReviews,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Groomify');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Bookings', 'fa fa-book', Bookings::class)->setQueryParameter('mercure_topic', '/api/v1/bookings');
        yield MenuItem::linkToCrud('Schedules', 'fa fa-calendar', Schedule::class)->setQueryParameter('mercure_topic', '/api/v1/schedules');
        yield MenuItem::linkToCrud('Cities', 'fa fa-map-marker', Cities::class);
        yield MenuItem::linkToCrud('Districts', 'fa fa-map-signs', Districts::class);
        yield MenuItem::linkToCrud('Clients', 'fa fa-address-book-o', Clients::class);
        yield MenuItem::linkToCrud('Masters', 'fa fa-address-book', Masters::class)->setQueryParameter('mercure_topic', '/api/v1/masters');
        yield MenuItem::linkToCrud('Pets', 'fa fa-paw', Pets::class);
        yield MenuItem::linkToCrud('Services', 'fa fa-money', Services::class);
        yield MenuItem::linkToCrud('MastersServices', 'fa fa-link', MastersServices::class)->setQueryParameter('mercure_topic', '/api/v1/masters_services');
        yield MenuItem::linkToCrud('Reviews', 'fa fa-comment', Review::class);
    }

}
