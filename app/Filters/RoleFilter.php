<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleId = (int) session()->get('rol_id');
        $allowedRoles = array_map('intval', $arguments ?? []);

        if (session()->get('user_id') === null || ! in_array($roleId, $allowedRoles, true)) {
            return redirect()->to(site_url('inventario'))
                ->with('error', 'Tu usuario no tiene permiso para realizar esta acción.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
