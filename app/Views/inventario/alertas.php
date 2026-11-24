<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel">
    <?php if ($productos === []): ?>
        <p class="empty-state">No hay productos con alertas de stock.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Estado</th><th>SKU</th><th>Producto</th><th>Categoría</th><th>Existencia</th><th>Punto de reposición</th></tr></thead>
            <tbody><?php foreach ($productos as $producto): ?>
                <tr>
                    <td><span class="status <?= $producto['estado_stock'] === 'Agotado' || $producto['estado_stock'] === 'Crítico' ? 'status-danger' : 'status-warning' ?>"><?= esc($producto['estado_stock']) ?></span></td>
                    <td><?= esc($producto['sku']) ?></td><td><strong><?= esc($producto['nombre']) ?></strong></td><td><?= esc($producto['categoria']) ?></td>
                    <td><?= number_format((float) $producto['stock_actual'], 3) ?> <?= esc($producto['unidad_medida']) ?></td>
                    <td><?= number_format((float) $producto['punto_reorden'], 3) ?> <?= esc($producto['unidad_medida']) ?></td>
                </tr>
            <?php endforeach ?></tbody>
        </table></div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
