<?php

namespace App\Services;

use App\Entity\Producto;
use Symfony\Component\HttpFoundation\RequestStack;

class CestaCompra {

    protected $requestStack;
    protected $cesta;
    protected $productos;
    protected $unidades;

    public function _construct(RequestStack $requestStack) {
        $this->$requestStack = $requestStack;
    }

    protected function carga_cesta() {
        $sesion = $this->requestStack->getSession();
        // Si hay productos en la sesión los cargamos en los atributos del objeto
        if ($sesion->has("productos") && $sesion->has("unidades")) {
            $this->productos = $sesion->get("productos");
            $this->unidades = $sesion->get("unidades");
        } else {
            $this->productos = [];
            $this->unidades = [];
        }
    }

    protected function guardar_cesta() {
        $sesion = $this->requestStack->getSession();
        $sesion->set($this->productos);
        $sesion->set($this->unidades);
    }

    public function get_Productos() {
        $this->carga_cesta();
        return $this->productos;
    }

    public function get_Unidades() {
        $this->carga_cesta();
        return $this->unidades;
    }

    public function cargar_productos($productos, $unidades) {
        //Cargamos los productos en la sesión
        for ($i = 0; $i < count($productos); $i++) {
            if ($unidades[$i] != 0) {
                $this->cargar_producto($productos[$i], $unidades[$i]);
            }
        }
    }

    // Recibe como parámetro un objeto tipo Producto
    public function cargar_producto($producto, $unidades) {
        $this->carga_cesta(); //Cargamos la cesta
        // Ahora podemos utilizar productos y unidades
        $codigo = $producto->getCod(); //Recibe el objeto de tipo Producto
        //Cargamos el código del producto a la cesta
        //Si existe el producto, cargamos las unidades a la cesta
        if (array_key_exists($codigo, $this->productos)) {
            //Buscamos la posición de los productos y en esa posición sumamos unidades
            $codigos_productos = array_keys($this->productos);
            $posicion = array_search($codigo, $codigos_productos);
            $unidades[$posicion] += $unidades;
        } else {
            $productos[] = ['$codigo' => $producto];
            $unidades[] = [$unidades];
        }


        $this->guardar_cesta();
    }
}
