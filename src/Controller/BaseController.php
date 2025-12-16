<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Categoria;
use App\Entity\Producto;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Services\CestaCompra;

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
        return $this->render('productos/mostrar_productos.html.twig', [
                    'productos' => $productos,
        ]);
    }

    #[Route('{producto}/detalles', name: 'detalles')]
    public function mostrar_detalles(): Response {
        
    }

    #[Route('/anadir', name: 'anadir')]
    public function anadir_productos(EntityManagerInterface $em, Request $request, Cestacompra $cesta): Response {
        //Recogemos los datos de entrada
        $productos_id = $request->request->all("productos_id");
        $unidades = $request->request->all("unidades");
        //Vamos a obtener un array de objetos producto a través del id
        $productos = $em->getRepository(Producto::class)->findBy(['id' => $productos_id]);
        //Cargamos los productos en la sesión
        $cesta->cargar_productos($productos, $unidades);
        $valores_productos = array_values($productos);
        
        //Sacamos la categoría del producto 
        //$categoria_id = $valores_productos[0] -> getCategoria() -> getId();
        
        return $this->redirectToRoute('productos', ['categoria' => $valores_productos[0]->getCategoria()->getId()]);
    }

    #[Route('/cesta', name: 'cesta')]
    public function cesta(CestaCompra $cesta) {
        return $this->render('cesta/mostrar_cesta.html.twig', [
                    'productos' => $cesta->get_Productos(),
                    'unidades' => $cesta->get_Unidades()
        ]);
    }
    
    #[Route('/eliminar', name: 'eliminar')]
    public function eliminar(Request $request, CestaCompra $cesta){
        // Recogemos los datos de entrada (los valores de la petición post)
        $producto_id = $request->request->get("producto_id");
        $unidades = $request->request->get("unidades");
        $cesta->eliminar_producto($producto_id, $unidades);
        return $this->redirectToRoute('cesta');
    }
}
