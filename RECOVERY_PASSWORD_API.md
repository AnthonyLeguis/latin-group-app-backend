# API de Recuperación y Cambio de Contraseña

## Endpoints Implementados

### 1. Solicitar Recuperación de Contraseña (Público)
**POST** `/api/v1/auth/forgot-password`

Envía un email al usuario con un enlace para restablecer su contraseña.

**Request Body:**
```json
{
  "email": "usuario@ejemplo.com"
}
```

**Response Success (200):**
```json
{
  "message": "Se ha enviado un correo electrónico con las instrucciones para restablecer tu contraseña"
}
```

**Response Error (400):**
```json
{
  "error": "No se encontró ninguna cuenta con ese correo electrónico"
}
```

---

### 2. Restablecer Contraseña con Token (Público)
**POST** `/api/v1/auth/reset-password`

Restablece la contraseña del usuario usando el token recibido por email.

**Request Body:**
```json
{
  "email": "usuario@ejemplo.com",
  "token": "token_recibido_por_email",
  "password": "nuevaPassword123",
  "password_confirmation": "nuevaPassword123"
}
```

**Response Success (200):**
```json
{
  "message": "Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión con tu nueva contraseña"
}
```

**Response Error (400):**
```json
{
  "error": "Token inválido o expirado"
}
```

**Errores Posibles:**
- "Las contraseñas no coinciden"
- "La contraseña debe tener al menos 8 caracteres"
- "Token inválido o expirado"
- "El token ha expirado. Solicita uno nuevo"
- "Usuario no encontrado"

---

### 3. Cambiar Contraseña (Requiere Autenticación)
**POST** `/api/v1/auth/change-password`

Permite al usuario autenticado cambiar su contraseña actual.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "current_password": "passwordActual123",
  "new_password": "nuevaPassword456",
  "new_password_confirmation": "nuevaPassword456"
}
```

**Response Success (200):**
```json
{
  "message": "Tu contraseña ha sido actualizada exitosamente"
}
```

**Response Error (400):**
```json
{
  "error": "La contraseña actual es incorrecta"
}
```

**Errores Posibles:**
- "La contraseña actual es incorrecta"
- "Las contraseñas nuevas no coinciden"
- "La nueva contraseña debe tener al menos 8 caracteres"
- "La nueva contraseña debe ser diferente a la actual"

**Response Error (401):**
```json
{
  "error": "No autenticado"
}
```

---

## Flujo de Recuperación de Contraseña

1. **Usuario solicita recuperación:**
   - Frontend envía POST a `/api/v1/auth/forgot-password` con el email
   - Backend genera un token único y lo guarda en la BD
   - Backend envía email con enlace al frontend que incluye token y email

2. **Usuario recibe email:**
   - Email contiene botón y enlace del tipo:
     `http://frontend.com/auth/reset-password?token=abc123&email=user@example.com`
   - Token válido por 60 minutos

3. **Usuario resetea contraseña:**
   - Frontend muestra formulario de nueva contraseña
   - Frontend envía POST a `/api/v1/auth/reset-password` con:
     - email
     - token
     - password
     - password_confirmation
   - Backend valida token, actualiza contraseña y elimina token usado
   - Backend revoca todos los tokens de sesión anteriores

4. **Usuario inicia sesión:**
   - Usuario puede hacer login con la nueva contraseña

---

## Seguridad Implementada

- ✅ Tokens hasheados en base de datos
- ✅ Tokens expiran después de 60 minutos
- ✅ Token de un solo uso (se elimina después de usarse)
- ✅ Validación de longitud mínima de contraseña (8 caracteres)
- ✅ Validación de coincidencia de contraseñas
- ✅ Revocación de tokens de sesión al resetear contraseña
- ✅ Logging de todas las operaciones importantes
- ✅ Rate limiting en rutas sensibles

---

## Configuración Requerida

### Variables de Entorno (.env)

```env
# Frontend URL para links de recuperación
FRONTEND_URL=http://localhost:4200

# Configuración de Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@latingroup.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Base de Datos

La tabla `password_reset_tokens` se crea automáticamente con la migración:

```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);
```

---

## Testing con Postman/Thunder Client

### Test 1: Forgot Password
```bash
POST http://localhost:8000/api/v1/auth/forgot-password
Content-Type: application/json

{
  "email": "test@example.com"
}
```

### Test 2: Reset Password
```bash
POST http://localhost:8000/api/v1/auth/reset-password
Content-Type: application/json

{
  "email": "test@example.com",
  "token": "token_from_email",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

### Test 3: Change Password
```bash
POST http://localhost:8000/api/v1/auth/change-password
Content-Type: application/json
Authorization: Bearer your_token_here

{
  "current_password": "oldPassword123",
  "new_password": "newPassword456",
  "new_password_confirmation": "newPassword456"
}
```

---

## Próximos Pasos Frontend

1. Crear página `/auth/forgot-password` con formulario de email
2. Crear página `/auth/reset-password` que lea token y email de query params
3. Crear formulario de cambio de contraseña en perfil de usuario
4. Implementar servicios Angular para consumir estos endpoints
5. Agregar manejo de errores y mensajes de éxito
