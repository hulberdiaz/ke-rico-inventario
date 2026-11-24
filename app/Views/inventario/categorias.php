<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">CATÁLOGO</span><h2>Organizar productos</h2></div>
        <?php if (in_array((int) session()->get('rol_id'), [1, 2, 4], true)): ?>
            <a class="button" href="<?= site_url('inventario/catalogos/categorias/nuevo') ?>" target="_blank" rel="noopener">＋ Nueva categoría</a>
        <?php endif ?>
    </div>
    <?php if ($categorias === []): ?>
        <p class="empty-state">No hay categorías registradas.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Categoría</th><th>Descripción</th><th>Productos asociados</th><th>Estado</th></tr></thead>
            <tbody><?php foreach ($categorias as $categoria): ?>
                <tr><td><strong><?= esc($categoria['nombre']) ?></strong></td><td><?= esc($categoria['descripcion']) ?></td><td><?= number_format((int) $categoria['cantidad_productos']) ?></td><td><?= (int) $categoria['activo'] === 1 ? 'Activa' : 'Inactiva' ?></td></tr>
            <?php endforeach ?></tbody>
        </table></div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
