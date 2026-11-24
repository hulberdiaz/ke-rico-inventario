<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel form-panel">
    <p class="muted">Solo usuarios administradores pueden guardar estos valores.</p>
    <form class="data-form" action="<?= site_url('inventario/configuracion') ?>" method="post" target="_blank">
        <?= csrf_field() ?>
        <div class="form-grid">
            <?php foreach ($ajustes as $ajuste): ?>
                <?php if (in_array($ajuste['clave'], ['comprobante_consecutivo'], true)) { continue; } ?>
                <label class="wide-field"><?= esc($ajuste['descripcion'] ?: $ajuste['clave']) ?>
                    <?php if (in_array($ajuste['tipo'], ['texto', 'booleano', 'json'], true)): ?>
                        <input type="text" name="ajustes[<?= esc($ajuste['clave'], 'attr') ?>]" value="<?= esc($ajuste['valor'], 'attr') ?>">
                    <?php elseif ($ajuste['tipo'] === 'entero'): ?>
                        <input type="number" step="1" min="0" name="ajustes[<?= esc($ajuste['clave'], 'attr') ?>]" value="<?= esc($ajuste['valor'], 'attr') ?>">
                    <?php else: ?>
                        <input type="number" step="0.01" name="ajustes[<?= esc($ajuste['clave'], 'attr') ?>]" value="<?= esc($ajuste['valor'], 'attr') ?>">
                    <?php endif ?>
                    <small class="cell-subtitle"><?= esc($ajuste['clave']) ?></small>
                </label>
            <?php endforeach ?>
        </div>
        <div class="form-actions"><button class="button" type="submit">Guardar configuración</button></div>
    </form>
</section>
<?= $this->endSection() ?>
