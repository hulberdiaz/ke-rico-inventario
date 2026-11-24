<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::index');
$routes->get('login', 'Auth::index');
$routes->post('login/ingresar', 'Auth::ingresar');
$routes->get('logout', 'Auth::salir');

$routes->group('', ['filter' => 'auth'], static function ($routes): void {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('inventario', 'Inventario::index');
    $routes->get('inventario/productos', 'Inventario::productos');
    $routes->get('inventario/categorias', 'Inventario::categorias');
    $routes->get('inventario/alertas', 'Inventario::alertas');
    $routes->get('inventario/ventas', 'Inventario::ventas');
    $routes->get('inventario/comprobantes/(:num)', 'Inventario::comprobante/$1');
    $routes->get('inventario/movimientos', 'Inventario::movimientos', ['filter' => 'roles:1,2,4']);
    $routes->get('inventario/proveedores', 'Inventario::proveedores', ['filter' => 'roles:1,2,4']);
    $routes->get('inventario/clientes', 'Inventario::clientes');
    $routes->get('inventario/compras', 'Inventario::compras', ['filter' => 'roles:1,2,4']);
    $routes->get('inventario/promociones', 'Inventario::promociones', ['filter' => 'roles:1,2']);
    $routes->get('inventario/reportes', 'Inventario::reportes', ['filter' => 'roles:1,2']);
    $routes->get('inventario/bitacora', 'Inventario::bitacora', ['filter' => 'roles:1,2']);
    $routes->get('inventario/usuarios', 'Inventario::usuarios', ['filter' => 'roles:1']);
    $routes->get('inventario/respaldos', 'Inventario::respaldos', ['filter' => 'roles:1']);
    $routes->get('inventario/configuracion', 'Inventario::configuracion', ['filter' => 'roles:1']);
    $routes->post('inventario/configuracion', 'Operaciones::guardarConfiguracion', ['filter' => 'roles:1']);

    $routes->get('inventario/usuarios/nuevo', 'Operaciones::nuevoUsuario', ['filter' => 'roles:1']);
    $routes->post('inventario/usuarios', 'Operaciones::guardarUsuario', ['filter' => 'roles:1']);
    $routes->get('inventario/productos/nuevo', 'Operaciones::nuevoProducto', ['filter' => 'roles:1,2,4']);
    $routes->post('inventario/productos', 'Operaciones::guardarProducto', ['filter' => 'roles:1,2,4']);
    $routes->get('inventario/productos/editar/(:num)', 'Operaciones::editarProducto/$1', ['filter' => 'roles:1,2,4']);
    $routes->post('inventario/productos/(:num)', 'Operaciones::actualizarProducto/$1', ['filter' => 'roles:1,2,4']);
    $routes->get('inventario/catalogos/(:segment)/nuevo', 'Operaciones::nuevoCatalogo/$1', ['filter' => 'roles:1,2,3,4']);
    $routes->post('inventario/catalogos/(:segment)', 'Operaciones::guardarCatalogo/$1', ['filter' => 'roles:1,2,3,4']);
    $routes->get('inventario/stock/nuevo', 'Operaciones::nuevoAjuste', ['filter' => 'roles:1,2,4']);
    $routes->post('inventario/stock', 'Operaciones::guardarAjuste', ['filter' => 'roles:1,2,4']);
    $routes->get('inventario/ventas/nueva', 'Operaciones::nuevaVenta', ['filter' => 'roles:1,2,3']);
    $routes->post('inventario/ventas', 'Operaciones::registrarVenta', ['filter' => 'roles:1,2,3']);
    $routes->get('inventario/compras/nueva', 'Operaciones::nuevaCompra', ['filter' => 'roles:1,2,4']);
    $routes->post('inventario/compras', 'Operaciones::registrarCompra', ['filter' => 'roles:1,2,4']);
});