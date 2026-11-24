<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Auth extends BaseController
{
    public function index()
    {
        if (session()->get('user_id') !== null) {
            return redirect()->to(site_url('inventario'));
        }

        return view('login');
    }

    public function ingresar()
    {
        $credencial = trim((string) $this->request->getPost('usuario'));
        $clave      = (string) $this->request->getPost('password');

        if ($credencial === '' || $clave === '') {
            return redirect()->back()->with('error', 'Ingresa tu usuario y contraseña.');
        }

        $usuarioModel = new UsuarioModel();
        $user = $usuarioModel->buscarPorCredencial($credencial);

        if (! $user || ! password_verify($clave, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Usuario o contraseña incorrectos.');
        }

        if (! empty($user['bloqueado_hasta']) && strtotime($user['bloqueado_hasta']) > time()) {
            return redirect()->back()->with('error', 'La cuenta está bloqueada temporalmente.');
        }

        if (! $usuarioModel->update($user['id'], [
            'ultimo_acceso'     => date('Y-m-d H:i:s'),
            'intentos_fallidos' => 0,
        ])) {
            return redirect()->back()->with('error', 'No se pudo actualizar el acceso. Inténtalo de nuevo.');
        }

        session()->regenerate(true);
        session()->set([
            'usuario_id' => $user['id'],
            'user_id'    => $user['id'],
            'usuario'    => $user['usuario'],
            'nombre'     => $user['nombre'] . ' ' . $user['apellido'],
            'cargo'      => $user['cargo'],
            'rol_id'     => $user['rol_id'],
            'rol'        => strtolower($user['rol_nombre']),
            'logueado'   => true,
        ]);

        return redirect()->to(site_url('inventario'));
    }

    public function salir()
    {
        session()->destroy();
        return redirect()->to(site_url('login'));
    }
}