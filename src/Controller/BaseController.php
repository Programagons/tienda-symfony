<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Categoria;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_USER')]
final class BaseController extends AbstractController {

    #[Route('/categorias', name: 'categorias')]
    public function mostrar_categorias(ManagerRegistry $em): Response {
        $categorias = $em->getRepository(Categoria::class)->findAll();
        return $this->render('categorias/mostrar_categorias.html.twig', [
                    'categorias' => $categorias,
        ]);
    }

    #[Route('/productos/{categoria}', name: 'productos')]
    public function mostrar_productos(ManagerRegistry $em, int $categoria): Response {
        $categoriaObjeto = $em->getRepository(Categoria::class)->find($categoria);
        $productos = $categoriaObjeto->getProductos();
        return $this->render('productos/mostrar_productos.html.twig'.[
        'productos' => $productos,
        ]);
    }
}
