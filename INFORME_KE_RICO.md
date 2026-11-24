# Informe funcional — Sistema Ke-Rico

## 1. Objetivo

Ke-Rico es una aplicación web para apoyar la operación de un negocio de
alimentos: consultar productos e insumos, controlar existencias, registrar
ventas y compras, y revisar la actividad del sistema. La aplicación está
construida con PHP y CodeIgniter 4 y utiliza MySQL.

## 2. Acceso y navegación

- Inicio de sesión por usuario o correo y contraseña.
- Al entrar, la aplicación muestra el resumen del inventario.
- Los permisos se determinan por el rol asociado a la cuenta.
- La mayoría de los enlaces del menú abre una pestaña nueva; “Salir” cierra la
  sesión en la pestaña actual.
- URL local: `http://localhost/invenkerico/public/index.php/login`

Los nombres de acceso informados para la base de prueba son `admin`,
`gerente`, `cajero` e `inventario`. Este informe no incluye contraseñas.

## 3. Módulos

### Resumen del inventario

Presenta cantidades de productos e insumos activos, alertas de stock, valor de
ventas completadas del día, productos por debajo del punto de reposición y
ventas recientes.

### Productos, categorías y existencias

- Consulta productos e insumos, con búsqueda y filtros por tipo y categoría.
- Permite registrar y editar productos a los perfiles autorizados.
- Muestra alertas cuando las existencias llegan al punto de reposición.
- Registra entradas, salidas, mermas, devoluciones y ajustes de conteo.
- Los movimientos guardan producto, motivo, usuario y stock anterior/nuevo.
- Las listas de productos, ventas y movimientos muestran hasta 25 registros
  por página.

### Ventas

El formulario permite seleccionar productos de venta, cantidades, cliente y,
cuando corresponda, una promoción. El sistema valida existencias, calcula los
importes y registra el movimiento de salida del stock. También genera un
comprobante interno asociado a la venta.

### Compras

Permite registrar compras recibidas de proveedores y sus productos. Al
completar la operación, aumenta las existencias y deja movimientos asociados.

### Catálogos y administración

Incluye consultas de clientes, proveedores y categorías; creación de registros
según los permisos del rol; promociones; reportes de inventario y ventas;
bitácora de actividad; administración de usuarios; configuración; e historial
de respaldos.

Los reportes resumen el valor estimado del inventario y las ventas del mes y
de los últimos 30 días. La bitácora muestra hasta 200 actividades recientes.

## 4. Perfiles y permisos

| Usuario informado | Perfil | Funciones principales |
|---|---|---|
| `admin` | Administrador | Acceso general; puede crear usuarios y consultar configuración, reportes, bitácora e historial de respaldos. También tiene acceso a ventas, compras, productos y existencias. |
| `gerente` | Gerente | Supervisa ventas, compras, productos, inventario, clientes, proveedores y promociones; puede consultar reportes y bitácora. No administra usuarios ni configuración o respaldos. |
| `cajero` | Cajero | Consulta productos, existencias, ventas y clientes; puede registrar ventas y comprobantes. No registra compras ni ajustes de stock. |
| `inventario` | Inventario | Administra productos y existencias, registra ajustes y compras, y consulta movimientos, proveedores y ventas. No registra ventas ni administra usuarios. |

Los permisos también se verifican en las rutas del servidor, no solamente
ocultando opciones del menú. Por ejemplo, promociones están limitadas a
Administrador y Gerente; la creación de clientes a Administrador, Gerente y
Cajero; y la creación de proveedores a Administrador, Gerente e Inventario.

## 5. Datos y protección

La aplicación utiliza tablas para usuarios y roles, productos, categorías,
clientes, proveedores, ventas y detalles, compras y detalles, promociones,
movimientos de inventario, comprobantes, bitácora y configuración.

El inicio de sesión verifica contraseñas almacenadas como hash. Las operaciones
de venta, compra y stock usan transacciones para mantener relacionados los
cambios de existencias y sus registros.

El archivo local `kerico.sql` no se modificó ni se incluye en el paquete de
código: contiene registros de prueba que deben revisarse antes de compartirlos.
Para instalar otra copia se debe usar un esquema y datos de prueba autorizados,
nunca datos de producción.

## 6. Alcance y pendientes

- El comprobante es interno; no es una factura electrónica ni una factura DIAN.
- La sección de respaldos consulta un historial; no genera ni descarga un
  archivo de respaldo.
- Las operaciones de escritura deben probarse completamente con una copia de
  la base de datos antes de usar el sistema con datos reales.
- El informe describe las funciones implementadas; no reemplaza pruebas de
  aceptación, revisión de seguridad ni validación contable.
