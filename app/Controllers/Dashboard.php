<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        return redirect()->to(site_url('inventario'));
    }
}