<?php

namespace App\Controller\Admin;

use App\Entity\Image;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SectionMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AdminDashboard(routePath: '/old-dashboard', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private readonly ProductRepository $productRepository)
    {
    }

    #[Route(name: 'app_homepage')]
    public function index(): Response
    {
//        return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
//         return $this->redirectToRoute('admin_image_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
         return $this->render('app/index.html.twig', [
             'products' => $this->productRepository->findBy([], [], 10),
         ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Dummy');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        foreach ([Product::class, Image::class] as $entityClass) {
            $label = new \ReflectionClass($entityClass)->getShortName();
            yield MenuItem::linkToCrud($label, 'fas fa-list', $entityClass)
                ->setBadge($this->entityManager->getRepository($entityClass)->count([]));
        }
        foreach (['meili:schema:validate', 'meili:schema:update', 'meili:index'] as $commandName) {
            yield MenuItem::linkToUrl($commandName, 'fas fa-list', $this->urlGenerator->generate('survos_command', ['commandName' => $commandName]))
                ->setLinkTarget('_blank');
            ;
        }
        yield MenuItem::linkToUrl('Commands', 'fas fa-list', $this->urlGenerator->generate('survos_commands'))
            ->setLinkTarget('_blank');
        ;
        yield MenuItem::linkToRoute('meili-admin', null, 'meili_admin');



    }
}
