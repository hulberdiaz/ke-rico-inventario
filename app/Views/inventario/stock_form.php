<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel form-panel">
    <p class="muted">Entrada y devolución suman stock; salida y merma lo restan. En “Ajuste” la cantidad será el nuevo stock contado.</p>
    <form class="data-form" action="<?= site_url('inventario/stock') ?>" method="post" target="_blank">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Producto<select name="producto_id" required><option value="">Selecciona</option>
                <?php foreach ($productos as $producto): ?>
                    <option value="<?= (int) $producto['id'] ?>" <?= (string) old('producto_id') === (string) $producto['id'] ? 'selected' : '' ?>><?= esc($producto['sku'] . ' — ' . $producto['nombre']) ?> (stock <?= number_format((float) $producto['stock_actual'], 3) ?>)</option>
                <?php endforeach ?>
            </select></label>
            <label>Tipo de movimiento<select name="tipo" required>
                <option value="Entrada" <?= old('tipo') === 'Entrada' ? 'selected' : '' ?>>Entrada</option><option value="Salida" <?= old('tipo') === 'Salida' ? 'selected' : '' ?>>Salida</option><option value="Ajuste" <?= old('tipo') === 'Ajuste' ? 'selected' : '' ?>>Ajuste de conteo</option><option value="Merma" <?= old('tipo') === 'Merma' ? 'selected' : '' ?>>Merma</option><option value="Devolucion" <?= old('tipo') === 'Devolucion' ? 'selected' : '' ?>>Devolución</option>
            </select></label>
            <label>Cantidad<input type="number" name="cantidad" min="0.001" step="0.001" value="<?= esc(old('cantidad')) ?>" required></label>
            <label>Motivo<input name="motivo" maxlength="120" value="<?= esc(old('motivo')) ?>" required></label>
            <label class="wide-field">Observaciones<textarea name="observaciones" maxlength="255" rows="3"><?= esc(old('observaciones')) ?></textarea></label>
        </div>
        <div class="form-actions"><button class="button" type="submit">Registrar movimiento</button><a class="button button-outline" href="<?= site_url('inventario/movimientos') ?>" target="_blank" rel="noopener">Ver movimientos</a></div>
    </form>
</section>
<?= $this->endSection() ?>
