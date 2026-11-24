<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields    = [
        'usuario', 'password_hash', 'nombre', 'apellido',
        'tipo_documento', 'documento', 'fecha_nac', 'correo',
        'telefono', 'cargo', 'rol_id', 'activo',
        'intentos_fallidos', 'bloqueado_hasta', 'ultimo_acceso'
    ];

    /**
     * Busca un usuario activo por su nombre de usuario o correo electrónico
     */
    public function buscarPorCredencial(string $credencial): ?array
    {
        return $this->select('usuarios.*, roles.nombre AS rol_nombre')
                    ->join('roles', 'roles.id = usuarios.rol_id')
                    ->where('usuarios.activo', 1)
                    ->where('roles.activo', 1)
                    ->groupStart()
                        ->where('usuarios.usuario', $credencial)
                        ->orWhere('usuarios.correo', $credencial)
                    ->groupEnd()
                    ->first();
    }

    public function registrarIntentoFallido(int $id, int $maxIntentos, int $duracionBloqueoMinutos): ?bool
    {
        $this->db->transBegin();
        $usuario = $this->db->query(
            'SELECT intentos_fallidos, bloqueado_hasta FROM usuarios WHERE id = ? AND activo = 1 AND deleted_at IS NULL FOR UPDATE',
            [$id]
        )->getRowArray();

        if ($usuario === null) {
            $this->db->transRollback();
            return null;
        }

        $bloqueadoHasta = ! empty($usuario['bloqueado_hasta']) ? strtotime($usuario['bloqueado_hasta']) : false;
        if ($bloqueadoHasta !== false && $bloqueadoHasta > time()) {
            $this->db->transRollback();
            return true;
        }

        $intentosFallidos = $bloqueadoHasta !== false ? 1 : (int) $usuario['intentos_fallidos'] + 1;
        $bloqueoAplicado = $intentosFallidos >= $maxIntentos;
        $actualizacion = [
            'intentos_fallidos' => $intentosFallidos,
            'bloqueado_hasta'   => $bloqueoAplicado
                ? date('Y-m-d H:i:s', time() + $duracionBloqueoMinutos * 60)
                : null,
        ];

        if (! $this->update($id, $actualizacion) || ! $this->db->transStatus()) {
            $this->db->transRollback();
            return null;
        }

        $this->db->transCommit();
        return $bloqueoAplicado;
    }
}