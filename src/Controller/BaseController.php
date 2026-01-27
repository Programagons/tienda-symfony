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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use App\Entity\Pedido;
use App\Entity\Usuario;
use App\Entity\PedidoProducto;
use Symfony\Component\HttpFoundation\Request;
use App\Services\CestaCompra;
use Symfony\Bridge\Twig\mime\TemplatedEmail;

#[IsGranted('ROLE_USER')]
final class BaseController extends AbstractController {
#PRUEBA

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
    public function eliminar(Request $request, CestaCompra $cesta) {
        // Recogemos los datos de entrada (los valores de la petición post)
        $producto_id = $request->request->get("producto_id");
        $unidades = $request->request->get("unidades");
        $cesta->eliminar_producto($producto_id, $unidades);
        return $this->redirectToRoute('cesta');
    }

    #[Route('/pedido', name: 'pedido')]
    public function pedido(ManagerRegistry $em, CestaCompra $cesta) {

// Obtenemos productos de la sesión
        $productos = $cesta->get_Productos();
        $unidades = $cesta->get_Unidades();

        // Flag para los errores
        if (count($productos) == 0) {
            // Error = 1 cuando NO hay productos en la cesta
            $error = 1;
        } else {
            // Generamos un nuevo objeto pedido con sus setters
            $pedido = new Pedido();

            // Setteamos el coste del pedido
            $pedido->setCoste($cesta->calcular_coste($unidades, $productos));

            //Setteamos la fecha del pedido
            $pedido->setFecha(new \Datetime());

            // Setteamos el usuario
            $pedido->setUsuario($this->getUser());

            // Lo insertamos en la base de datos
            $em->persist($pedido);

            foreach ($this->productos as $codigo_producto => $productoCesta) {
                // Cargamos los pedidos en pedidoproducto
                $pedidoProducto = new PedidoProducto();
                $pedidoProducto->setPedido($pedido);
                $producto = $em->getRepository(Producto::class)->findBy(['id' => $productoCesta->getId()])[0];
                $pedidoProducto->setProducto($producto);
                $pedidoProducto->setUnidades($unidades[$codigo_producto]);

                $em->persist($pedidoProducto);
            }
            try {
                //Guardamos todo con flush
                $em->flush();
                $pedido_id = $pedido->getId();
            } catch (Exception $ex) {
                dd($ex->getMessage());
                $error = 2;
            }
            

            if (!error) {
                // Obtenemos el id del usuario desde la sesión
                $usuario_id = $this->getUser()->getUserIdentifier();
                $usuario = $em->getRepository(Usuario::class)->find($usuario_id);
                // Así sacamos el email del usuario
                $destination_email = $usuario->getEmail();

                // Mandamos el correo al email del usuario
                
                $email = (new TemplatedEmail())
                        ->from('programagons@gmail.com')
                        ->to(new Address($destination_email))
                        ->subject('Confirmación de pedido' . $pedido->getId())

                        // indicamos la ruta de la plantilla
                        ->htmlTemplate('correo.html.twig')
                        ->locale('es')
                        // pasamos variables (clave => valor) a la plantilla
                        ->context([
                            'pedido_id' => $pedido->getId(), 'productos' => $cesta->get_Productos(), 'unidades' => $cesta->get_Unidades(),
                            'coste' => $cesta->calcular_coste(),
                        ])
                ;
                $mailer->send($email);
            }
        }

        return $this->render('pedido.html.twig', [
                    'error' => $error,
                    'pedido_id' => $pedido->getId()
        ]);
    }
}
