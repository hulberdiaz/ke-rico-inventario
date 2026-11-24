<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Auth extends BaseController
{
    private const MAX_INTENTOS_FALLIDOS = 5;
    private const DURACION_BLOQUEO_MINUTOS = 15;

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

        if (! $user) {
            return redirect()->back()->with('error', 'Usuario o contraseña incorrectos.');
        }

        $bloqueadoHasta = ! empty($user['bloqueado_hasta']) ? strtotime($user['bloqueado_hasta']) : false;
        if ($bloqueadoHasta !== false && $bloqueadoHasta > time()) {
            return redirect()->back()->with('error', 'La cuenta está bloqueada temporalmente.');
        }

        if ($bloqueadoHasta !== false && $bloqueadoHasta <= time()) {
            if (! $usuarioModel->update($user['id'], [
                'intentos_fallidos' => 0,
                'bloqueado_hasta'   => null,
            ])) {
                return redirect()->back()->with('error', 'No se pudo actualizar el acceso. Inténtalo de nuevo.');
            }

            $user['intentos_fallidos'] = 0;
        }

        if (! password_verify($clave, $user['password_hash'])) {
            $intentosFallidos = (int) $user['intentos_fallidos'] + 1;
            $actualizacion = ['intentos_fallidos' => $intentosFallidos];
            $bloqueoAplicado = $intentosFallidos >= self::MAX_INTENTOS_FALLIDOS;

            if ($bloqueoAplicado) {
                $actualizacion['bloqueado_hasta'] = date(
                    'Y-m-d H:i:s',
                    time() + self::DURACION_BLOQUEO_MINUTOS * 60
                );
            }

            if (! $usuarioModel->update($user['id'], $actualizacion)) {
                return redirect()->back()->with('error', 'No se pudo registrar el intento de acceso. Inténtalo de nuevo.');
            }

            $mensaje = $bloqueoAplicado
                ? 'La cuenta fue bloqueada durante 15 minutos tras varios intentos fallidos.'
                : 'Usuario o contraseña incorrectos.';

            return redirect()->back()->with('error', $mensaje);
        }

        if (! $usuarioModel->update($user['id'], [
            'ultimo_acceso'     => date('Y-m-d H:i:s'),
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
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