<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard('/admin', self::ROUTE)]
final class DashboardController extends AbstractDashboardController
{
    public const ROUTE = 'admin';

    public function index(): Response
    {
        return $this->redirectToRoute('app_homepage');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('DummyJson admin');
    }
}
