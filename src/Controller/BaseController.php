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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[IsGranted('ROLE_USER')]
final class BaseController extends AbstractController {
#PRUEBA

    #Mostrar categorías
    #[Route('/categorias', name: 'categorias')]
    public function mostrar_categorias(ManagerRegistry $em): Response {
        #Obtenemos las categorías de la base de datos
        $categorias = $em->getRepository(Categoria::class)->findAll();
        #Renderizamos la vista 
        return $this->render('categorias/mostrar_categorias.html.twig', [
                    'categorias' => $categorias,
        ]);
    }

    #Mostrar productos
    #[Route('/productos/{categoria}', name: 'productos')]
    public function mostrar_productos(ManagerRegistry $em, int $categoria): Response {
        # Buscamos la categoría por el id
        $categoriaObjeto = $em->getRepository(Categoria::class)->find($categoria);
        # Sacamos los productos de esa categoría
        $productos = $categoriaObjeto->getProductos();
        # Renderizamos
        return $this->render('productos/mostrar_productos.html.twig', [
                    'productos' => $productos,
        ]);
    }

    # WIP Mostrar detalles del producto
    #[Route('{producto}/detalles', name: 'detalles')]
    public function mostrar_detalles(): Response {
        
    }

    # Añadir productos a la cesta
    #[Route('/anadir', name: 'anadir')]
    public function anadir_productos(EntityManagerInterface $em, Request $request, Cestacompra $cesta): Response {
        #Recogemos los datos de entrada
        $productos_id = $request->request->all("productos_id");
        $unidades = $request->request->all("unidades");
        #Vamos a obtener un array de objetos producto a través del id
        $productos = $em->getRepository(Producto::class)->findBy(['id' => $productos_id]);
        #Cargamos los productos en la sesión
        $cesta->cargar_productos($productos, $unidades);
        $valores_productos = array_values($productos);

        #Sacamos la categoría del producto 
        #$categoria_id = $valores_productos[0] -> getCategoria() -> getId();

        #Redirigimos a la listsa de productos de la categoría del primer producto
        return $this->redirectToRoute('productos', ['categoria' => $valores_productos[0]->getCategoria()->getId()]);
    }

    #Mostramos el contenido de la cesta
    #[Route('/cesta', name: 'cesta')]
    public function cesta(CestaCompra $cesta) {
        return $this->render('cesta/mostrar_cesta.html.twig', [
                    'productos' => $cesta->get_Productos(),
                    'unidades' => $cesta->get_Unidades()
        ]);
    }

    # Eliminar o reducir producto
    #[Route('/eliminar', name: 'eliminar')]
    public function eliminar(Request $request, CestaCompra $cesta) {
        # Recogemos los datos de entrada (los valores de la petición post)
        $producto_id = $request->request->get("producto_id");
        $unidades = $request->request->get("unidades");
        # Eliminamos el producto de la cesta
        $cesta->eliminar_producto($producto_id, $unidades);
        # Volvemos a la cesta 
        return $this->redirectToRoute('cesta');
    }

    # Generamos un pedido con los datos de la cesta
    #[Route('/pedido', name: 'pedido')]
    public function pedido(EntityManagerInterface $em, CestaCompra $cesta, MailerInterface $mailer) {

        # Variables
        $error = 0;
        # Obtenemos productos de la sesión
        $productos = $cesta->get_Productos();
        $unidades = $cesta->get_Unidades();

        # Si la cesta está vacía, como el codigo no puede ser null, nos daría error
        $pedido = $pedido ?? null;

        # Flag para los errores
        if (count($productos) == 0) {
            # Error = 1 cuando NO hay productos en la cesta
            $error = 1;
        } else {
            # Generamos un nuevo objeto pedido con sus setters
            $pedido = new Pedido();

            # Setteamos el coste del pedido
            $pedido->setCoste($cesta->calcular_coste());

            #Setteamos la fecha del pedido
            $pedido->setFecha(new \Datetime());

            # Setteamos el usuario
            $pedido->setUsuario($this->getUser());

            # Lo insertamos en la base de datos
            $em->persist($pedido);

            foreach ($productos as $codigo_producto => $productoCesta) {
                # Cargamos los pedidos en pedidoproducto
                $pedidoProducto = new PedidoProducto();
                $pedidoProducto->setPedido($pedido);
                $producto = $em->getRepository(Producto::class)->findBy(['id' => $productoCesta->getId()])[0];
                $pedidoProducto->setProducto($producto);
                $pedidoProducto->setUnidades($unidades[$codigo_producto]);

                $em->persist($pedidoProducto);
            }
            try {
                #Guardamos todo con flush
                $em->flush();
            } catch (Exception $ex) {
                $error = 2;
            }


            if (!$error) {
                # Obtenemos el id del usuario desde la sesión
                $usuario_id = $this->getUser()->getId();
                $usuario = $em->getRepository(Usuario::class)->find($usuario_id);
                # Así sacamos el email del usuario
                $destination_email = $usuario->getEmail();

                # Mandamos el correo al email del usuario

                $email = (new TemplatedEmail())
                        ->from(new Address('programagons@gmail.com', 'Tienda Symfony'))
                        ->to(new Address($destination_email))
                        ->subject('Confirmación de pedido #' . $pedido->getId())
                        # indicamos la ruta de la plantilla
                        ->htmlTemplate('correo.html.twig')
                        ->locale('es')
                        # pasamos variables (clave => valor) a la plantilla
                        ->context([
                            'pedido_id' => $pedido->getId(),
                            'productos' => $cesta->get_Productos(),
                            'unidades' => $cesta->get_Unidades(),
                            'coste' => $cesta->calcular_coste(),
                ]);
                $mailer->send($email);
            }
        }

        return $this->render('pedido/pedido.html.twig', [
                    'error' => $error,
                    'pedido_id' => $pedido ? $pedido->getId() : null,
        ]);
    }

    #[Route('/historial', name: 'historial')]
    public function historial(EntityManagerInterface $em): Response {
        # Obtenemos el usuario logueado
        $usuario = $this->getUser();

        # Buscamos los pedidos del usuario ordenados por fecha de forma descendente
        $pedidos = $em->getRepository(Pedido::class)->findBy(
                ['Usuario' => $usuario],
                ['fecha' => 'DESC']
        );

        # Pasamos la información a la plantilla
        return $this->render('pedido/historial.html.twig', [
                    'pedidos' => $pedidos
        ]);
    }
}
