<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<form class="data-form" action="<?= site_url('inventario/compras') ?>" method="post" target="_blank">
    <?= csrf_field() ?>
    <section class="panel">
        <div class="form-grid">
            <label>Proveedor<select name="proveedor_id" required><option value="">Selecciona un proveedor</option>
                <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?= (int) $proveedor['id'] ?>"><?= esc($proveedor['nombre_empresa']) ?></option>
                <?php endforeach ?>
            </select></label>
            <label class="wide-field">Observaciones<textarea name="observaciones" maxlength="255" rows="2"><?= esc(old('observaciones')) ?></textarea></label>
        </div>
        <p class="muted">Esta compra se registra como recibida; al guardar se actualizan las existencias y el costo promedio.</p>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><span class="eyebrow">RECEPCIÓN</span><h2>Productos comprados</h2></div></div>
        <?php if ($productos === []): ?>
            <p class="empty-state">No hay productos activos para recibir.</p>
        <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>SKU / Producto</th><th>Unidad</th><th>Stock actual</th><th>Cantidad recibida</th><th>Costo unitario</th></tr></thead>
                <tbody><?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><strong><?= esc($producto['nombre']) ?></strong><small class="cell-subtitle"><?= esc($producto['sku']) ?></small></td>
                        <td><?= esc($producto['unidad_medida']) ?></td><td><?= number_format((float) $producto['stock_actual'], 3) ?></td>
                        <td><input class="quantity-input" type="number" name="productos[<?= (int) $producto['id'] ?>]" min="0" step="0.001" value="0" aria-label="Cantidad recibida de <?= esc($producto['nombre']) ?>"></td>
                        <td><input class="quantity-input" type="number" name="costos[<?= (int) $producto['id'] ?>]" min="0" step="0.01" value="<?= esc((string) $producto['precio_costo']) ?>" aria-label="Costo unitario de <?= esc($producto['nombre']) ?>"></td>
                    </tr>
                <?php endforeach ?></tbody>
            </table></div>
        <?php endif ?>
    </section>
    <div class="form-actions"><button class="button" type="submit">Registrar compra recibida</button><a class="button button-outline" href="<?= site_url('inventario/compras') ?>" target="_blank" rel="noopener">Ver compras</a></div>
</form>
<?= $this->endSection() ?>
