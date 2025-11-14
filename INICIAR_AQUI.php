<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio Rápido - GASELAG</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            text-align: center;
        }
        h1 {
            color: #667eea;
            margin-top: 0;
            font-size: 2.5em;
        }
        .emoji {
            font-size: 5em;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            margin: 10px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 1.2em;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .button.secondary {
            background: #6c757d;
        }
        .info {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .steps {
            text-align: left;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="emoji">🚀</div>
        <h1>GASELAG</h1>
        <p style="font-size: 1.2em; color: #666;">Sistema de Retiro de Medidores</p>
        
        <div class="info">
            <strong>✅ Puerto MySQL: 3307 (Configurado)</strong>
            <br><br>
            <strong>🔐 Sistema con Autenticación</strong><br>
            El sistema ahora incluye login con roles de usuario
        </div>

        <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;">
            <strong>👤 Usuarios por Defecto:</strong><br>
            <strong>Admin:</strong> admin / password<br>
            <strong>Técnico 1:</strong> tecnico1 / password<br>
            <strong>Técnico 2:</strong> tecnico2 / password
        </div>

        <div class="steps">
            <h3>🎯 Antes de comenzar:</h3>
            <ol>
                <li>Abre <strong>XAMPP Control Panel</strong></li>
                <li>Haz clic en <strong>Start</strong> en Apache ✅</li>
                <li>Haz clic en <strong>Start</strong> en MySQL ✅</li>
            </ol>
        </div>

        <h2>¿Qué deseas hacer?</h2>
        
        <div style="margin: 30px 0;">
            <a href="instalar.php" class="button">
                🔧 Instalar Sistema
            </a>
            <br>
            <small style="color: #666;">Instalación normal con datos de ejemplo</small>
        </div>

        <div style="margin: 30px 0;">
            <a href="login.php" class="button">
                🔐 Iniciar Sesión
            </a>
            <br>
            <small style="color: #666;">Acceder al sistema (después de instalar)</small>
        </div>

        <div style="margin: 30px 0;">
            <a href="index.php" class="button secondary">
                🏠 Panel Principal
            </a>
            <br>
            <small style="color: #666;">Ir directamente al sistema (si ya tienes sesión)</small>
        </div>

        <div style="margin: 30px 0;">
            <a href="tools/verificar_instalacion.php" class="button secondary">
                🔍 Verificar Estado del Sistema
            </a>
            <br>
            <small style="color: #666;">Herramienta de diagnóstico (tools/)</small>
        </div>

        <hr style="margin: 30px 0;">

        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
            <strong>⚠️ Primera Instalación:</strong><br>
            1. Asegúrate de que Apache y MySQL estén iniciados en XAMPP<br>
            2. Haz clic en <strong>"🔧 Instalar Sistema"</strong><br>
            3. Espera a que termine la instalación<br>
            4. Haz clic en <strong>"🔐 Iniciar Sesión"</strong> con las credenciales de arriba<br>
            <br>
            <strong>✅ Sistema ya instalado?</strong> Ve directamente a <strong>"🔐 Iniciar Sesión"</strong>
        </div>
    </div>
</body>
</html>

