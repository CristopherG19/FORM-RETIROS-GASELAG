# 🔐 Flujo de Login con PIN o Contraseña - Sistema GASELAG

## 📋 Resumen del Sistema

El sistema tiene **2 tipos de usuarios** con diferentes políticas de seguridad:

| Rol | Credencial | Política | Cambio Forzado |
|-----|-----------|----------|----------------|
| **Admin** | Contraseña compleja | Mínimo 8 caracteres, mayúsculas, minúsculas, números, símbolos | ✅ Sí (primer login) |
| **Técnico** | PIN numérico | 4-8 dígitos, no secuencias, no repetidos | ✅ Sí (primer login) |

---

## 🔄 Flujo Completo del Login

```
┌─────────────────────────────────────────────────────────────────┐
│                    1. PANTALLA DE LOGIN                          │
│                                                                   │
│  Usuario ingresa:                                                │
│  • Username (DNI o usuario)                                      │
│  • Password/PIN                                                  │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│              2. VALIDACIÓN EN login.php                          │
│                                                                   │
│  ✓ Verifica token CSRF                                           │
│  ✓ Llama a función login($username, $password)                   │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│         3. FUNCIÓN login() en config/database.php                │
│                                                                   │
│  PASO 1: Verificar bloqueo de cuenta                             │
│  ├─ Bloqueado por intentos? → Mostrar mensaje de bloqueo        │
│  └─ No bloqueado? → Continuar                                    │
│                                                                   │
│  PASO 2: Buscar usuario en BD                                    │
│  ├─ SELECT * FROM usuarios WHERE username = ? AND estado='activo'│
│  └─ Usuario no existe? → Login fallido                           │
│                                                                   │
│  PASO 3: Verificar contraseña/PIN                                │
│  ├─ password_verify($password, $user['password'])                │
│  ├─ ✅ Correcto? → Continuar PASO 4                              │
│  └─ ❌ Incorrecto? → Registrar intento fallido                   │
│                                                                   │
│  PASO 4: Login exitoso                                           │
│  ├─ Resetear intentos_fallidos = 0                               │
│  ├─ Actualizar ultimo_login = NOW()                              │
│  ├─ Registrar en auditoría                                       │
│  ├─ Crear sesión ($_SESSION)                                     │
│  └─ Verificar force_password_change                              │
└─────────────────────────────────────────────────────────────────┘
                            ↓
              ┌─────────────┴─────────────┐
              │                           │
    force_password_change = TRUE      force_password_change = FALSE
              │                           │
              ↓                           ↓
┌──────────────────────────┐    ┌──────────────────────────┐
│  4A. PRIMER LOGIN        │    │  4B. LOGIN NORMAL        │
│  (Cambio Obligatorio)    │    │                          │
│                          │    │  → Pantalla de Bienvenida│
│  → cambiar_password.php  │    │  → Redirige a index.php  │
│     ?first_login=1       │    │                          │
└──────────────────────────┘    └──────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────────────────┐
│         5. CAMBIO DE CONTRASEÑA/PIN OBLIGATORIO                  │
│                                                                   │
│  El usuario VE:                                                  │
│  • Formulario bloqueado (no puede cancelar)                      │
│  • Mensaje: "Debe cambiar su contraseña en el primer acceso"    │
│                                                                   │
│  Campos:                                                         │
│  • Contraseña/PIN actual                                         │
│  • Nueva contraseña/PIN                                          │
│  • Confirmar nueva contraseña/PIN                                │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│       6. VALIDACIÓN SEGÚN ROL (cambiar_password.php)             │
│                                                                   │
│  SI es ADMIN:                                                    │
│  ├─ validatePassword($newPassword) en PasswordPolicy.php        │
│  ├─ Mínimo 8 caracteres                                          │
│  ├─ Debe contener: mayúsculas, minúsculas, números, símbolos    │
│  ├─ No puede ser igual al username o email                       │
│  ├─ No puede estar en historial (últimas 5 contraseñas)         │
│  └─ Calcula fuerza: débil/media/fuerte/muy fuerte               │
│                                                                   │
│  SI es TÉCNICO:                                                  │
│  ├─ validatePIN($newPassword) en PasswordPolicy.php             │
│  ├─ Solo dígitos numéricos                                       │
│  ├─ Entre 4 y 8 dígitos                                          │
│  ├─ No puede ser: 0000, 1111, 1234, 4321                        │
│  ├─ No puede ser secuencial (1234, 5678)                         │
│  ├─ No puede ser igual al DNI (username)                         │
│  └─ No puede tener todos dígitos iguales                         │
└─────────────────────────────────────────────────────────────────┘
                            ↓
                      ✅ Validación OK
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│              7. ACTUALIZACIÓN EN BASE DE DATOS                   │
│                                                                   │
│  UPDATE usuarios SET                                             │
│    password = ? (hash bcrypt cost 8),                            │
│    password_changed_at = NOW(),                                  │
│    force_password_change = FALSE                                 │
│  WHERE id = ?                                                    │
│                                                                   │
│  SI es ADMIN: Guardar en password_history                        │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│              8. PANTALLA DE ÉXITO Y REDIRECCIÓN                  │
│                                                                   │
│  → Muestra: "✓ Contraseña/PIN cambiado exitosamente"            │
│  → Animación con spinner                                         │
│  → Redirige a index.php después de 1 segundo                     │
└─────────────────────────────────────────────────────────────────┘
                            ↓
                  🎉 ACCESO COMPLETO AL SISTEMA
```

---

## 🔑 Detalles de Autenticación

### ¿Cómo sabe el sistema si validar PIN o Contraseña?

**Respuesta**: Por el **ROL del usuario**

```php
// En cambiar_password.php línea ~38
if ($isAdmin) {
    // Validar como CONTRASEÑA COMPLEJA
    $validation = validatePassword($newPassword, $_SESSION['username'], $email);
} else {
    // Validar como PIN NUMÉRICO
    $validation = validatePIN($newPassword, $_SESSION['username']);
}
```

### ¿Dónde se guarda la contraseña/PIN?

**En la misma columna**: `usuarios.password`

- **ADMIN**: Guarda hash de contraseña compleja (ej: "Admin2024!")
- **TÉCNICO**: Guarda hash de PIN numérico (ej: "8527")

Ambos se hashean con `password_hash()` y se verifican con `password_verify()`.

### ¿Por qué funciona con el mismo campo?

Porque `password_verify()` **solo compara hashes**, no le importa si originalmente fue "Admin2024!" o "8527". Solo verifica:

```php
password_verify("8527", $hash_guardado) // ✅ True si coincide
```

---

## 📝 Ejemplo Práctico

### Escenario 1: Admin crea técnico nuevo

```
1. Admin en gestion_usuarios_mejorado.php
2. Crea usuario: username="12345678", password="password", rol="user"
3. Sistema guarda: hash de "password", force_password_change=TRUE

4. Técnico hace login:
   - Usuario: 12345678
   - Contraseña: password ← (contraseña temporal)
   
5. Sistema detecta force_password_change=TRUE
   → Redirige a cambiar_password.php?first_login=1

6. Técnico cambia a PIN:
   - PIN actual: password
   - Nuevo PIN: 8527
   - Confirmar: 8527
   
7. Sistema detecta rol='user' → validatePIN("8527")
   ✅ 4 dígitos, no secuencial, no repetidos → VÁLIDO

8. Guarda hash("8527") en usuarios.password
   force_password_change = FALSE

9. Técnico ahora puede hacer login con:
   - Usuario: 12345678
   - PIN: 8527 ← (su PIN personal)
```

### Escenario 2: Admin crea otro admin

```
1. Admin crea usuario: username="admin2", password="password", rol="admin"
2. Sistema guarda: hash de "password", force_password_change=TRUE

3. Admin2 hace login:
   - Usuario: admin2
   - Contraseña: password
   
4. Sistema detecta force_password_change=TRUE
   → Redirige a cambiar_password.php?first_login=1

5. Admin2 cambia a contraseña compleja:
   - Contraseña actual: password
   - Nueva contraseña: Admin2024!Secure
   - Confirmar: Admin2024!Secure
   
6. Sistema detecta rol='admin' → validatePassword("Admin2024!Secure")
   ✅ 8+ caracteres, mayúsculas, minúsculas, números, símbolos → VÁLIDO

7. Guarda hash("Admin2024!Secure") en usuarios.password
   Guarda en password_history para evitar reutilización
   force_password_change = FALSE

8. Admin2 ahora puede hacer login con:
   - Usuario: admin2
   - Contraseña: Admin2024!Secure
```

---

## 🛡️ Políticas de Seguridad Activas

### Para Técnicos (PIN)
✅ 4-8 dígitos numéricos  
✅ No puede ser 0000, 1111, 1234, 4321  
✅ No puede ser secuencial  
✅ No puede ser igual al DNI  
✅ No puede tener todos dígitos iguales  
❌ No verifica historial de PINs anteriores  

### Para Administradores (Contraseña)
✅ Mínimo 8 caracteres  
✅ Debe contener mayúsculas (A-Z)  
✅ Debe contener minúsculas (a-z)  
✅ Debe contener números (0-9)  
✅ Debe contener símbolos (!@#$%^&*)  
✅ No puede ser igual al username o email  
✅ No puede estar en las últimas 5 contraseñas (historial)  
✅ Calcula fuerza con barra de progreso  

---

## 🚀 Mejora Propuesta: Password Automático

### Estado Actual
```php
// En el modal de crear usuario
<input type="password" class="form-control" name="password" required>
```
Admin debe escribir contraseña temporal manualmente.

### Propuesta
```php
// Eliminar campo de contraseña del formulario
// En el código PHP:
$password = 'password'; // Contraseña automática
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 8]);
```

**Ventajas:**
- ✅ Admin no tiene que pensar en contraseña temporal
- ✅ Consistencia: todos empiezan con "password"
- ✅ Simple de comunicar al nuevo usuario
- ✅ Seguro: force_password_change=TRUE obliga cambio inmediato
- ✅ Menos campos en el formulario = más simple

**Flujo simplificado:**
```
Admin crea usuario → Sistema asigna "password" automáticamente
Admin le dice al técnico: "Usuario: 12345678, Contraseña: password"
Técnico hace primer login → Debe cambiar a su PIN personal
```

---

## 📊 Resumen de Archivos Clave

| Archivo | Responsabilidad |
|---------|----------------|
| `login.php` | Formulario de login y validación inicial |
| `config/database.php` | Función `login()` - Verifica credenciales |
| `config/PasswordPolicy.php` | Valida PIN y contraseñas según rol |
| `pages/cambiar_password.php` | Formulario de cambio obligatorio/voluntario |
| `config/SecurityConfig.php` | Constantes de políticas de seguridad |

---

## ✨ Conclusión

Tu sistema **SÍ tiene lógica completa de PIN o Contraseña** diferenciada por rol:

1. ✅ **Login unificado**: Misma pantalla para ambos
2. ✅ **Detección automática**: Según rol en BD
3. ✅ **Validación diferenciada**: PIN para técnicos, contraseña para admins
4. ✅ **Mismo campo en BD**: `usuarios.password` almacena ambos (hasheados)
5. ✅ **Cambio forzado**: Primer login obliga a cambiar credencial temporal
6. ✅ **Seguridad robusta**: Bloqueos, intentos, auditoría

¿Quieres que implemente la mejora de contraseña automática "password"? 🚀
