<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Entity\Categoria;
use App\Entity\Producto;
use App\Entity\Usuario;
use App\Entity\Pedido;


#[AdminDashboard(routePath: '/dashboard', routeName: 'dashboard')]
class DashboardController extends AbstractDashboardController {

    public function index(): Response {
        // return parent::index();
        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(CategoriaCrudController::class)->generateUrl());

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

    # Configuración del dashboard 
    public function configureDashboard(): Dashboard {
        return Dashboard::new()
                        ->setTitle('Tienda de Libros y Cómics')
                        ->disableUrlSignatures();
    }

    # Enlaces a los demás dashboards
    public function configureMenuItems(): iterable {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Categorías', 'fas fa-list', Categoria::class);
        yield MenuItem::linkToCrud('Productos', 'fas fa-list', Producto::class);
        yield MenuItem::linkToCrud('Usuarios', 'fas fa-list', Usuario::class);
        yield MenuItem::linkToCrud('Pedidos', 'fas fa-list', Pedido::class);
    }
}
