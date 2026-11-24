<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tirilla <?= esc($comprobante['numero']) ?> - Ke-Rico</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/inventario.css') ?>">
</head>
<body>
<main class="receipt">
    <div class="receipt-actions"><button class="button" type="button" onclick="window.print()">Imprimir tirilla</button><a class="button button-outline" href="<?= site_url('inventario/ventas') ?>" target="_blank" rel="noopener">Ver ventas</a></div>
    <section class="receipt-paper">
        <h1><?= esc($comprobante['emisor']['nombre'] ?? 'Ke-Rico') ?></h1>
        <p><?= esc($comprobante['emisor']['razon_social'] ?? '') ?><br>NIT: <?= esc($comprobante['emisor']['nit'] ?? '') ?><br><?= esc($comprobante['emisor']['direccion'] ?? '') ?></p>
        <hr>
        <h2>TIRILLA INTERNA DE VENTA</h2>
        <p><strong><?= esc($comprobante['numero']) ?></strong><br><?= esc($comprobante['fecha_emision']) ?><br>Cliente: <?= esc($comprobante['cliente']['nombre'] ?? 'Consumidor final') ?></p>
        <div class="table-wrap"><table>
            <thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Total</th></tr></thead>
            <tbody><?php foreach ($comprobante['detalle'] as $linea): ?>
                <tr><td><?= esc($linea['nombre']) ?><small class="cell-subtitle"><?= esc($linea['sku']) ?></small></td><td><?= number_format((float) $linea['cantidad'], 3) ?></td><td>$ <?= number_format((float) $linea['precio_unitario'], 0, ',', '.') ?></td><td>$ <?= number_format((float) $linea['total'], 0, ',', '.') ?></td></tr>
            <?php endforeach ?></tbody>
        </table></div>
        <div class="receipt-total">
            <p>Subtotal <strong>$ <?= number_format((float) $comprobante['subtotal'], 0, ',', '.') ?></strong></p>
            <p>Descuento <strong>$ <?= number_format((float) $comprobante['descuento'], 0, ',', '.') ?></strong></p>
            <p>Impuesto incluido <strong>$ <?= number_format((float) $comprobante['impuesto'], 0, ',', '.') ?></strong></p>
            <p class="receipt-grand-total">Total <strong>$ <?= number_format((float) $comprobante['total'], 0, ',', '.') ?></strong></p>
        </div>
        <p class="receipt-note">Documento interno. No es factura electrónica ni comprobante fiscal oficial.</p>
    </section>
</main>
</body>
</html>
