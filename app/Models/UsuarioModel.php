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
}