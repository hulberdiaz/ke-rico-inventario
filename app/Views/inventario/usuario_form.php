<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel form-panel">
    <p class="muted">La contraseña se almacenará cifrada. Debe tener al menos 10 caracteres.</p>
    <form class="data-form" action="<?= site_url('inventario/usuarios') ?>" method="post" target="_blank">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label>Usuario<input name="usuario" maxlength="40" value="<?= esc(old('usuario')) ?>" required></label>
            <label>Contraseña temporal<input type="password" name="password" minlength="10" maxlength="200" required></label>
            <label>Nombre<input name="nombre" maxlength="80" value="<?= esc(old('nombre')) ?>" required></label>
            <label>Apellido<input name="apellido" maxlength="80" value="<?= esc(old('apellido')) ?>" required></label>
            <label>Tipo de documento<select name="tipo_documento" required>
                <?php foreach (['CC' => 'Cédula', 'TI' => 'Tarjeta de identidad', 'CE' => 'Cédula de extranjería', 'PA' => 'Pasaporte', 'NIT' => 'NIT'] as $value => $label): ?>
                    <option value="<?= esc($value, 'attr') ?>" <?= old('tipo_documento', 'CC') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach ?>
            </select></label>
            <label>Documento<input name="documento" maxlength="30" value="<?= esc(old('documento')) ?>" required></label>
            <label>Correo<input type="email" name="correo" maxlength="120" value="<?= esc(old('correo')) ?>" required></label>
            <label>Teléfono<input name="telefono" maxlength="30" value="<?= esc(old('telefono')) ?>"></label>
            <label>Fecha de nacimiento<input type="date" name="fecha_nac" value="<?= esc(old('fecha_nac')) ?>"></label>
            <label>Cargo<input name="cargo" maxlength="80" value="<?= esc(old('cargo')) ?>"></label>
            <label>Rol<select name="rol_id" required><option value="">Selecciona un rol</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= (int) $rol['id'] ?>" <?= (string) old('rol_id') === (string) $rol['id'] ? 'selected' : '' ?>><?= esc($rol['nombre']) ?></option>
                <?php endforeach ?>
            </select></label>
        </div>
        <div class="form-actions"><button class="button" type="submit">Crear usuario</button><a class="button button-outline" href="<?= site_url('inventario/usuarios') ?>" target="_blank" rel="noopener">Volver a usuarios</a></div>
    </form>
</section>
<?= $this->endSection() ?>
