<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<form class="data-form" action="<?= site_url('inventario/ventas') ?>" method="post" target="_blank">
    <?= csrf_field() ?>
    <section class="panel">
        <div class="form-grid">
            <label>Cliente<select name="cliente_id">
                <option value="">Consumidor final</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= (int) $cliente['id'] ?>" <?= (string) old('cliente_id') === (string) $cliente['id'] ? 'selected' : '' ?>><?= esc($cliente['nombre']) ?></option>
                <?php endforeach ?>
            </select></label>
            <label>Método de pago<select name="metodo_pago" required>
                <option value="Efectivo">Efectivo</option><option value="Tarjeta">Tarjeta</option><option value="Transferencia">Transferencia</option><option value="Mixto">Mixto</option>
            </select></label>
            <label>Monto recibido (efectivo/mixto)<input type="number" name="monto_recibido" min="0" step="0.01" value="<?= esc(old('monto_recibido', '0')) ?>"></label>
            <label>Promoción (opcional)<select name="codigo_promocion"><option value="">Sin promoción</option>
                <?php foreach ($promociones as $promocion): ?>
                    <option value="<?= esc($promocion['codigo'], 'attr') ?>" <?= old('codigo_promocion') === $promocion['codigo'] ? 'selected' : '' ?>><?= esc($promocion['codigo'] . ' — ' . $promocion['descripcion']) ?></option>
                <?php endforeach ?>
            </select></label>
        </div>
        <p class="muted">Tirilla interna de venta. No es una factura electrónica ni sustituye la validación tributaria.</p>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><span class="eyebrow">CATÁLOGO</span><h2>Elige cantidades</h2></div></div>
        <?php if ($productos === []): ?>
            <p class="empty-state">No hay productos de venta activos.</p>
        <?php else: ?>
            <div class="sale-products">
                <?php foreach ($productos as $producto): ?>
                    <label class="sale-product">
                        <span><strong><?= esc($producto['nombre']) ?></strong><small><?= esc($producto['sku']) ?> · $ <?= number_format((float) $producto['precio_venta'], 0, ',', '.') ?> · Stock <?= number_format((float) $producto['stock_actual'], 3) ?> <?= esc($producto['unidad_medida']) ?></small></span>
                        <input type="number" name="productos[<?= (int) $producto['id'] ?>]" min="0" max="<?= esc((string) $producto['stock_actual']) ?>" step="0.001" value="0" aria-label="Cantidad de <?= esc($producto['nombre']) ?>">
                    </label>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
    <div class="form-actions"><button class="button" type="submit">Registrar venta y generar tirilla</button><a class="button button-outline" href="<?= site_url('inventario/ventas') ?>" target="_blank" rel="noopener">Ver ventas</a></div>
</form>
<?= $this->endSection() ?>
