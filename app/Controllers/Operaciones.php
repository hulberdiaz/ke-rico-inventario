<?php

namespace App\Controllers;

class Operaciones extends BaseController
{
    public function nuevoProducto(): string
    {
        return view('inventario/producto_form', [
            'titulo'      => 'Nuevo producto',
            'producto'    => [],
            'categorias'  => $this->catalogo('categorias'),
            'proveedores' => $this->catalogo('proveedores'),
            'action'      => site_url('inventario/productos'),
        ]);
    }

    public function editarProducto(int $id)
    {
        $producto = db_connect()->table('productos')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($producto === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('No se encontró el producto.');
        }

        return view('inventario/producto_form', [
            'titulo'      => 'Editar producto',
            'producto'    => $producto,
            'categorias'  => $this->catalogo('categorias'),
            'proveedores' => $this->catalogo('proveedores'),
            'action'      => site_url('inventario/productos/' . $id),
        ]);
    }

    public function guardarProducto()
    {
        $datos = $this->datosProducto();
        $error = $this->validarProducto($datos);
        if ($error !== null) {
            return redirect()->to(site_url('inventario/productos/nuevo'))
                ->withInput()->with('error', $error);
        }

        $db = db_connect();
        if ($db->table('productos')->where('sku', $datos['sku'])->countAllResults() > 0) {
            return redirect()->to(site_url('inventario/productos/nuevo'))
                ->withInput()->with('error', 'Ese SKU ya está registrado.');
        }

        $db->transBegin();
        $datos['stock_actual'] = 0;
        $datos['activo'] = 1;
        $datos['creado_en'] = date('Y-m-d H:i:s');
        $datos['actualizado_en'] = date('Y-m-d H:i:s');
        if (! $db->table('productos')->insert($datos)) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/productos/nuevo'))
                ->withInput()->with('error', 'No se pudo guardar el producto.');
        }

        $id = $db->insertID();
        $this->registrarBitacora($db, 'PRODUCTO_CREAR', 'Inventario', 'Producto creado: ' . $datos['nombre'], 'productos', (string) $id);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/productos/nuevo'))
                ->withInput()->with('error', 'No se pudo completar el registro del producto.');
        }

        $db->transCommit();
        return redirect()->to(site_url('inventario/productos'))->with('success', 'Producto creado. Registra su stock inicial con un movimiento de entrada.');
    }

    public function actualizarProducto(int $id)
    {
        $db = db_connect();
        $actual = $db->table('productos')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        if ($actual === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('No se encontró el producto.');
        }

        $datos = $this->datosProducto();
        $error = $this->validarProducto($datos);
        if ($error !== null) {
            return redirect()->to(site_url('inventario/productos/editar/' . $id))
                ->withInput()->with('error', $error);
        }

        if ($db->table('productos')->where('sku', $datos['sku'])->where('id !=', $id)->countAllResults() > 0) {
            return redirect()->to(site_url('inventario/productos/editar/' . $id))
                ->withInput()->with('error', 'Ese SKU ya está registrado.');
        }

        $datos['actualizado_en'] = date('Y-m-d H:i:s');
        $db->transBegin();
        if (! $db->table('productos')->where('id', $id)->update($datos)) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/productos/editar/' . $id))
                ->withInput()->with('error', 'No se pudo actualizar el producto.');
        }

        $this->registrarBitacora($db, 'PRODUCTO_ACTUALIZAR', 'Inventario', 'Producto actualizado: ' . $datos['nombre'], 'productos', (string) $id, $actual, $datos);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/productos/editar/' . $id))
                ->withInput()->with('error', 'No se pudo completar la actualización del producto.');
        }

        $db->transCommit();
        return redirect()->to(site_url('inventario/productos'))->with('success', 'Producto actualizado.');
    }

    public function nuevoAjuste(): string
    {
        return view('inventario/stock_form', [
            'titulo'    => 'Registrar movimiento de stock',
            'productos' => $this->productosActivos(),
        ]);
    }

    public function guardarAjuste()
    {
        $productId = filter_var($this->request->getPost('producto_id'), FILTER_VALIDATE_INT);
        $tipo = (string) $this->request->getPost('tipo');
        $cantidad = $this->request->getPost('cantidad');
        $motivo = trim((string) $this->request->getPost('motivo'));
        $observaciones = trim((string) $this->request->getPost('observaciones'));
        $tipos = ['Entrada', 'Salida', 'Ajuste', 'Merma', 'Devolucion'];

        if (! $productId || ! in_array($tipo, $tipos, true) || ! is_numeric($cantidad) || (float) $cantidad <= 0 || $motivo === '') {
            return redirect()->to(site_url('inventario/stock/nuevo'))
                ->withInput()->with('error', 'Selecciona un producto y tipo, e ingresa una cantidad positiva y un motivo.');
        }

        $db = db_connect();
        $db->transBegin();
        $producto = $db->query(
            'SELECT * FROM productos WHERE id = ? AND activo = 1 AND deleted_at IS NULL FOR UPDATE',
            [$productId]
        )->getRowArray();

        if ($producto === null) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/stock/nuevo'))->with('error', 'El producto no existe o está inactivo.');
        }

        $anterior = (float) $producto['stock_actual'];
        $movido = (float) $cantidad;
        if (in_array($tipo, ['Entrada', 'Devolucion'], true)) {
            $nuevo = $anterior + $movido;
        } elseif (in_array($tipo, ['Salida', 'Merma'], true)) {
            $nuevo = $anterior - $movido;
        } else {
            $nuevo = $movido;
            $movido = abs($nuevo - $anterior);
        }

        if ($movido <= 0) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/stock/nuevo'))
                ->withInput()->with('error', 'El movimiento no cambia el stock actual.');
        }

        if ($nuevo < 0) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/stock/nuevo'))
                ->withInput()->with('error', 'El movimiento dejaría el inventario en negativo.');
        }

        $db->table('productos')->where('id', $productId)->update([
            'stock_actual'   => number_format($nuevo, 3, '.', ''),
            'actualizado_en' => date('Y-m-d H:i:s'),
        ]);
        $db->table('movimientos_inventario')->insert([
            'producto_id'      => $productId,
            'tipo'             => $tipo,
            'motivo'           => $motivo,
            'cantidad'         => number_format($movido, 3, '.', ''),
            'stock_anterior'   => number_format($anterior, 3, '.', ''),
            'stock_nuevo'      => number_format($nuevo, 3, '.', ''),
            'costo_unitario'   => $producto['precio_costo'],
            'referencia_tipo'  => 'Ajuste',
            'usuario_id'       => (int) session()->get('user_id'),
            'observaciones'    => $observaciones,
        ]);
        $this->registrarBitacora($db, 'STOCK_' . strtoupper($tipo), 'Inventario', $motivo . ': ' . $producto['nombre'], 'productos', (string) $productId, ['stock' => $anterior], ['stock' => $nuevo]);

        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/stock/nuevo'))
                ->withInput()->with('error', 'No se pudo guardar el movimiento de inventario.');
        }

        $db->transCommit();
        return redirect()->to(site_url('inventario/movimientos'))->with('success', 'Movimiento de stock registrado.');
    }

    public function nuevaVenta(): string
    {
        return view('inventario/venta_form', [
            'titulo'      => 'Nueva venta',
            'productos'   => $this->productosActivos(true),
            'clientes'    => db_connect()->table('clientes')->where('deleted_at', null)->orderBy('nombre')->get()->getResultArray(),
            'promociones' => db_connect()->table('promociones')->where('activa', 1)->where('deleted_at', null)->orderBy('codigo')->get()->getResultArray(),
        ]);
    }

    public function registrarVenta()
    {
        $seleccion = $this->request->getPost('productos');
        if (! is_array($seleccion)) {
            return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'Agrega al menos un producto a la venta.');
        }

        $db = db_connect();
        $db->transBegin();
        $lineas = [];
        foreach ($seleccion as $id => $cantidad) {
            if ($cantidad === '' || (float) $cantidad === 0.0) {
                continue;
            }
            if (! ctype_digit((string) $id) || ! is_numeric($cantidad) || (float) $cantidad < 0) {
                $db->transRollback();
                return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'Revisa las cantidades de los productos.');
            }

            $producto = $db->query(
                'SELECT * FROM productos WHERE id = ? AND activo = 1 AND es_insumo = 0 AND precio_venta > 0 AND deleted_at IS NULL FOR UPDATE',
                [(int) $id]
            )->getRowArray();
            $cantidad = round((float) $cantidad, 3);
            if ($producto === null || $cantidad <= 0 || (float) $producto['stock_actual'] < $cantidad) {
                $db->transRollback();
                return redirect()->to(site_url('inventario/ventas/nueva'))
                    ->with('error', 'Uno de los productos no está disponible o no tiene stock suficiente.');
            }

            $bruto = round($cantidad * (float) $producto['precio_venta'], 2);
            $lineas[] = [
                'producto' => $producto,
                'cantidad' => $cantidad,
                'bruto'    => $bruto,
                'descuento' => 0.0,
            ];
        }

        if ($lineas === []) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'Agrega al menos un producto con cantidad mayor que cero.');
        }

        $clienteId = filter_var($this->request->getPost('cliente_id'), FILTER_VALIDATE_INT);
        $cliente = null;
        if ($clienteId) {
            $cliente = $db->table('clientes')->where('id', $clienteId)->where('deleted_at', null)->get()->getRowArray();
            if ($cliente === null) {
                $db->transRollback();
                return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'El cliente seleccionado no está disponible.');
            }
        }

        $subtotal = array_sum(array_column($lineas, 'bruto'));
        $codigoPromocion = strtoupper(trim((string) $this->request->getPost('codigo_promocion')));
        $promocion = null;
        $descuentoTotal = 0.0;
        if ($codigoPromocion !== '') {
            $promocion = $db->query(
                'SELECT * FROM promociones WHERE codigo = ? AND activa = 1 AND deleted_at IS NULL FOR UPDATE',
                [$codigoPromocion]
            )->getRowArray();

            $hoy = date('Y-m-d');
            if (
                $promocion === null
                || ($promocion['fecha_inicio'] !== null && $hoy < $promocion['fecha_inicio'])
                || ($promocion['fecha_fin'] !== null && $hoy > $promocion['fecha_fin'])
                || ((int) $promocion['usos_maximos'] > 0 && (int) $promocion['usos_actuales'] >= (int) $promocion['usos_maximos'])
                || $subtotal < (float) $promocion['monto_minimo']
                || ((int) $promocion['requiere_autorizacion'] === 1 && ! in_array((int) session()->get('rol_id'), [1, 2], true))
            ) {
                $db->transRollback();
                return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'El código de promoción no es válido, no está vigente o requiere autorización.');
            }

            $elegibles = [];
            foreach ($lineas as $indice => $linea) {
                $aplica = $promocion['alcance'] === 'venta'
                    || ($promocion['alcance'] === 'producto' && (int) $promocion['producto_id'] === (int) $linea['producto']['id'])
                    || ($promocion['alcance'] === 'categoria' && (int) $promocion['categoria_id'] === (int) $linea['producto']['categoria_id']);
                if ($aplica) {
                    $elegibles[] = $indice;
                }
            }
            $montoElegible = 0.0;
            foreach ($elegibles as $indice) {
                $montoElegible += $lineas[$indice]['bruto'];
            }
            if ($montoElegible <= 0) {
                $db->transRollback();
                return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'La promoción no aplica a los productos seleccionados.');
            }

            $descuentoTotal = $promocion['tipo'] === 'porcentaje'
                ? round($montoElegible * min(100, (float) $promocion['valor']) / 100, 2)
                : min($montoElegible, (float) $promocion['valor']);

            $asignado = 0.0;
            foreach ($elegibles as $posicion => $indice) {
                if ($posicion === count($elegibles) - 1) {
                    $descuentoLinea = round($descuentoTotal - $asignado, 2);
                } else {
                    $descuentoLinea = round($descuentoTotal * $lineas[$indice]['bruto'] / $montoElegible, 2);
                    $asignado += $descuentoLinea;
                }
                $lineas[$indice]['descuento'] = min($lineas[$indice]['bruto'], $descuentoLinea);
            }
        }

        $total = round($subtotal - $descuentoTotal, 2);
        if ($total < 0) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'El descuento no puede superar el total de la venta.');
        }

        $metodoPago = (string) $this->request->getPost('metodo_pago');
        if (! in_array($metodoPago, ['Efectivo', 'Tarjeta', 'Transferencia', 'Mixto'], true)) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'Selecciona un método de pago válido.');
        }
        $recibidoPost = $this->request->getPost('monto_recibido');
        $recibido = $metodoPago === 'Efectivo' || $metodoPago === 'Mixto'
            ? (is_numeric($recibidoPost) ? (float) $recibidoPost : -1)
            : $total;
        if ($recibido < $total) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'El monto recibido no alcanza para cubrir la venta.');
        }

        $folio = 'V-' . strtoupper(bin2hex(random_bytes(6)));
        $db->table('ventas')->insert([
            'folio'          => $folio,
            'usuario_id'     => (int) session()->get('user_id'),
            'cliente_id'     => $clienteId ?: null,
            'subtotal'       => number_format($subtotal, 2, '.', ''),
            'descuento_total' => number_format($descuentoTotal, 2, '.', ''),
            'base_gravable'  => '0.00',
            'impuesto_total' => '0.00',
            'total'          => number_format($total, 2, '.', ''),
            'costo_total'    => '0.00',
            'metodo_pago'    => $metodoPago,
            'monto_recibido' => number_format($recibido, 2, '.', ''),
            'cambio'         => number_format(max(0, $recibido - $total), 2, '.', ''),
            'estado'         => 'Completada',
        ]);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'No se pudo registrar la venta.');
        }
        $ventaId = $db->insertID();

        $detalleComprobante = [];
        $baseTotal = 0.0;
        $impuestoTotal = 0.0;
        $costoTotal = 0.0;
        foreach ($lineas as $linea) {
            $producto = $linea['producto'];
            $neto = round($linea['bruto'] - $linea['descuento'], 2);
            $base = round($neto / (1 + (float) $producto['iva_porcentaje'] / 100), 2);
            $impuesto = round($neto - $base, 2);
            $baseTotal += $base;
            $impuestoTotal += $impuesto;
            $costoTotal += (float) $producto['precio_costo'] * $linea['cantidad'];
            $stockNuevo = (float) $producto['stock_actual'] - $linea['cantidad'];

            $db->table('venta_detalle')->insert([
                'venta_id'           => $ventaId,
                'producto_id'        => $producto['id'],
                'sku'                => $producto['sku'],
                'nombre_producto'    => $producto['nombre'],
                'cantidad'           => number_format($linea['cantidad'], 3, '.', ''),
                'precio_unitario'    => $producto['precio_venta'],
                'costo_unitario'     => $producto['precio_costo'],
                'descuento_unitario' => number_format($linea['descuento'] / $linea['cantidad'], 2, '.', ''),
                'iva_porcentaje'     => $producto['iva_porcentaje'],
                'subtotal'           => number_format($base, 2, '.', ''),
                'impuesto'           => number_format($impuesto, 2, '.', ''),
                'total'              => number_format($neto, 2, '.', ''),
            ]);
            $db->table('productos')->where('id', $producto['id'])->update([
                'stock_actual'   => number_format($stockNuevo, 3, '.', ''),
                'actualizado_en' => date('Y-m-d H:i:s'),
            ]);
            $db->table('movimientos_inventario')->insert([
                'producto_id'     => $producto['id'],
                'tipo'            => 'Salida',
                'motivo'          => 'Venta ' . $folio,
                'cantidad'        => number_format($linea['cantidad'], 3, '.', ''),
                'stock_anterior'  => $producto['stock_actual'],
                'stock_nuevo'     => number_format($stockNuevo, 3, '.', ''),
                'costo_unitario'  => $producto['precio_costo'],
                'referencia_tipo' => 'Venta',
                'referencia_id'   => $ventaId,
                'usuario_id'      => (int) session()->get('user_id'),
                'observaciones'   => '',
            ]);
            $detalleComprobante[] = [
                'sku'             => $producto['sku'],
                'nombre'          => $producto['nombre'],
                'cantidad'        => $linea['cantidad'],
                'precio_unitario' => (float) $producto['precio_venta'],
                'iva_porcentaje'  => (float) $producto['iva_porcentaje'],
                'subtotal'        => $base,
                'impuesto'        => $impuesto,
                'total'           => $neto,
            ];
        }

        $db->table('ventas')->where('id', $ventaId)->update([
            'base_gravable'  => number_format($baseTotal, 2, '.', ''),
            'impuesto_total' => number_format($impuestoTotal, 2, '.', ''),
            'costo_total'    => number_format($costoTotal, 2, '.', ''),
        ]);

        if ($promocion !== null) {
            $db->table('descuentos_aplicados')->insert([
                'venta_id'        => $ventaId,
                'promocion_id'    => $promocion['id'],
                'codigo'          => $promocion['codigo'],
                'tipo'            => $promocion['tipo'],
                'valor'           => $promocion['valor'],
                'motivo'          => $promocion['descripcion'],
                'valor_original'  => number_format($subtotal, 2, '.', ''),
                'valor_descuento' => number_format($descuentoTotal, 2, '.', ''),
                'valor_final'     => number_format($total, 2, '.', ''),
                'autorizado_por'  => (int) $promocion['requiere_autorizacion'] === 1 ? (int) session()->get('user_id') : null,
            ]);
            $db->table('promociones')->where('id', $promocion['id'])->update([
                'usos_actuales' => (int) $promocion['usos_actuales'] + 1,
            ]);
        }

        $comprobanteId = $this->crearComprobante($db, $ventaId, $cliente, $folio, $subtotal, $descuentoTotal, $baseTotal, $impuestoTotal, $total, $detalleComprobante);
        if ($comprobanteId === null || ! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'No se pudo completar la venta y emitir su tirilla interna.');
        }

        $this->registrarBitacora($db, 'VENTA_REGISTRAR', 'Ventas', 'Venta registrada: ' . $folio, 'ventas', (string) $ventaId);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/ventas/nueva'))->with('error', 'No se pudo completar el registro de la venta.');
        }

        $db->transCommit();
        return redirect()->to(site_url('inventario/comprobantes/' . $comprobanteId));
    }

    public function nuevaCompra(): string
    {
        return view('inventario/compra_form', [
            'titulo'      => 'Registrar compra recibida',
            'productos'   => $this->productosActivos(),
            'proveedores' => $this->catalogo('proveedores'),
        ]);
    }

    public function registrarCompra()
    {
        $proveedorId = filter_var($this->request->getPost('proveedor_id'), FILTER_VALIDATE_INT);
        $seleccion = $this->request->getPost('productos');
        $costos = $this->request->getPost('costos');
        if (! $proveedorId || ! is_array($seleccion) || ! is_array($costos)) {
            return redirect()->to(site_url('inventario/compras/nueva'))->with('error', 'Selecciona un proveedor e ingresa los productos de la compra.');
        }

        $db = db_connect();
        $db->transBegin();
        $proveedor = $db->table('proveedores')->where('id', $proveedorId)->where('estado', 'Activo')->where('deleted_at', null)->get()->getRowArray();
        if ($proveedor === null) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/compras/nueva'))->with('error', 'El proveedor no está activo.');
        }

        $lineas = [];
        $total = 0.0;
        foreach ($seleccion as $id => $cantidad) {
            if ($cantidad === '' || (float) $cantidad === 0.0) {
                continue;
            }
            if (! ctype_digit((string) $id) || ! is_numeric($cantidad) || (float) $cantidad < 0 || ! isset($costos[$id]) || ! is_numeric($costos[$id]) || (float) $costos[$id] < 0) {
                $db->transRollback();
                return redirect()->to(site_url('inventario/compras/nueva'))->with('error', 'Revisa las cantidades y costos de los productos.');
            }

            $producto = $db->query(
                'SELECT * FROM productos WHERE id = ? AND activo = 1 AND deleted_at IS NULL FOR UPDATE',
                [(int) $id]
            )->getRowArray();
            $cantidad = round((float) $cantidad, 3);
            if ($producto === null || $cantidad <= 0) {
                $db->transRollback();
                return redirect()->to(site_url('inventario/compras/nueva'))->with('error', 'Uno de los productos no está disponible.');
            }

            $costo = round((float) $costos[$id], 2);
            $subtotal = round($cantidad * $costo, 2);
            $total += $subtotal;
            $lineas[] = ['producto' => $producto, 'cantidad' => $cantidad, 'costo' => $costo, 'subtotal' => $subtotal];
        }

        if ($lineas === []) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/compras/nueva'))->with('error', 'Agrega al menos un producto con cantidad mayor que cero.');
        }

        $numero = 'OC-' . strtoupper(bin2hex(random_bytes(5)));
        $db->table('compras')->insert([
            'numero'       => $numero,
            'proveedor_id' => $proveedorId,
            'usuario_id'   => (int) session()->get('user_id'),
            'subtotal'     => number_format($total, 2, '.', ''),
            'impuesto'     => '0.00',
            'total'        => number_format($total, 2, '.', ''),
            'estado'       => 'Recibida',
            'observaciones' => trim((string) $this->request->getPost('observaciones')),
        ]);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/compras/nueva'))->with('error', 'No se pudo registrar la compra.');
        }
        $compraId = $db->insertID();

        foreach ($lineas as $linea) {
            $producto = $linea['producto'];
            $anterior = (float) $producto['stock_actual'];
            $nuevo = $anterior + $linea['cantidad'];
            $costoPromedio = $nuevo > 0
                ? (($anterior * (float) $producto['precio_costo']) + $linea['subtotal']) / $nuevo
                : $linea['costo'];

            $db->table('compra_detalle')->insert([
                'compra_id'    => $compraId,
                'producto_id'  => $producto['id'],
                'cantidad'     => number_format($linea['cantidad'], 3, '.', ''),
                'costo_unitario' => number_format($linea['costo'], 2, '.', ''),
                'subtotal'     => number_format($linea['subtotal'], 2, '.', ''),
            ]);
            $db->table('productos')->where('id', $producto['id'])->update([
                'stock_actual'   => number_format($nuevo, 3, '.', ''),
                'precio_costo'   => number_format($costoPromedio, 2, '.', ''),
                'actualizado_en' => date('Y-m-d H:i:s'),
            ]);
            $db->table('movimientos_inventario')->insert([
                'producto_id'     => $producto['id'],
                'tipo'            => 'Entrada',
                'motivo'          => 'Compra ' . $numero,
                'cantidad'        => number_format($linea['cantidad'], 3, '.', ''),
                'stock_anterior'  => number_format($anterior, 3, '.', ''),
                'stock_nuevo'     => number_format($nuevo, 3, '.', ''),
                'costo_unitario'  => number_format($linea['costo'], 2, '.', ''),
                'referencia_tipo' => 'Compra',
                'referencia_id'   => $compraId,
                'usuario_id'      => (int) session()->get('user_id'),
                'observaciones'   => '',
            ]);
        }

        $this->registrarBitacora($db, 'COMPRA_REGISTRAR', 'Compras', 'Compra recibida: ' . $numero, 'compras', (string) $compraId);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/compras/nueva'))->with('error', 'No se pudo completar la compra.');
        }

        $db->transCommit();
        return redirect()->to(site_url('inventario/compras'))->with('success', 'Compra recibida y stock actualizado.');
    }

    public function nuevoCatalogo(string $tipo)
    {
        $roles = $this->rolesCatalogo($tipo);
        if ($roles === []) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (! in_array((int) session()->get('rol_id'), $roles, true)) {
            return redirect()->to(site_url('inventario'))->with('error', 'Tu usuario no tiene permiso para realizar esta acción.');
        }

        $campos = $this->camposCatalogo($tipo);
        if ($tipo === 'promociones') {
            $campos['producto_id']['options'] = ['' => 'No aplica'] + $this->opcionesProductos();
            $campos['categoria_id']['options'] = ['' => 'No aplica'] + $this->opcionesCategorias();
        }

        return view('inventario/registro_form', [
            'titulo' => 'Nuevo registro',
            'tipo'   => $tipo,
            'campos' => array_values($campos),
        ]);
    }

    public function guardarCatalogo(string $tipo)
    {
        $roles = $this->rolesCatalogo($tipo);
        if ($roles === []) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (! in_array((int) session()->get('rol_id'), $roles, true)) {
            return redirect()->to(site_url('inventario'))->with('error', 'Tu usuario no tiene permiso para realizar esta acción.');
        }

        $campos = $this->camposCatalogo($tipo);
        $datos = [];
        foreach (array_keys($campos) as $nombre) {
            $valor = trim((string) $this->request->getPost($nombre));
            $datos[$nombre] = $valor === '' ? null : $valor;
        }

        $error = $this->validarCatalogo($tipo, $datos);
        if ($error !== null) {
            return redirect()->to(site_url('inventario/catalogos/' . rawurlencode($tipo) . '/nuevo'))
                ->withInput()->with('error', $error);
        }

        $db = db_connect();
        if ($tipo === 'categorias' && $db->table('categorias')->where('nombre', $datos['nombre'])->countAllResults() > 0) {
            return redirect()->to(site_url('inventario/catalogos/' . $tipo . '/nuevo'))->withInput()->with('error', 'Ya existe una categoría con ese nombre.');
        }
        if ($tipo === 'proveedores' && $db->table('proveedores')->where('nit', $datos['nit'])->countAllResults() > 0) {
            return redirect()->to(site_url('inventario/catalogos/' . $tipo . '/nuevo'))->withInput()->with('error', 'Ya existe un proveedor con ese NIT.');
        }
        if ($tipo === 'promociones') {
            $datos['codigo'] = strtoupper($datos['codigo']);
            if ($db->table('promociones')->where('codigo', $datos['codigo'])->countAllResults() > 0) {
                return redirect()->to(site_url('inventario/catalogos/' . $tipo . '/nuevo'))->withInput()->with('error', 'Ya existe una promoción con ese código.');
            }
        }
        if ($tipo === 'clientes' && $datos['documento'] !== null && $db->table('clientes')->where('documento', $datos['documento'])->where('documento !=', '')->where('deleted_at', null)->countAllResults() > 0) {
            return redirect()->to(site_url('inventario/catalogos/' . $tipo . '/nuevo'))->withInput()->with('error', 'Ya existe un cliente con ese documento.');
        }

        if ($tipo === 'categorias') {
            $datos['activo'] = 1;
        } elseif ($tipo === 'proveedores') {
            $datos['estado'] = 'Activo';
            $datos['calificacion'] = 0;
        } elseif ($tipo === 'promociones') {
            $datos['producto_id'] = $datos['producto_id'] === null ? null : (int) $datos['producto_id'];
            $datos['categoria_id'] = $datos['categoria_id'] === null ? null : (int) $datos['categoria_id'];
            $datos['monto_minimo'] = (float) $datos['monto_minimo'];
            $datos['usos_maximos'] = (int) $datos['usos_maximos'];
            $datos['usos_actuales'] = 0;
            $datos['requiere_autorizacion'] = 0;
            $datos['activa'] = 1;
            $datos['creado_por'] = (int) session()->get('user_id');
        }

        $tabla = ['categorias' => 'categorias', 'clientes' => 'clientes', 'proveedores' => 'proveedores', 'promociones' => 'promociones'][$tipo];
        $datos = array_filter($datos, static fn ($value): bool => $value !== null);
        $db->transBegin();
        if (! $db->table($tabla)->insert($datos)) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/catalogos/' . $tipo . '/nuevo'))->withInput()->with('error', 'No se pudo guardar el registro.');
        }
        $id = $db->insertID();
        $nombre = $datos['nombre'] ?? $datos['nombre_empresa'] ?? $datos['codigo'] ?? $datos['nombre'];
        $this->registrarBitacora($db, strtoupper(substr($tipo, 0, -1)) . '_CREAR', ucfirst($tipo), 'Registro creado: ' . $nombre, $tabla, (string) $id);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/catalogos/' . $tipo . '/nuevo'))->withInput()->with('error', 'No se pudo completar el registro.');
        }
        $db->transCommit();

        return redirect()->to(site_url('inventario/' . $tipo))->with('success', 'Registro guardado correctamente.');
    }

    public function nuevoUsuario(): string
    {
        return view('inventario/usuario_form', [
            'titulo' => 'Crear usuario',
            'roles'  => db_connect()->table('roles')->where('activo', 1)->orderBy('nombre')->get()->getResultArray(),
        ]);
    }

    public function guardarUsuario()
    {
        $usuario = trim((string) $this->request->getPost('usuario'));
        $password = (string) $this->request->getPost('password');
        $nombre = trim((string) $this->request->getPost('nombre'));
        $apellido = trim((string) $this->request->getPost('apellido'));
        $documento = trim((string) $this->request->getPost('documento'));
        $correo = trim((string) $this->request->getPost('correo'));
        $rolId = filter_var($this->request->getPost('rol_id'), FILTER_VALIDATE_INT);
        $fechaNac = trim((string) $this->request->getPost('fecha_nac'));

        if (
            strlen($usuario) < 3 || strlen($usuario) > 40 || ! preg_match('/^[A-Za-z0-9._-]+$/', $usuario)
            || strlen($password) < 10 || strlen($password) > 200
            || $nombre === '' || strlen($nombre) > 80 || $apellido === '' || strlen($apellido) > 80
            || $documento === '' || strlen($documento) > 30
            || filter_var($correo, FILTER_VALIDATE_EMAIL) === false
            || ! $rolId
        ) {
            return redirect()->to(site_url('inventario/usuarios/nuevo'))->withInput()
                ->with('error', 'Revisa los datos: contraseña mínima de 10 caracteres, usuario válido y campos obligatorios completos.');
        }

        if ($fechaNac !== '' && ! $this->fechaValida($fechaNac)) {
            return redirect()->to(site_url('inventario/usuarios/nuevo'))->withInput()->with('error', 'La fecha de nacimiento no es válida.');
        }

        $db = db_connect();
        $rol = $db->table('roles')->where('id', $rolId)->where('activo', 1)->get()->getRowArray();
        if ($rol === null) {
            return redirect()->to(site_url('inventario/usuarios/nuevo'))->withInput()->with('error', 'Selecciona un rol activo.');
        }
        foreach (['usuario' => $usuario, 'correo' => $correo, 'documento' => $documento] as $campo => $valor) {
            if ($db->table('usuarios')->where($campo, $valor)->countAllResults() > 0) {
                return redirect()->to(site_url('inventario/usuarios/nuevo'))->withInput()->with('error', 'El usuario, correo o documento ya está registrado.');
            }
        }

        $db->transBegin();
        $db->table('usuarios')->insert([
            'usuario'       => $usuario,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'nombre'        => $nombre,
            'apellido'      => $apellido,
            'tipo_documento' => (string) $this->request->getPost('tipo_documento'),
            'documento'     => $documento,
            'fecha_nac'     => $fechaNac !== '' ? $fechaNac : null,
            'correo'        => $correo,
            'telefono'      => trim((string) $this->request->getPost('telefono')),
            'cargo'         => trim((string) $this->request->getPost('cargo')),
            'rol_id'        => $rolId,
            'activo'        => 1,
        ]);

        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/usuarios/nuevo'))->withInput()->with('error', 'No se pudo crear el usuario.');
        }
        $id = $db->insertID();
        $this->registrarBitacora($db, 'USUARIO_CREAR', 'Usuarios', 'Usuario creado: ' . $usuario, 'usuarios', (string) $id);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/usuarios/nuevo'))->withInput()->with('error', 'No se pudo completar la creación del usuario.');
        }
        $db->transCommit();

        return redirect()->to(site_url('inventario/usuarios'))->with('success', 'Usuario creado correctamente.');
    }

    public function guardarConfiguracion()
    {
        $solicitados = $this->request->getPost('ajustes');
        if (! is_array($solicitados) || $solicitados === []) {
            return redirect()->to(site_url('inventario/configuracion'))->with('error', 'No hay cambios para guardar.');
        }

        $db = db_connect();
        $permitidos = [];
        foreach ($db->table('configuracion')->get()->getResultArray() as $ajuste) {
            $permitidos[$ajuste['clave']] = $ajuste;
        }
        unset($permitidos['comprobante_consecutivo']);

        $errores = [];
        $cambios = [];
        foreach ($solicitados as $clave => $valor) {
            if (! is_string($clave) || ! isset($permitidos[$clave]) || ! is_scalar($valor)) {
                continue;
            }
            $valor = trim((string) $valor);
            $tipo = $permitidos[$clave]['tipo'];
            if ($tipo === 'entero' && ! ctype_digit($valor)) {
                $errores[] = $clave;
            } elseif ($tipo === 'decimal' && (! is_numeric($valor) || (float) $valor < 0)) {
                $errores[] = $clave;
            } elseif ($tipo === 'booleano' && ! in_array(strtolower($valor), ['0', '1', 'true', 'false'], true)) {
                $errores[] = $clave;
            } elseif ($tipo === 'json' && json_decode($valor) === null && $valor !== 'null') {
                $errores[] = $clave;
            } else {
                $cambios[$clave] = $valor;
            }
        }
        if ($errores !== []) {
            return redirect()->to(site_url('inventario/configuracion'))
                ->with('error', 'Revisa los valores de configuración: ' . implode(', ', $errores));
        }
        if ($cambios === []) {
            return redirect()->to(site_url('inventario/configuracion'))->with('error', 'No se recibieron valores válidos para guardar.');
        }

        $db->transBegin();
        foreach ($cambios as $clave => $valor) {
            $db->table('configuracion')->where('clave', $clave)->update(['valor' => $valor]);
        }
        $this->registrarBitacora($db, 'CONFIGURACION_ACTUALIZAR', 'Sistema', 'Configuración del sistema actualizada', 'configuracion', '', null, $cambios);
        if (! $db->transStatus()) {
            $db->transRollback();
            return redirect()->to(site_url('inventario/configuracion'))->with('error', 'No se pudo guardar la configuración.');
        }
        $db->transCommit();

        return redirect()->to(site_url('inventario/configuracion'))->with('success', 'Configuración guardada.');
    }

    private function datosProducto(): array
    {
        return [
            'sku'           => strtoupper(trim((string) $this->request->getPost('sku'))),
            'nombre'        => trim((string) $this->request->getPost('nombre')),
            'descripcion'   => trim((string) $this->request->getPost('descripcion')),
            'categoria_id'  => filter_var($this->request->getPost('categoria_id'), FILTER_VALIDATE_INT),
            'proveedor_id'  => filter_var($this->request->getPost('proveedor_id'), FILTER_VALIDATE_INT) ?: null,
            'unidad_medida' => trim((string) $this->request->getPost('unidad_medida')),
            'precio_costo'  => $this->request->getPost('precio_costo'),
            'precio_venta'  => $this->request->getPost('precio_venta'),
            'iva_porcentaje' => $this->request->getPost('iva_porcentaje'),
            'punto_reorden' => $this->request->getPost('punto_reorden'),
            'stock_maximo'  => $this->request->getPost('stock_maximo'),
            'es_insumo'     => $this->request->getPost('es_insumo') === '1' ? 1 : 0,
            'imagen'        => trim((string) $this->request->getPost('imagen')),
        ];
    }

    private function validarProducto(array $datos): ?string
    {
        if ($datos['sku'] === '' || strlen($datos['sku']) > 30 || $datos['nombre'] === '' || strlen($datos['nombre']) > 120) {
            return 'El SKU y el nombre son obligatorios (máximo 30 y 120 caracteres).';
        }
        if (! $datos['categoria_id'] || strlen($datos['unidad_medida']) > 20) {
            return 'Selecciona una categoría e ingresa una unidad de medida válida.';
        }
        $categorias = db_connect()->table('categorias')
            ->where('id', $datos['categoria_id'])
            ->where('activo', 1)
            ->where('deleted_at', null)
            ->countAllResults();
        if ($categorias === 0) {
            return 'La categoría seleccionada no está disponible.';
        }
        if ($datos['proveedor_id'] !== null) {
            $proveedores = db_connect()->table('proveedores')
                ->where('id', $datos['proveedor_id'])
                ->where('estado', 'Activo')
                ->where('deleted_at', null)
                ->countAllResults();
            if ($proveedores === 0) {
                return 'El proveedor seleccionado no está activo.';
            }
        }
        foreach (['precio_costo', 'precio_venta', 'iva_porcentaje', 'punto_reorden', 'stock_maximo'] as $campo) {
            if (! is_numeric($datos[$campo]) || (float) $datos[$campo] < 0) {
                return 'Los precios, impuestos y límites de stock deben ser números no negativos.';
            }
        }
        if ((float) $datos['iva_porcentaje'] > 100 || strlen($datos['descripcion']) > 255 || strlen($datos['imagen']) > 180) {
            return 'Revisa la tasa de IVA, la descripción o la ruta de imagen.';
        }

        return null;
    }

    private function catalogo(string $tabla): array
    {
        $builder = db_connect()->table($tabla);
        if ($tabla === 'categorias') {
            $builder->where('deleted_at', null)->where('activo', 1)->orderBy('nombre');
        } else {
            $builder->where('deleted_at', null)->where('estado', 'Activo')->orderBy('nombre_empresa');
        }

        return $builder->get()->getResultArray();
    }

    private function productosActivos(bool $paraVenta = false): array
    {
        $builder = db_connect()->table('productos')
            ->select('id, sku, nombre, precio_costo, precio_venta, iva_porcentaje, stock_actual, unidad_medida')
            ->where('activo', 1)
            ->where('deleted_at', null)
            ->orderBy('nombre');
        if ($paraVenta) {
            $builder->where('es_insumo', 0)->where('precio_venta >', 0);
        }

        return $builder->get()->getResultArray();
    }

    private function rolesCatalogo(string $tipo): array
    {
        return [
            'categorias'  => [1, 2, 4],
            'clientes'    => [1, 2, 3],
            'proveedores' => [1, 2, 4],
            'promociones' => [1, 2],
        ][$tipo] ?? [];
    }

    private function camposCatalogo(string $tipo): array
    {
        $campos = [
            'categorias' => [
                'nombre'      => ['name' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'required' => true, 'maxlength' => 60],
                'descripcion' => ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'textarea', 'maxlength' => 180, 'wide' => true],
            ],
            'clientes' => [
                'tipo_documento' => ['name' => 'tipo_documento', 'label' => 'Tipo de documento', 'type' => 'select', 'default' => 'CC', 'options' => ['CC' => 'Cédula', 'TI' => 'Tarjeta de identidad', 'CE' => 'Cédula de extranjería', 'PA' => 'Pasaporte', 'NIT' => 'NIT', 'NA' => 'Consumidor final']],
                'documento'      => ['name' => 'documento', 'label' => 'Documento (opcional)', 'type' => 'text', 'maxlength' => 30],
                'nombre'         => ['name' => 'nombre', 'label' => 'Nombre o razón social', 'type' => 'text', 'required' => true, 'maxlength' => 120],
                'telefono'       => ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'text', 'maxlength' => 30],
                'correo'         => ['name' => 'correo', 'label' => 'Correo', 'type' => 'email', 'maxlength' => 120],
                'direccion'      => ['name' => 'direccion', 'label' => 'Dirección', 'type' => 'text', 'maxlength' => 180, 'wide' => true],
            ],
            'proveedores' => [
                'nombre_empresa' => ['name' => 'nombre_empresa', 'label' => 'Empresa', 'type' => 'text', 'required' => true, 'maxlength' => 120],
                'nit'            => ['name' => 'nit', 'label' => 'NIT', 'type' => 'text', 'required' => true, 'maxlength' => 30],
                'contacto_nombre' => ['name' => 'contacto_nombre', 'label' => 'Contacto', 'type' => 'text', 'maxlength' => 120],
                'telefono'       => ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'text', 'maxlength' => 30],
                'correo'         => ['name' => 'correo', 'label' => 'Correo', 'type' => 'email', 'maxlength' => 120],
                'direccion'      => ['name' => 'direccion', 'label' => 'Dirección', 'type' => 'text', 'maxlength' => 180],
                'tipo_productos' => ['name' => 'tipo_productos', 'label' => 'Productos que suministra', 'type' => 'text', 'maxlength' => 180, 'wide' => true],
            ],
            'promociones' => [
                'codigo'       => ['name' => 'codigo', 'label' => 'Código', 'type' => 'text', 'required' => true, 'maxlength' => 30],
                'descripcion'  => ['name' => 'descripcion', 'label' => 'Descripción', 'type' => 'text', 'maxlength' => 180],
                'tipo'         => ['name' => 'tipo', 'label' => 'Tipo de descuento', 'type' => 'select', 'required' => true, 'default' => 'porcentaje', 'options' => ['porcentaje' => 'Porcentaje', 'fijo' => 'Valor fijo']],
                'valor'        => ['name' => 'valor', 'label' => 'Valor', 'type' => 'number', 'required' => true, 'min' => 0.01, 'step' => 0.01],
                'alcance'      => ['name' => 'alcance', 'label' => 'Aplicar a', 'type' => 'select', 'required' => true, 'default' => 'venta', 'options' => ['venta' => 'Toda la venta', 'producto' => 'Producto específico', 'categoria' => 'Categoría']],
                'producto_id'  => ['name' => 'producto_id', 'label' => 'Producto (si aplica)', 'type' => 'select', 'options' => []],
                'categoria_id' => ['name' => 'categoria_id', 'label' => 'Categoría (si aplica)', 'type' => 'select', 'options' => []],
                'monto_minimo' => ['name' => 'monto_minimo', 'label' => 'Compra mínima', 'type' => 'number', 'min' => 0, 'step' => 0.01, 'default' => '0'],
                'fecha_inicio' => ['name' => 'fecha_inicio', 'label' => 'Válida desde (opcional)', 'type' => 'date'],
                'fecha_fin'    => ['name' => 'fecha_fin', 'label' => 'Válida hasta (opcional)', 'type' => 'date'],
                'usos_maximos' => ['name' => 'usos_maximos', 'label' => 'Usos máximos (0 = ilimitado)', 'type' => 'number', 'min' => 0, 'step' => 1, 'default' => '0'],
            ],
        ];

        return $campos[$tipo] ?? [];
    }

    private function validarCatalogo(string $tipo, array $datos): ?string
    {
        if ($tipo === 'categorias' && ($datos['nombre'] === null || strlen($datos['nombre']) > 60)) {
            return 'Ingresa el nombre de la categoría (máximo 60 caracteres).';
        }
        if ($tipo === 'clientes' && ($datos['nombre'] === null || strlen($datos['nombre']) > 120)) {
            return 'Ingresa el nombre del cliente (máximo 120 caracteres).';
        }
        if ($tipo === 'proveedores' && ($datos['nombre_empresa'] === null || $datos['nit'] === null || strlen($datos['nombre_empresa']) > 120 || strlen($datos['nit']) > 30)) {
            return 'La empresa y el NIT son obligatorios.';
        }
        if ($tipo === 'promociones') {
            if ($datos['codigo'] === null || ! in_array($datos['tipo'], ['porcentaje', 'fijo'], true) || ! in_array($datos['alcance'], ['venta', 'producto', 'categoria'], true)) {
                return 'Completa el código y los valores válidos de la promoción.';
            }
            if (! is_numeric($datos['valor']) || (float) $datos['valor'] <= 0 || ! is_numeric($datos['monto_minimo']) || (float) $datos['monto_minimo'] < 0 || ! is_numeric($datos['usos_maximos'] ?? 0) || (int) $datos['usos_maximos'] < 0) {
                return 'El valor, monto mínimo y límite de usos no son válidos.';
            }
            if ($datos['tipo'] === 'porcentaje' && (float) $datos['valor'] > 100) {
                return 'El porcentaje de descuento no puede superar 100%.';
            }
            if ($datos['alcance'] === 'producto' && empty($datos['producto_id'])) {
                return 'Selecciona el producto al que aplica la promoción.';
            }
            if ($datos['alcance'] === 'categoria' && empty($datos['categoria_id'])) {
                return 'Selecciona la categoría a la que aplica la promoción.';
            }
            if ($datos['fecha_inicio'] !== null && $datos['fecha_fin'] !== null && $datos['fecha_fin'] < $datos['fecha_inicio']) {
                return 'La fecha final debe ser igual o posterior a la fecha inicial.';
            }
            if ($datos['fecha_inicio'] !== null && ! $this->fechaValida($datos['fecha_inicio'])) {
                return 'La fecha inicial de la promoción no es válida.';
            }
            if ($datos['fecha_fin'] !== null && ! $this->fechaValida($datos['fecha_fin'])) {
                return 'La fecha final de la promoción no es válida.';
            }
            if (! ctype_digit((string) ($datos['usos_maximos'] ?? '0'))) {
                return 'El límite de usos debe ser un número entero.';
            }
            if ($datos['alcance'] === 'producto' && db_connect()->table('productos')->where('id', (int) $datos['producto_id'])->where('activo', 1)->where('deleted_at', null)->countAllResults() === 0) {
                return 'El producto seleccionado no está disponible.';
            }
            if ($datos['alcance'] === 'categoria' && db_connect()->table('categorias')->where('id', (int) $datos['categoria_id'])->where('activo', 1)->where('deleted_at', null)->countAllResults() === 0) {
                return 'La categoría seleccionada no está disponible.';
            }
        }

        return null;
    }

    private function fechaValida(string $fecha): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $date !== false && $date->format('Y-m-d') === $fecha;
    }

    private function opcionesProductos(): array
    {
        $opciones = [];
        foreach ($this->productosActivos(true) as $producto) {
            $opciones[(string) $producto['id']] = $producto['sku'] . ' — ' . $producto['nombre'];
        }
        return $opciones;
    }

    private function opcionesCategorias(): array
    {
        $opciones = [];
        foreach ($this->catalogo('categorias') as $categoria) {
            $opciones[(string) $categoria['id']] = $categoria['nombre'];
        }
        return $opciones;
    }

    private function crearComprobante($db, int $ventaId, ?array $cliente, string $folio, float $subtotal, float $descuento, float $base, float $impuesto, float $total, array $detalle): ?int
    {
        $consecutivoRow = $db->query(
            'SELECT valor FROM configuracion WHERE clave = ? FOR UPDATE',
            ['comprobante_consecutivo']
        )->getRowArray();
        if ($consecutivoRow === null) {
            return null;
        }

        $consecutivo = (int) $consecutivoRow['valor'];
        if ($consecutivo < 1 || $consecutivo > 50000) {
            return null;
        }

        $ajustes = [];
        foreach ($db->table('configuracion')->whereIn('clave', [
            'comprobante_prefijo',
            'resolucion_dian',
            'empresa_nombre',
            'empresa_razon_social',
            'empresa_nit',
            'empresa_direccion',
            'empresa_telefono',
            'empresa_ciudad',
        ])->get()->getResultArray() as $ajuste) {
            $ajustes[$ajuste['clave']] = $ajuste['valor'];
        }

        $prefijo = $ajustes['comprobante_prefijo'] ?? 'KR';
        $numero = $prefijo . '-' . str_pad((string) $consecutivo, 8, '0', STR_PAD_LEFT);
        $emisor = [
            'nombre'        => $ajustes['empresa_nombre'] ?? 'Ke-Rico',
            'razon_social'  => $ajustes['empresa_razon_social'] ?? '',
            'nit'           => $ajustes['empresa_nit'] ?? '',
            'direccion'     => $ajustes['empresa_direccion'] ?? '',
            'telefono'      => $ajustes['empresa_telefono'] ?? '',
            'ciudad'        => $ajustes['empresa_ciudad'] ?? '',
        ];
        $clienteSnapshot = $cliente === null
            ? ['tipo_documento' => 'NA', 'documento' => '', 'nombre' => 'Consumidor final', 'telefono' => '', 'correo' => '', 'direccion' => '']
            : [
                'tipo_documento' => $cliente['tipo_documento'],
                'documento'      => $cliente['documento'],
                'nombre'         => $cliente['nombre'],
                'telefono'       => $cliente['telefono'],
                'correo'         => $cliente['correo'],
                'direccion'      => $cliente['direccion'],
            ];

        $db->table('comprobantes')->insert([
            'venta_id'       => $ventaId,
            'numero'         => $numero,
            'prefijo'        => $prefijo,
            'consecutivo'    => $consecutivo,
            'tipo'           => 'Tirilla POS',
            'resolucion_dian' => $ajustes['resolucion_dian'] ?? '',
            'emisor'         => json_encode($emisor, JSON_UNESCAPED_UNICODE),
            'cliente'        => json_encode($clienteSnapshot, JSON_UNESCAPED_UNICODE),
            'detalle'        => json_encode($detalle, JSON_UNESCAPED_UNICODE),
            'subtotal'       => number_format($subtotal, 2, '.', ''),
            'descuento'      => number_format($descuento, 2, '.', ''),
            'base_gravable'  => number_format($base, 2, '.', ''),
            'impuesto'       => number_format($impuesto, 2, '.', ''),
            'total'          => number_format($total, 2, '.', ''),
            'estado'         => 'Emitido',
        ]);
        if (! $db->transStatus()) {
            return null;
        }
        $comprobanteId = (int) $db->insertID();

        $db->table('configuracion')
            ->where('clave', 'comprobante_consecutivo')
            ->update(['valor' => (string) ($consecutivo + 1)]);

        return $comprobanteId;
    }

    private function registrarBitacora($db, string $accion, string $modulo, string $descripcion, string $entidad, string $entidadId, ?array $antes = null, ?array $despues = null): void
    {
        $db->table('bitacora')->insert([
            'usuario_id'     => (int) session()->get('user_id'),
            'usuario_nombre' => (string) session()->get('nombre'),
            'accion'         => $accion,
            'modulo'         => $modulo,
            'descripcion'    => $descripcion,
            'nivel'          => 'Info',
            'entidad'        => $entidad,
            'entidad_id'     => $entidadId,
            'datos_antes'    => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE),
            'datos_despues'  => $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE),
            'ip'             => $this->request->getIPAddress(),
            'user_agent'     => substr($this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);
    }
}
