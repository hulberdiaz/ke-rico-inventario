<?php

namespace App\Controllers;

class Inventario extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $fechaInicio = date('Y-m-d 00:00:00');

        $productos = $db->table('productos')
            ->where('activo', 1)
            ->where('deleted_at', null)
            ->where('es_insumo', 0)
            ->countAllResults();

        $insumos = $db->table('productos')
            ->where('activo', 1)
            ->where('deleted_at', null)
            ->where('es_insumo', 1)
            ->countAllResults();

        $alertas = $db->table('productos')
            ->where('activo', 1)
            ->where('deleted_at', null)
            ->where('stock_actual <= punto_reorden', null, false)
            ->countAllResults();

        $ventasHoy = $db->table('ventas')
            ->selectSum('total', 'total')
            ->where('estado', 'Completada')
            ->where('fecha >=', $fechaInicio)
            ->get()
            ->getRowArray();

        $bajoStock = $db->table('productos p')
            ->select('p.sku, p.nombre, p.stock_actual, p.punto_reorden, p.unidad_medida, c.nombre AS categoria')
            ->join('categorias c', 'c.id = p.categoria_id')
            ->where('p.activo', 1)
            ->where('p.deleted_at', null)
            ->where('p.stock_actual <= p.punto_reorden', null, false)
            ->orderBy('p.stock_actual', 'ASC')
            ->limit(8)
            ->get()
            ->getResultArray();

        $ventasRecientes = $db->table('ventas v')
            ->select('v.folio, v.fecha, v.total, v.estado, u.nombre AS cajero, u.apellido AS apellido_cajero')
            ->join('usuarios u', 'u.id = v.usuario_id')
            ->orderBy('v.fecha', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        return view('inventario/dashboard', [
            'titulo'         => 'Resumen del inventario',
            'productos'      => $productos,
            'insumos'        => $insumos,
            'alertas'        => $alertas,
            'ventasHoy'      => (float) ($ventasHoy['total'] ?? 0),
            'bajoStock'      => $bajoStock,
            'ventasRecientes' => $ventasRecientes,
        ]);
    }

    public function productos(): string
    {
        $db = db_connect();
        $busqueda = trim((string) $this->request->getGet('q'));
        $tipo = (string) $this->request->getGet('tipo');
        $categoriaId = (string) $this->request->getGet('categoria');

        $builder = $db->table('productos p')
            ->select('p.id, p.sku, p.nombre, p.descripcion, p.precio_costo, p.precio_venta, p.stock_actual, p.punto_reorden, p.unidad_medida, p.es_insumo, c.nombre AS categoria')
            ->join('categorias c', 'c.id = p.categoria_id')
            ->where('p.activo', 1)
            ->where('p.deleted_at', null);

        if ($busqueda !== '') {
            $builder->groupStart()
                ->like('p.nombre', $busqueda)
                ->orLike('p.sku', $busqueda)
                ->orLike('p.descripcion', $busqueda)
                ->groupEnd();
        }

        if ($tipo === 'insumos') {
            $builder->where('p.es_insumo', 1);
        } elseif ($tipo === 'venta') {
            $builder->where('p.es_insumo', 0);
        } else {
            $tipo = 'todos';
        }

        if (ctype_digit($categoriaId) && $categoriaId !== '0') {
            $builder->where('p.categoria_id', (int) $categoriaId);
        } else {
            $categoriaId = '';
        }

        $builder->orderBy('p.nombre');
        [$productos, $pager] = $this->paginar($builder, 25);

        return view('inventario/productos', [
            'titulo'      => 'Productos e insumos',
            'productos'   => $productos,
            'pager'       => $pager,
            'categorias'  => $db->table('categorias')->where('deleted_at', null)->orderBy('nombre')->get()->getResultArray(),
            'busqueda'    => $busqueda,
            'tipo'        => $tipo,
            'categoriaId' => $categoriaId,
        ]);
    }

    public function categorias(): string
    {
        $categorias = db_connect()->table('categorias c')
            ->select('c.id, c.nombre, c.descripcion, c.activo, COUNT(p.id) AS cantidad_productos')
            ->join('productos p', 'p.categoria_id = c.id AND p.deleted_at IS NULL', 'left')
            ->where('c.deleted_at', null)
            ->groupBy('c.id')
            ->orderBy('c.nombre')
            ->get()
            ->getResultArray();

        return view('inventario/categorias', [
            'titulo'     => 'Categorías',
            'categorias' => $categorias,
        ]);
    }

    public function alertas(): string
    {
        $productos = db_connect()->table('productos p')
            ->select("p.sku, p.nombre, p.stock_actual, p.punto_reorden, p.unidad_medida, c.nombre AS categoria, CASE WHEN p.stock_actual <= 0 THEN 'Agotado' WHEN p.stock_actual <= p.punto_reorden * 0.5 THEN 'Crítico' ELSE 'Bajo' END AS estado_stock", false)
            ->join('categorias c', 'c.id = p.categoria_id')
            ->where('p.activo', 1)
            ->where('p.deleted_at', null)
            ->where('p.stock_actual <= p.punto_reorden', null, false)
            ->orderBy('p.stock_actual', 'ASC')
            ->get()
            ->getResultArray();

        return view('inventario/alertas', [
            'titulo'   => 'Alertas de existencias',
            'productos' => $productos,
        ]);
    }

    public function ventas(): string
    {
        $db = db_connect();
        $builder = $db->table('ventas v')
            ->select('v.id, v.folio, v.fecha, v.total, v.metodo_pago, v.estado, u.nombre AS cajero, u.apellido AS apellido_cajero, cl.nombre AS cliente, c.id AS comprobante_id')
            ->join('usuarios u', 'u.id = v.usuario_id')
            ->join('clientes cl', 'cl.id = v.cliente_id', 'left')
            ->join('comprobantes c', 'c.venta_id = v.id', 'left')
            ->orderBy('v.fecha', 'DESC');

        [$ventas, $pager] = $this->paginar($builder, 25);

        return view('inventario/ventas', [
            'titulo' => 'Ventas',
            'ventas' => $ventas,
            'pager'  => $pager,
        ]);
    }

    public function comprobante(int $id): string
    {
        $comprobante = db_connect()->table('comprobantes')
            ->where('id', $id)
            ->where('estado', 'Emitido')
            ->get()
            ->getRowArray();

        if ($comprobante === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('No se encontró el comprobante.');
        }

        $comprobante['emisor'] = json_decode($comprobante['emisor'], true) ?: [];
        $comprobante['cliente'] = json_decode($comprobante['cliente'], true) ?: [];
        $comprobante['detalle'] = json_decode($comprobante['detalle'], true) ?: [];

        return view('inventario/comprobante', ['comprobante' => $comprobante]);
    }

    public function movimientos(): string
    {
        $db = db_connect();
        $builder = $db->table('movimientos_inventario m')
            ->select('m.tipo, m.motivo, m.cantidad, m.stock_anterior, m.stock_nuevo, p.unidad_medida, m.creado_en, p.sku, p.nombre AS producto, u.nombre AS usuario, u.apellido AS apellido_usuario')
            ->join('productos p', 'p.id = m.producto_id')
            ->join('usuarios u', 'u.id = m.usuario_id')
            ->orderBy('m.creado_en', 'DESC');

        [$movimientos, $pager] = $this->paginar($builder, 25);

        return view('inventario/movimientos', [
            'titulo'      => 'Movimientos de inventario',
            'movimientos' => $movimientos,
            'pager'       => $pager,
        ]);
    }

    public function proveedores(): string
    {
        $rows = db_connect()->table('proveedores')
            ->select('nombre_empresa, nit, contacto_nombre, telefono, correo, estado, calificacion')
            ->where('deleted_at', null)
            ->orderBy('nombre_empresa')
            ->get()
            ->getResultArray();

        $puedeCrear = in_array((int) session()->get('rol_id'), [1, 2, 4], true);
        return $this->tablaRegistros('Proveedores', $rows, [
            'nombre_empresa' => 'Empresa',
            'nit'            => 'NIT',
            'contacto_nombre' => 'Contacto',
            'telefono'       => 'Teléfono',
            'correo'         => 'Correo',
            'estado'         => 'Estado',
            'calificacion'   => 'Calificación',
        ], $puedeCrear ? site_url('inventario/catalogos/proveedores/nuevo') : null);
    }

    public function clientes(): string
    {
        $rows = db_connect()->table('clientes')
            ->select('tipo_documento, documento, nombre, telefono, correo, direccion')
            ->where('deleted_at', null)
            ->orderBy('nombre')
            ->get()
            ->getResultArray();

        $puedeCrear = in_array((int) session()->get('rol_id'), [1, 2, 3], true);
        return $this->tablaRegistros('Clientes', $rows, [
            'nombre'         => 'Nombre',
            'tipo_documento' => 'Tipo de documento',
            'documento'      => 'Documento',
            'telefono'       => 'Teléfono',
            'correo'         => 'Correo',
            'direccion'      => 'Dirección',
        ], $puedeCrear ? site_url('inventario/catalogos/clientes/nuevo') : null);
    }

    public function compras(): string
    {
        $rows = db_connect()->table('compras c')
            ->select('c.numero, c.fecha, p.nombre_empresa AS proveedor, u.usuario AS usuario, c.total, c.estado')
            ->join('proveedores p', 'p.id = c.proveedor_id')
            ->join('usuarios u', 'u.id = c.usuario_id')
            ->orderBy('c.fecha', 'DESC')
            ->get()
            ->getResultArray();

        $puedeCrear = in_array((int) session()->get('rol_id'), [1, 2, 4], true);
        return $this->tablaRegistros('Compras', $rows, [
            'numero'    => 'Número',
            'fecha'     => 'Fecha',
            'proveedor' => 'Proveedor',
            'usuario'   => 'Registró',
            'total'     => 'Total',
            'estado'    => 'Estado',
        ], $puedeCrear ? site_url('inventario/compras/nueva') : null);
    }

    public function promociones(): string
    {
        $rows = db_connect()->table('promociones')
            ->select('codigo, descripcion, tipo, valor, alcance, fecha_inicio, fecha_fin, usos_actuales, usos_maximos, activa')
            ->where('deleted_at', null)
            ->orderBy('codigo')
            ->get()
            ->getResultArray();

        $puedeCrear = in_array((int) session()->get('rol_id'), [1, 2], true);
        return $this->tablaRegistros('Promociones', $rows, [
            'codigo'       => 'Código',
            'descripcion'  => 'Descripción',
            'tipo'         => 'Tipo',
            'valor'        => 'Valor',
            'alcance'      => 'Aplica a',
            'fecha_inicio' => 'Desde',
            'fecha_fin'    => 'Hasta',
            'usos_actuales' => 'Usos',
            'usos_maximos' => 'Límite',
            'activa'       => 'Activa',
        ], $puedeCrear ? site_url('inventario/catalogos/promociones/nuevo') : null);
    }

    public function bitacora(): string
    {
        $rows = db_connect()->table('bitacora')
            ->select('creado_en, usuario_nombre, accion, modulo, descripcion, nivel')
            ->orderBy('creado_en', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();

        return $this->tablaRegistros('Bitácora del sistema', $rows, [
            'creado_en'     => 'Fecha',
            'usuario_nombre' => 'Usuario',
            'accion'        => 'Acción',
            'modulo'        => 'Módulo',
            'descripcion'   => 'Detalle',
            'nivel'         => 'Nivel',
        ]);
    }

    public function usuarios(): string
    {
        $rows = db_connect()->table('usuarios u')
            ->select('u.usuario, u.nombre, u.apellido, u.correo, u.cargo, r.nombre AS rol, u.activo')
            ->join('roles r', 'r.id = u.rol_id')
            ->where('u.deleted_at', null)
            ->orderBy('u.nombre')
            ->get()
            ->getResultArray();

        return $this->tablaRegistros('Usuarios del sistema', $rows, [
            'usuario' => 'Usuario',
            'nombre'  => 'Nombre',
            'apellido' => 'Apellido',
            'correo'  => 'Correo',
            'cargo'   => 'Cargo',
            'rol'     => 'Rol',
            'activo'  => 'Activo',
        ], site_url('inventario/usuarios/nuevo'));
    }

    public function respaldos(): string
    {
        $rows = db_connect()->table('respaldos')
            ->select('nombre_archivo, tipo, estado, tamano_bytes, iniciado_en, finalizado_en, mensaje')
            ->orderBy('iniciado_en', 'DESC')
            ->get()
            ->getResultArray();

        return $this->tablaRegistros('Historial de respaldos', $rows, [
            'nombre_archivo' => 'Archivo',
            'tipo'           => 'Tipo',
            'estado'         => 'Estado',
            'tamano_bytes'   => 'Tamaño (bytes)',
            'iniciado_en'    => 'Iniciado',
            'finalizado_en'  => 'Finalizado',
            'mensaje'        => 'Detalle',
        ]);
    }

    public function configuracion(): string
    {
        return view('inventario/configuracion', [
            'titulo' => 'Configuración del sistema',
            'ajustes' => db_connect()->table('configuracion')->orderBy('clave')->get()->getResultArray(),
        ]);
    }

    public function reportes(): string
    {
        $db = db_connect();
        $inventario = $db->table('productos')
            ->select('COALESCE(SUM(stock_actual * precio_costo), 0) AS valor, COUNT(*) AS cantidad', false)
            ->where('activo', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $ventasMes = $db->table('ventas')
            ->select('COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total', false)
            ->where('estado', 'Completada')
            ->where('fecha >=', date('Y-m-01 00:00:00'))
            ->get()
            ->getRowArray();

        $ventasDia = $db->table('ventas')
            ->select('DATE(fecha) AS dia, COUNT(*) AS cantidad, SUM(total) AS total', false)
            ->where('estado', 'Completada')
            ->where('fecha >=', date('Y-m-d 00:00:00', strtotime('-29 days')))
            ->groupBy('DATE(fecha)')
            ->orderBy('dia', 'DESC')
            ->get()
            ->getResultArray();

        return view('inventario/reportes', [
            'titulo'         => 'Reportes',
            'inventario'     => $inventario,
            'ventasMes'      => $ventasMes,
            'ventasDia'      => $ventasDia,
        ]);
    }

    private function tablaRegistros(string $titulo, array $rows, array $columns, ?string $createUrl = null): string
    {
        return view('inventario/registros', [
            'titulo'  => $titulo,
            'rows'    => $rows,
            'columns' => $columns,
            'createUrl' => $createUrl,
        ]);
    }

    private function paginar($builder, int $porPagina): array
    {
        $pagina = max(1, (int) $this->request->getGet('page'));
        $total = $builder->countAllResults(false);
        $ultimaPagina = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $ultimaPagina);
        $rows = $builder->limit($porPagina, ($pagina - 1) * $porPagina)->get()->getResultArray();
        $links = service('pager')->makeLinks($pagina, $porPagina, $total, 'inventory_newtab');

        return [$rows, $links];
    }
}
