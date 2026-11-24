<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel form-panel">
    <p class="muted">El stock se administra desde movimientos para conservar el historial del inventario.</p>
    <form class="data-form" action="<?= esc($action, 'attr') ?>" method="post" target="_blank">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>SKU<input name="sku" maxlength="30" value="<?= esc(old('sku', $producto['sku'] ?? '')) ?>" required></label>
            <label>Nombre<input name="nombre" maxlength="120" value="<?= esc(old('nombre', $producto['nombre'] ?? '')) ?>" required></label>
            <label>Categoría<select name="categoria_id" required><option value="">Selecciona</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id'] ?>" <?= (string) old('categoria_id', $producto['categoria_id'] ?? '') === (string) $categoria['id'] ? 'selected' : '' ?>><?= esc($categoria['nombre']) ?></option>
                <?php endforeach ?>
            </select></label>
            <label>Proveedor<select name="proveedor_id"><option value="">Sin proveedor</option>
                <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?= (int) $proveedor['id'] ?>" <?= (string) old('proveedor_id', $producto['proveedor_id'] ?? '') === (string) $proveedor['id'] ? 'selected' : '' ?>><?= esc($proveedor['nombre_empresa']) ?></option>
                <?php endforeach ?>
            </select></label>
            <label>Unidad de medida<input name="unidad_medida" maxlength="20" value="<?= esc(old('unidad_medida', $producto['unidad_medida'] ?? 'unidad')) ?>" required></label>
            <label>Precio de costo<input type="number" name="precio_costo" min="0" step="0.01" value="<?= esc(old('precio_costo', $producto['precio_costo'] ?? '0.00')) ?>" required></label>
            <label>Precio de venta<input type="number" name="precio_venta" min="0" step="0.01" value="<?= esc(old('precio_venta', $producto['precio_venta'] ?? '0.00')) ?>" required></label>
            <label>IVA (%)<input type="number" name="iva_porcentaje" min="0" max="100" step="0.01" value="<?= esc(old('iva_porcentaje', $producto['iva_porcentaje'] ?? '0.00')) ?>" required></label>
            <label>Punto de reposición<input type="number" name="punto_reorden" min="0" step="0.001" value="<?= esc(old('punto_reorden', $producto['punto_reorden'] ?? '0.000')) ?>" required></label>
            <label>Stock máximo<input type="number" name="stock_maximo" min="0" step="0.001" value="<?= esc(old('stock_maximo', $producto['stock_maximo'] ?? '0.000')) ?>" required></label>
            <label>Tipo<select name="es_insumo"><option value="0" <?= (string) old('es_insumo', $producto['es_insumo'] ?? '0') === '0' ? 'selected' : '' ?>>Producto de venta</option><option value="1" <?= (string) old('es_insumo', $producto['es_insumo'] ?? '') === '1' ? 'selected' : '' ?>>Insumo</option></select></label>
            <label>Ruta de imagen (opcional)<input name="imagen" maxlength="180" value="<?= esc(old('imagen', $producto['imagen'] ?? '')) ?>"></label>
            <label class="wide-field">Descripción<textarea name="descripcion" maxlength="255" rows="3"><?= esc(old('descripcion', $producto['descripcion'] ?? '')) ?></textarea></label>
        </div>
        <div class="form-actions"><button class="button" type="submit">Guardar producto</button><a class="button button-outline" href="<?= site_url('inventario/productos') ?>" target="_blank" rel="noopener">Volver a productos</a></div>
    </form>
</section>
<?= $this->endSection() ?>
