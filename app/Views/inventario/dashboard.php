<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="summary-grid" aria-label="Resumen">
    <article class="summary-card"><span>Productos activos</span><strong><?= number_format((int) $productos) ?></strong><small>Artículos disponibles en el catálogo</small></article>
    <article class="summary-card"><span>Insumos activos</span><strong><?= number_format((int) $insumos) ?></strong><small>Materias primas y empaques</small></article>
    <article class="summary-card <?= $alertas > 0 ? 'summary-warning' : '' ?>"><span>Alertas de stock</span><strong><?= number_format((int) $alertas) ?></strong><small>Productos por debajo del punto de reposición</small></article>
    <article class="summary-card"><span>Ventas de hoy</span><strong>$ <?= number_format((float) $ventasHoy, 0, ',', '.') ?></strong><small>Solo ventas completadas</small></article>
</section>

<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">REPOSICIÓN</span><h2>Existencias bajas</h2></div><a class="text-link" href="<?= site_url('inventario/alertas') ?>" target="_blank" rel="noopener">Ver alertas</a></div>
    <?php if ($bajoStock === []): ?>
        <p class="empty-state">No hay productos por debajo del punto de reposición.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>SKU</th><th>Producto</th><th>Categoría</th><th>Existencia</th><th>Punto de reposición</th></tr></thead>
            <tbody><?php foreach ($bajoStock as $producto): ?>
                <tr><td><?= esc($producto['sku']) ?></td><td><strong><?= esc($producto['nombre']) ?></strong></td><td><?= esc($producto['categoria']) ?></td><td><?= number_format((float) $producto['stock_actual'], 3) ?> <?= esc($producto['unidad_medida']) ?></td><td><?= number_format((float) $producto['punto_reorden'], 3) ?> <?= esc($producto['unidad_medida']) ?></td></tr>
            <?php endforeach ?></tbody>
        </table></div>
    <?php endif ?>
</section>

<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">ACTIVIDAD</span><h2>Ventas recientes</h2></div><a class="text-link" href="<?= site_url('inventario/ventas') ?>" target="_blank" rel="noopener">Ver ventas</a></div>
    <?php if ($ventasRecientes === []): ?>
        <p class="empty-state">Todavía no hay ventas registradas.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Folio</th><th>Fecha</th><th>Cajero</th><th>Total</th><th>Estado</th></tr></thead>
            <tbody><?php foreach ($ventasRecientes as $venta): ?>
                <tr><td><strong><?= esc($venta['folio']) ?></strong></td><td><?= esc($venta['fecha']) ?></td><td><?= esc(trim($venta['cajero'] . ' ' . $venta['apellido_cajero'])) ?></td><td>$ <?= number_format((float) $venta['total'], 0, ',', '.') ?></td><td><span class="status <?= $venta['estado'] === 'Completada' ? 'status-ok' : 'status-muted' ?>"><?= esc($venta['estado']) ?></span></td></tr>
            <?php endforeach ?></tbody>
        </table></div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
