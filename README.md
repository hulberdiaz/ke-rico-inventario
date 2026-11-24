# Ke-Rico — Inventario y ventas

Aplicación web en PHP y CodeIgniter 4 para administrar productos, existencias,
compras, ventas y usuarios.

## Requisitos

- PHP 8.2 o superior con las extensiones `intl`, `mbstring` y `mysqli`.
- MySQL o MariaDB.
- Composer.

## Preparar una copia local

1. Instala las dependencias con `composer install`.
2. Copia `.env.example` como `.env` y configura la URL y los datos de conexión a
   tu base de datos.
3. Crea la base de datos `kerico` e importa un esquema y datos de prueba
   autorizados.
4. Inicia Apache y MySQL en WampServer. Abre:
   `http://localhost/invenkerico/public/index.php/login`

El archivo local `kerico.sql` y `.env` no se incluyen en el paquete. El SQL
contiene registros de usuarios/datos de prueba y debe revisarse antes de
compartirlo. Para ejecutar la aplicación, solicita un esquema limpio y una base
de datos de prueba; no uses datos de producción.

## Importante

- No publiques `.env`, contraseñas ni datos reales.
- Los respaldos y comprobantes disponibles en la aplicación son funciones
  internas de demostración; no equivalen a copias descargables ni a facturación
  electrónica.
