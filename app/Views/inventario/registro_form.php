<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel form-panel">
    <form class="data-form" action="<?= site_url('inventario/catalogos/' . rawurlencode($tipo)) ?>" method="post" target="_blank">
        <?= csrf_field() ?>
        <div class="form-grid">
            <?php foreach ($campos as $campo): ?>
                <?php $valor = old($campo['name'], $campo['default'] ?? ''); ?>
                <label class="<?= ! empty($campo['wide']) ? 'wide-field' : '' ?>"><?= esc($campo['label']) ?>
                    <?php if ($campo['type'] === 'textarea'): ?>
                        <textarea name="<?= esc($campo['name'], 'attr') ?>" maxlength="<?= (int) ($campo['maxlength'] ?? 255) ?>" rows="3" <?= ! empty($campo['required']) ? 'required' : '' ?>><?= esc($valor) ?></textarea>
                    <?php elseif ($campo['type'] === 'select'): ?>
                        <select name="<?= esc($campo['name'], 'attr') ?>" <?= ! empty($campo['required']) ? 'required' : '' ?>>
                            <?php foreach ($campo['options'] as $optionValue => $optionLabel): ?>
                                <option value="<?= esc((string) $optionValue, 'attr') ?>" <?= (string) $valor === (string) $optionValue ? 'selected' : '' ?>><?= esc($optionLabel) ?></option>
                            <?php endforeach ?>
                        </select>
                    <?php else: ?>
                        <input type="<?= esc($campo['type'], 'attr') ?>" name="<?= esc($campo['name'], 'attr') ?>" maxlength="<?= (int) ($campo['maxlength'] ?? 180) ?>" value="<?= esc((string) $valor, 'attr') ?>" <?= isset($campo['min']) ? 'min="' . esc((string) $campo['min'], 'attr') . '"' : '' ?> <?= isset($campo['step']) ? 'step="' . esc((string) $campo['step'], 'attr') . '"' : '' ?> <?= ! empty($campo['required']) ? 'required' : '' ?>>
                    <?php endif ?>
                </label>
            <?php endforeach ?>
        </div>
        <div class="form-actions"><button class="button" type="submit">Guardar</button><a class="button button-outline" href="<?= site_url('inventario/' . $tipo) ?>" target="_blank" rel="noopener">Cancelar</a></div>
    </form>
</section>
<?= $this->endSection() ?>
