<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel">
    <?php if ($movimientos === []): ?>
        <p class="empty-state">No hay movimientos de inventario registrados.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Motivo</th><th>Cantidad</th><th>Antes</th><th>Después</th><th>Usuario</th></tr></thead>
            <tbody><?php foreach ($movimientos as $movimiento): ?>
                <tr>
                    <td><?= esc($movimiento['creado_en']) ?></td><td><?= esc($movimiento['tipo']) ?></td>
                    <td><strong><?= esc($movimiento['producto']) ?></strong><small class="cell-subtitle"><?= esc($movimiento['sku']) ?></small></td>
                    <td><?= esc($movimiento['motivo']) ?></td>
                    <td><?= number_format((float) $movimiento['cantidad'], 3) ?> <?= esc($movimiento['unidad_medida']) ?></td>
                    <td><?= number_format((float) $movimiento['stock_anterior'], 3) ?></td>
                    <td><?= number_format((float) $movimiento['stock_nuevo'], 3) ?></td>
                    <td><?= esc(trim($movimiento['usuario'] . ' ' . $movimiento['apellido_usuario'])) ?></td>
                </tr>
            <?php endforeach ?></tbody>
        </table></div>
        <div class="pager-wrap"><?= $pager ?></div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
