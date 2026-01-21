<?php

namespace App\Services;

use App\Entity\Producto;
use Symfony\Component\HttpFoundation\RequestStack;

class CestaCompra {

    protected $requestStack;
    protected $cesta;
    protected $productos;
    protected $unidades;

    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
    }

    //Recupera el array de productos y unidades de la sesión
    protected function cargar_cesta() {
        $session = $this->requestStack->getSession();
        // Si hay productos en la sesión los cargamos en los atributos del objeto
        if ($session->has('productos') && $session->has('unidades')) {
            $this->productos = $session->get('productos');
            $this->unidades = $session->get('unidades');
        } else {
            $this->productos = [];
            $this->unidades = [];
        }
    }

    protected function guardar_cesta() {
        $sesion = $this->requestStack->getSession();
        $sesion->set('productos',$this->productos);
        $sesion->set('unidades',$this->unidades);
    }

    public function get_Productos() {
        $this->cargar_cesta();
        return $this->productos;
    }

    public function get_Unidades() {
        $this->cargar_cesta();
        return $this->unidades;
    }

    //Recibe los productos y unidades del formulario como parámetros
    public function cargar_productos($productos, $unidades) {
        $this->cargar_cesta(); //Cargamos la cesta
        //Cargamos los productos en la sesión
        for ($i = 0; $i < count($productos); $i++) {
            if ($unidades[$i] != 0) {
                //carga un producto en la sesión
                $this->cargar_producto($productos[$i], $unidades[$i]);
            }
        }
        $this->guardar_cesta();
    }

    // Recibe como parámetro un objeto tipo Producto
    public function cargar_producto($producto, $unidad) {
        // Ahora podemos utilizar productos y unidades
        $codigo = $producto->getCod(); //Recibe el objeto de tipo Producto
        //Cargamos el código del producto a la cesta
        //Si existe el producto, cargamos las unidades a la cesta
        if (array_key_exists($codigo, $this->productos)) { // Está en la cesta
            //Le sumamos las unidades
            $this->unidades[$codigo] += $unidad;
        } else if($unidad != 0) { // Si no está en la cesta, lo agregamos con las unidades(si tiene)
            $this->productos[$codigo] = $producto;
            $this->unidades[$codigo] = $unidad;
        }
    }
    
    public function eliminar_producto($codigo_producto, $unidades){
        // Cargar la sesión de la cesta
        $this->cargar_cesta();
        // Si el producto está en nuestros productos
        if (array_key_exists($codigo_producto, $this->productos)){
            // Le restamos las unidades
            $this->unidades[$codigo_producto] -= $unidades;
            // Si al restar las unidades, se quedan en negativo o 0, lo sacamos de la cesta
            if ($this->unidades[$codigo_producto] <= 0){
                unset($this->unidades[$codigo_producto]);
                unset($this->productos[$codigo_producto]);
            }
            $this->guardar_cesta();
        }
    }
    
    //Vamos a calcular el coste de la cesta al completo
    public function calcular_coste($unidades, $producto){
        // Variable para el coste
        $costeTotal = 0;
        //Array de objetos, vamos sacando por cada producto
        
        foreach ($this->productos as $codigo_producto => $producto) {
            // Multiplicamos el precio del producto por sus unidades
            // Sumamos al coste total todo lo que obtenemos
            $costeTotal += $producto -> getPrecio() * $this->unidades[$codigo_producto];
        }
        return $costeTotal;
    }
}
