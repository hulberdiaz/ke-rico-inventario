<?= $this->extend('inventario/layout') ?>

<?= $this->section('content') ?>
<section class="panel">
    <?php if (! empty($createUrl)): ?>
        <div class="panel-heading"><div><span class="eyebrow">GESTIÓN</span><h2>Administrar registros</h2></div><a class="button" href="<?= esc($createUrl, 'attr') ?>" target="_blank" rel="noopener">＋ Agregar</a></div>
    <?php endif ?>
    <?php if ($rows === []): ?>
        <p class="empty-state">No hay registros para mostrar.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><?php foreach ($columns as $label): ?><th><?= esc($label) ?></th><?php endforeach ?></tr></thead>
            <tbody><?php foreach ($rows as $row): ?>
                <tr><?php foreach ($columns as $field => $label): ?>
                    <td><?= esc((string) ($row[$field] ?? '—')) ?></td>
                <?php endforeach ?></tr>
            <?php endforeach ?></tbody>
        </table></div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
