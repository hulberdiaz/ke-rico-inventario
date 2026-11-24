<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="summary-grid">
    <article class="summary-card"><span>Valor estimado del inventario</span><strong>$ <?= number_format((float) ($inventario['valor'] ?? 0), 0, ',', '.') ?></strong><small>Existencias multiplicadas por costo</small></article>
    <article class="summary-card"><span>Productos e insumos activos</span><strong><?= number_format((int) ($inventario['cantidad'] ?? 0)) ?></strong><small>Sin incluir registros inactivos o eliminados</small></article>
    <article class="summary-card"><span>Ventas completadas este mes</span><strong><?= number_format((int) ($ventasMes['cantidad'] ?? 0)) ?></strong><small>Total: $ <?= number_format((float) ($ventasMes['total'] ?? 0), 0, ',', '.') ?></small></article>
</section>
<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">ÚLTIMOS 30 DÍAS</span><h2>Ventas por día</h2></div></div>
    <?php if ($ventasDia === []): ?>
        <p class="empty-state">No hay ventas completadas en este período.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Fecha</th><th>Número de ventas</th><th>Total vendido</th></tr></thead>
            <tbody><?php foreach ($ventasDia as $fila): ?>
                <tr><td><?= esc($fila['dia']) ?></td><td><?= number_format((int) $fila['cantidad']) ?></td><td>$ <?= number_format((float) $fila['total'], 0, ',', '.') ?></td></tr>
            <?php endforeach ?></tbody>
        </table></div>
    <?php endif ?>
</section>
<p class="muted">Reporte informativo interno; no sustituye reportes contables ni documentos fiscales oficiales.</p>
<?= $this->endSection() ?>
