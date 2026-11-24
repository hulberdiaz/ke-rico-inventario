<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Sistema de Registro e Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .split-container {
            display: flex;
            min-height: 100vh;
        }
        .left-panel {
            flex: 1;
            background: linear-gradient(160deg, #f36c13 0%, #d95708 100%);
            color: #ffffff;
            padding: 3rem 4rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .brand-mark {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            border: 2px solid rgba(255, 255, 255, .85);
            border-radius: 14px;
            color: #ffffff;
            font-size: 30px;
            font-weight: 800;
        }
        .right-panel {
            flex: 1;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-box {
            width: 100%;
            max-width: 380px;
        }
        .form-label-custom {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 0.4rem;
        }
        .form-control-custom {
            background-color: #eef4fc;
            border: 1px solid #dbeafe;
            border-radius: 6px;
            padding: 0.65rem 0.9rem;
            font-size: 0.95rem;
        }
        .form-control-custom:focus {
            background-color: #ffffff;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15);
        }
        .btn-entrar {
            background-color: #ea580c;
            color: #ffffff;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            padding: 0.68rem;
            transition: background-color 0.2s;
        }
        .btn-entrar:hover {
            background-color: #c2410c;
            color: #ffffff;
        }
        @media (max-width: 768px) {
            .left-panel { display: none; }
        }
    </style>
</head>
<body>
    <div class="split-container">
        <!-- Panel izquierdo naranja -->
        <div class="left-panel">
            <div>
                <div class="brand-mark" aria-label="Ke-Rico">K</div>
            </div>
            <div>
                <h1 class="fw-bold display-6 mb-3">Sistema de Registro<br>e Inventario</h1>
                <p class="mb-0 opacity-90" style="max-width: 420px; line-height: 1.5;">
                    Controle las ventas, las existencias, los comprobantes y la rentabilidad del negocio desde un solo lugar.
                </p>
            </div>
            <div class="small opacity-75">
                Av. Cusco 145, Espinar - Cusco
            </div>
        </div>

        <!-- Panel derecho formulario -->
        <div class="right-panel">
            <div class="login-box">
                <h3 class="fw-bold text-dark mb-1">Iniciar sesión</h3>
                <p class="text-muted small mb-4">Ingrese con la cuenta que le asignó el administrador.</p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger py-2 small">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('login/ingresar') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label-custom">USUARIO O CORREO</label>
                        <input type="text" name="usuario" class="form-control form-control-custom" placeholder="admin" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom">CONTRASEÑA</label>
                        <input type="password" name="password" class="form-control form-control-custom" placeholder="••••••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-entrar w-100 mb-3">Entrar</button>
                </form>

                <div class="text-center">
                    <a href="#" class="text-muted small text-decoration-none">Olvidé mi contraseña</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>