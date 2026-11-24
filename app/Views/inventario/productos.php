<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<form class="filters" action="<?= site_url('inventario/productos') ?>" method="get" target="_blank">
    <label class="filter-search">Buscar por nombre, SKU o descripción
        <input type="search" name="q" value="<?= esc($busqueda) ?>" placeholder="Ej. empanada o EMP-CAR">
    </label>
    <label>Tipo
        <select name="tipo">
            <option value="todos" <?= $tipo === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="venta" <?= $tipo === 'venta' ? 'selected' : '' ?>>Productos de venta</option>
            <option value="insumos" <?= $tipo === 'insumos' ? 'selected' : '' ?>>Insumos</option>
        </select>
    </label>
    <label>Categoría
        <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int) $categoria['id'] ?>" <?= $categoriaId === (string) $categoria['id'] ? 'selected' : '' ?>><?= esc($categoria['nombre']) ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <button class="button" type="submit">Filtrar</button>
</form>
<section class="panel">
    <?php if (in_array((int) session()->get('rol_id'), [1, 2, 4], true)): ?>
        <div class="panel-heading">
            <div><span class="eyebrow">CATÁLOGO</span><h2>Administrar existencias</h2></div>
            <div class="action-links">
                <a class="button" href="<?= site_url('inventario/productos/nuevo') ?>" target="_blank" rel="noopener">Nuevo producto</a>
                <a class="button button-outline" href="<?= site_url('inventario/stock/nuevo') ?>" target="_blank" rel="noopener">Registrar movimiento</a>
            </div>
        </div>
    <?php endif ?>
    <?php if ($productos === []): ?>
        <p class="empty-state">No se encontraron productos con esos filtros.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>SKU</th><th>Producto</th><th>Categoría</th><th>Tipo</th><?php if (in_array((int) session()->get('rol_id'), [1, 2, 4], true)): ?><th>Costo</th><?php endif ?><th>Venta</th><th>Stock</th><th>Acciones</th></tr></thead>
            <tbody><?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?= esc($producto['sku']) ?></td>
                    <td><strong><?= esc($producto['nombre']) ?></strong><small class="cell-subtitle"><?= esc($producto['descripcion']) ?></small></td>
                    <td><?= esc($producto['categoria']) ?></td>
                    <td><?= (int) $producto['es_insumo'] === 1 ? 'Insumo' : 'Venta' ?></td>
                    <?php if (in_array((int) session()->get('rol_id'), [1, 2, 4], true)): ?><td>$ <?= number_format((float) $producto['precio_costo'], 0, ',', '.') ?></td><?php endif ?>
                    <td><?= (float) $producto['precio_venta'] > 0 ? '$ ' . number_format((float) $producto['precio_venta'], 0, ',', '.') : '—' ?></td>
                    <td><?= number_format((float) $producto['stock_actual'], 3) ?> <?= esc($producto['unidad_medida']) ?></td>
                    <td>
                        <?php if (in_array((int) session()->get('rol_id'), [1, 2, 4], true)): ?>
                            <a class="text-link" href="<?= site_url('inventario/productos/editar/' . (int) $producto['id']) ?>" target="_blank" rel="noopener">Editar</a>
                        <?php else: ?>
                            —
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?></tbody>
        </table></div>
        <div class="pager-wrap"><?= $pager ?></div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
