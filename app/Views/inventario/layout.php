<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($titulo ?? 'Inventario') ?> - Ke-Rico</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/inventario.css') ?>">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="<?= site_url('inventario') ?>" target="_blank" rel="noopener"><span class="brand-mark">K</span><span>Ke-Rico <small>Inventario</small></span></a>
        <nav class="main-nav" aria-label="Navegación principal">
            <a href="<?= site_url('inventario') ?>" target="_blank" rel="noopener">Resumen</a>
            <a href="<?= site_url('inventario/productos') ?>" target="_blank" rel="noopener">Productos</a>
            <a href="<?= site_url('inventario/categorias') ?>" target="_blank" rel="noopener">Categorías</a>
            <a href="<?= site_url('inventario/alertas') ?>" target="_blank" rel="noopener">Stock</a>
            <a href="<?= site_url('inventario/ventas') ?>" target="_blank" rel="noopener">Ventas</a>
            <a href="<?= site_url('inventario/clientes') ?>" target="_blank" rel="noopener">Clientes</a>
            <?php if (in_array((int) session()->get('rol_id'), [1, 2, 4], true)): ?>
                <a href="<?= site_url('inventario/compras') ?>" target="_blank" rel="noopener">Compras</a>
                <a href="<?= site_url('inventario/proveedores') ?>" target="_blank" rel="noopener">Proveedores</a>
                <a href="<?= site_url('inventario/movimientos') ?>" target="_blank" rel="noopener">Movimientos</a>
            <?php endif ?>
            <?php if (in_array((int) session()->get('rol_id'), [1, 2], true)): ?>
                <a href="<?= site_url('inventario/promociones') ?>" target="_blank" rel="noopener">Promociones</a>
                <a href="<?= site_url('inventario/reportes') ?>" target="_blank" rel="noopener">Reportes</a>
                <a href="<?= site_url('inventario/bitacora') ?>" target="_blank" rel="noopener">Bitácora</a>
            <?php endif ?>
            <?php if ((int) session()->get('rol_id') === 1): ?>
                <a href="<?= site_url('inventario/usuarios') ?>" target="_blank" rel="noopener">Usuarios</a>
                <a href="<?= site_url('inventario/respaldos') ?>" target="_blank" rel="noopener">Respaldos</a>
                <a href="<?= site_url('inventario/configuracion') ?>" target="_blank" rel="noopener">Configuración</a>
            <?php endif ?>
        </nav>
        <div class="user-menu">
            <span><?= esc((string) session()->get('nombre')) ?></span>
            <?php if (in_array((int) session()->get('rol_id'), [1, 2, 4], true)): ?>
                <a href="<?= site_url('inventario/productos/nuevo') ?>" target="_blank" rel="noopener">＋ Producto</a>
                <a href="<?= site_url('inventario/stock/nuevo') ?>" target="_blank" rel="noopener">＋ Ajuste</a>
                <a href="<?= site_url('inventario/compras/nueva') ?>" target="_blank" rel="noopener">＋ Compra</a>
            <?php endif ?>
            <?php if (in_array((int) session()->get('rol_id'), [1, 2, 3], true)): ?>
                <a href="<?= site_url('inventario/ventas/nueva') ?>" target="_blank" rel="noopener">＋ Venta</a>
            <?php endif ?>
            <a href="<?= site_url('logout') ?>" data-same-tab>Salir</a>
        </div>
    </header>
    <main class="page">
        <div class="page-heading">
            <div><span class="eyebrow">SISTEMA DE INVENTARIO</span><h1><?= esc($titulo ?? 'Inventario') ?></h1></div>
            <span class="user-role"><?= esc((string) session()->get('cargo')) ?></span>
        </div>
        <?php if ($mensaje = session()->getFlashdata('error')): ?>
            <div class="notice notice-error"><?= esc($mensaje) ?></div>
        <?php endif ?>
        <?php if ($mensaje = session()->getFlashdata('success')): ?>
            <div class="notice notice-success"><?= esc($mensaje) ?></div>
        <?php endif ?>
        <?= $this->renderSection('content') ?>
    </main>
</body>
</html>
