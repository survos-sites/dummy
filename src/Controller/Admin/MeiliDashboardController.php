<?php

namespace App\Controller\Admin;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Survos\MeiliBundle\Service\MeiliService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AdminDashboard(routePath: '/ez-meili', routeName: 'meili_admin')]
class MeiliDashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private MeiliService $meiliService,
    )
    {
    }

    public function index(): Response
    {
//        return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
         return $this->redirectToRoute('meili_admin_meili_index');
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
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Meili');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Meili Overview', 'fas fa-list',
            $this->urlGenerator->generate('meili_admin_meili_index'))
            ->setLinkTarget('_blank');
        ;
//        foreach ($this->meiliService->indexedEntities as $indexName)
        foreach ($this->meiliService->indexedByClass() as $class=>$indexes) {
            foreach ($indexes as $indexName => $setting) {
                {
                    $shortName = new \ReflectionClass($class)->getShortName();
                    yield MenuItem::linkToRoute($indexName . " index", 'fas fa-list',
                        'meili_admin_meili_show_index', routeParameters: ['indexName' => $indexName]);
//                    yield MenuItem::linkToRoute($indexName . " setting", 'fas fa-cog',)
                }

            }
        }



//        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
