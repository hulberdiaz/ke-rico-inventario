<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel">
    <?php if (in_array((int) session()->get('rol_id'), [1, 2, 3], true)): ?>
        <div class="panel-heading"><div><span class="eyebrow">PUNTO DE VENTA</span><h2>Registrar operación</h2></div><a class="button" href="<?= site_url('inventario/ventas/nueva') ?>" target="_blank" rel="noopener">＋ Nueva venta</a></div>
    <?php endif ?>
    <?php if ($ventas === []): ?>
        <p class="empty-state">No hay ventas registradas.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Folio</th><th>Fecha</th><th>Cajero</th><th>Cliente</th><th>Pago</th><th>Total</th><th>Estado</th></tr></thead>
            <tbody><?php foreach ($ventas as $venta): ?>
                <tr>
                    <td><strong><?= esc($venta['folio']) ?></strong><br><?php if (! empty($venta['comprobante_id'])): ?><a class="text-link" href="<?= site_url('inventario/comprobantes/' . (int) $venta['comprobante_id']) ?>" target="_blank" rel="noopener">Ver tirilla</a><?php endif ?></td><td><?= esc($venta['fecha']) ?></td>
                    <td><?= esc(trim($venta['cajero'] . ' ' . $venta['apellido_cajero'])) ?></td>
                    <td><?= esc($venta['cliente'] ?: 'Venta general') ?></td><td><?= esc($venta['metodo_pago']) ?></td>
                    <td>$ <?= number_format((float) $venta['total'], 0, ',', '.') ?></td>
                    <td><span class="status <?= $venta['estado'] === 'Completada' ? 'status-ok' : 'status-muted' ?>"><?= esc($venta['estado']) ?></span></td>
                </tr>
            <?php endforeach ?></tbody>
        </table></div>
        <div class="pager-wrap"><?= $pager ?></div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
