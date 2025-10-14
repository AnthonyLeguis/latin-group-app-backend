# LatinGroup App - Backend

Backend de la aplicación LatinGroup desarrollado con Laravel 11, implementando arquitectura limpia con permisos basados en roles para la gestión de usuarios y clientes.

## 🚀 Tecnologías

- **Laravel 11** - Framework PHP moderno
- **MySQL 8.0+** - Base de datos relacional
- **Laravel Sanctum** - Autenticación API stateless
- **Laravel Socialite** - OAuth con Google
- **Spatie Laravel Data** - Data Transfer Objects
- **PHP 8.3** - Lenguaje de programación

## 🏗️ Arquitectura Limpia

El proyecto sigue los principios de Clean Architecture con separación clara de responsabilidades:

```
app/
├── Data/              # DTOs para validación y transferencia de datos
│   ├── Auth/         # DTOs de autenticación
│   └── Client/       # DTOs de clientes
├── Http/Controllers/Api/V1/  # Controladores REST API
├── Models/           # Modelos Eloquent con relaciones
├── Policies/         # Autorización basada en políticas
├── Providers/        # Proveedores de servicios y gates
└── Services/         # Lógica de negocio desacoplada
```

## 📋 Características Implementadas

### 🔐 Sistema de Autenticación Completo
- ✅ **Registro jerárquico** - Solo admin/agent pueden registrar usuarios
- ✅ **Login universal** - Todos los usuarios registrados pueden loguearse
- ✅ **Google OAuth universal** - Disponible para todos los usuarios registrados
- ✅ **Tres tipos de usuario** - Admin, Agent, Client
- ✅ **Tokens JWT** - Autenticación stateless con Sanctum

### 👥 Gestión de Usuarios con Permisos
- ✅ **Admin**: Crear, ver, editar, eliminar cualquier usuario
- ✅ **Agent**: Crear/ver/editar usuarios tipo `client`, ver sus propios clientes
- ✅ **Client**: Solo puede gestionar sus propios datos
- ✅ **Rastreo de creación** - Campo `created_by` para auditar quién creó cada usuario

### 📋 Gestión de Clientes
- ✅ **CRUD completo** - Crear, leer, actualizar, eliminar
- ✅ **Asociación usuario-cliente** - Cada cliente pertenece a un usuario
- ✅ **Permisos por rol** - Solo admin/agent pueden crear clientes
- ✅ **Validación completa** - Datos requeridos y formatos

### 🛡️ Sistema de Autorización
- ✅ **Policies de Laravel** - Lógica de permisos centralizada
- ✅ **Gates personalizados** - Validaciones específicas por acción
- ✅ **Middleware de autenticación** - Protección de rutas
- ✅ **Validación de ownership** - Usuarios solo acceden a sus recursos

## 🛠️ Instalación y Configuración

### Prerrequisitos
- **PHP 8.3+**
- **Composer** (gestor de dependencias PHP)
- **MySQL 8.0+** (o MariaDB)
- **Git**

### 🚀 Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/AnthonyLeguis/latin-group-app-backend.git
   cd latin-group-app-backend
   ```

2. **Instalar dependencias PHP**
   ```bash
   composer install
   ```

3. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar base de datos MySQL**
   Editar el archivo `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=latin_group_app
   DB_USERNAME=tu_usuario_mysql
   DB_PASSWORD=tu_password_mysql
   ```

5. **Crear base de datos**
   ```sql
   CREATE DATABASE latin_group_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

6. **Ejecutar migraciones y seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Configurar Google OAuth** (opcional)
   - Ve a [Google Cloud Console](https://console.cloud.google.com/)
   - Crea un proyecto o selecciona uno existente
   - Habilita la Google+ API
   - Crea credenciales OAuth 2.0
   - Configura la URL autorizada: `http://localhost:8000/api/v1/auth/google/callback`
   - Actualiza las variables en `.env`:
     ```env
     GOOGLE_CLIENT_ID=tu_client_id_aqui
     GOOGLE_CLIENT_SECRET=tu_client_secret_aqui
     FRONTEND_URL=http://localhost:4200
     ```

## � Usuarios de Prueba

Después de ejecutar los seeders, tendrás estos usuarios disponibles:

| Tipo | Email | Password | Login Email | Google OAuth | Registro |
|------|-------|----------|------------|-------------|----------|
| **Admin** | `admin@example.com` | `password123` | ✅ Disponible | ✅ Disponible | Registrado por sistema |
| **Agent** | `agent@example.com` | `password123` | ✅ Disponible | ✅ Disponible | Registrado por sistema |
| **Client** | `client@example.com` | `password123` | ✅ Disponible | ✅ Disponible | Registrado por admin/agent |
| **Client** | `john@example.com` | `password123` | ✅ Disponible | ✅ Disponible | Registrado por admin/agent |
| **Client** | `jane@example.com` | `password123` | ✅ Disponible | ✅ Disponible | Registrado por admin/agent |

## 📚 Documentación de API

### 🔑 Autenticación

#### Registro (Solo clientes)
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "password123",
  "type": "client"
}
```

#### Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password123"
}
```

**Respuesta:**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "type": "admin",
    "created_at": "2025-10-14T...",
    "updated_at": "2025-10-14T..."
  },
  "token": "1|abc123def456..."
}
```

#### Google OAuth
```http
GET /api/v1/auth/google
```
**Disponible para:** Todos los usuarios registrados en el sistema.

Redirige automáticamente al usuario a Google para autenticación.

**Callback (manejo automático):**
```http
GET /api/v1/auth/google/callback
```
Procesa la respuesta de Google y valida permisos.

**Redirecciones:**
- **Éxito:** `http://localhost:4200/dashboard?token={token}&user_type={type}&user_id={id}`
- **Error:** `http://localhost:4200/access-denied?error=access_denied&message={mensaje}`

**Mensajes de error posibles:**
- `"Usuario no registrado en el sistema"`
- `"No tiene permisos para acceder al sistema"`

### 👥 Gestión de Usuarios

**Headers requeridos:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Listar usuarios
```http
GET /api/v1/users
GET /api/v1/users?type=client
```

#### Crear usuario
```http
POST /api/v1/users

{
  "name": "Nuevo Cliente",
  "email": "nuevo@example.com",
  "password": "password123",
  "type": "client"
}
```

#### Ver usuario específico
```http
GET /api/v1/users/{id}
```

#### Actualizar usuario
```http
PUT /api/v1/users/{id}

{
  "name": "Nombre Actualizado",
  "email": "actualizado@example.com"
}
```

#### Eliminar usuario
```http
DELETE /api/v1/users/{id}
```

### 📋 Gestión de Clientes

#### Crear cliente
```http
POST /api/v1/clients

{
  "name": "Cliente Empresa",
  "email": "cliente@empresa.com",
  "phone": "+1234567890",
  "address": "Dirección completa del cliente"
}
```

#### Listar clientes del usuario
```http
GET /api/v1/clients
```

#### Ver cliente específico
```http
GET /api/v1/clients/{id}
```

#### Actualizar cliente
```http
PUT /api/v1/clients/{id}

{
  "name": "Cliente Actualizado",
  "phone": "+0987654321"
}
```

#### Eliminar cliente
```http
DELETE /api/v1/clients/{id}
```

## 🔐 Flujo de Registro y Autenticación

### 📝 Proceso de Registro

1. **NO hay registro público** - Solo usuarios autenticados pueden registrar
2. **Admin** puede registrar usuarios de cualquier tipo (`admin`, `agent`, `client`) usando `/api/v1/users`
3. **Agent** puede registrar solo usuarios tipo `client` usando `/api/v1/users`
4. **Client** NO puede registrar a nadie

### 🔑 Proceso de Login

Una vez registrado, cualquier usuario puede loguearse usando:

#### **Opción 1: Email + Contraseña** (Todos los tipos)
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "usuario@example.com",
  "password": "password123"
}
```

#### **Opción 2: Google OAuth** (Todos los tipos registrados)
```http
GET /api/v1/auth/google
```
**Nota:** Requiere que el usuario esté registrado previamente en el sistema.

### 🚫 Reglas de Acceso

- **Registro público:** ❌ NO permitido
- **Login universal:** Todos los usuarios registrados pueden loguearse con email o Google
- **Jerarquía de registro:** Admin > Agent > Client (cada nivel puede registrar el inferior)
- **Interfaz diferenciada:** El frontend muestra diferentes vistas según el tipo de usuario

## 🔐 Matriz de Permisos

| Acción | Endpoint | Admin | Agent | Client |
|--------|----------|-------|-------|--------|
| **Login con email** | `POST /auth/login` | ✅ | ✅ | ✅ |
| **Registro público** | `POST /auth/register` | ❌ | ❌ | ❌ |
| **Login con Google** | `GET /auth/google` | ✅ | ✅ | ✅ |
| **Ver usuarios** | `GET /users` | ✅ Todos | ❌ Solo clients | ❌ |
| **Crear admin** | `POST /users` | ✅ | ❌ | ❌ |
| **Crear agent** | `POST /users` | ✅ | ❌ | ❌ |
| **Crear client** | `POST /users` | ✅ | ✅ | ❌ |
| **Editar usuarios** | `PUT /users/{id}` | ✅ Cualquier | ✅ Solo clients | ❌ |
| **Eliminar usuarios** | `DELETE /users/{id}` | ✅ | ❌ | ❌ |
| **Crear clientes** | `POST /clients` | ✅ | ✅ | ❌ |
| **Ver clientes** | `GET /clients` | ✅ Propios | ✅ Propios | ✅ Propios |
| **Editar clientes** | `PUT /clients/{id}` | ✅ Propios | ✅ Propios | ✅ Propios |
| **Eliminar clientes** | `DELETE /clients/{id}` | ✅ Propios | ✅ Propios | ✅ Propios |

## 🌐 **Uso desde el Frontend**

### Login con Google
Para implementar el botón "Iniciar sesión con Google" en tu frontend:

```javascript
// Redirigir al usuario a Google
function loginWithGoogle() {
  window.location.href = 'http://localhost:8000/api/v1/auth/google';
}

// El backend redirigirá automáticamente a:
// http://localhost:4200/dashboard?token=abc123&user_type=client&user_id=1

// En tu componente de dashboard, captura los parámetros de la URL:
const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');
const userType = urlParams.get('user_type');
const userId = urlParams.get('user_id');
const error = urlParams.get('error');
const message = urlParams.get('message');

// Manejo de errores
if (error === 'access_denied') {
  // Mostrar página de "Acceso denegado"
  showAccessDeniedPage(message);
  return;
}

// Manejo de login exitoso
if (token) {
  localStorage.setItem('auth_token', token);
  localStorage.setItem('user_type', userType);
  // Redirigir a la aplicación principal
}
```

## 📊 Base de Datos

### Tablas principales:
- **`users`** - Usuarios del sistema con roles
- **`clients`** - Datos adicionales de clientes
- **`personal_access_tokens`** - Tokens de Sanctum

### Relaciones:
- **User → Clients**: Un usuario puede tener múltiples clientes
- **User → User**: Rastreo de creación (`created_by`)

### Migraciones importantes:
- `create_users_table` - Usuarios con tipos y `created_by`
- `create_clients_table` - Datos de clientes asociados a usuarios
- `add_created_by_to_users_table` - Campo de auditoría

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test --filter=AuthTest
php artisan test --filter=UserTest
```

## � Comandos Útiles

```bash
# Limpiar cache
php artisan config:clear && php artisan cache:clear

# Resetear base de datos
php artisan migrate:fresh --seed

# Ver rutas disponibles
php artisan route:list --path=api

# Crear nuevo seeder
php artisan make:seeder NuevoSeeder
```

## 📝 Notas Técnicas

- **Autenticación**: Stateless con tokens JWT via Sanctum
- **Validación**: DTOs con Spatie Laravel Data
- **Autorización**: Policies y Gates de Laravel
- **Base de datos**: UTF8MB4 para soporte Unicode completo
- **Seeds**: Datos de prueba incluidos para desarrollo

## 🚀 Próximos Pasos

- [ ] Implementar notificaciones por email
- [ ] Agregar logging de actividades
- [ ] Implementar rate limiting
- [ ] Crear API documentation con Swagger
- [ ] Agregar tests unitarios e integración
- [ ] Implementar caché para optimización
- [ ] Desarrollar frontend React/Vue
- [ ] Agregar funcionalidades de reporting

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama feature: `git checkout -b feature/nueva-funcionalidad`
3. Commit cambios: `git commit -m 'Agregar nueva funcionalidad'`
4. Push rama: `git push origin feature/nueva-funcionalidad`
5. Abrir Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.

## ✨ Estado del Proyecto

- ✅ **Backend API completo** con autenticación y permisos
- ✅ **Arquitectura limpia** implementada
- ✅ **Sistema de roles** funcional
- ✅ **Base de datos** configurada y poblada
- ✅ **Autenticación múltiple** (email + Google OAuth)
- ✅ **Registro jerárquico** implementado
- ✅ **Sistema de autenticación probado y verificado**
- 🔄 **Frontend** pendiente de desarrollo
- 🔄 **Documentación API** puede mejorarse con Swagger

## 🧪 Pruebas Realizadas

### ✅ Verificación del Sistema de Autenticación

**Registro Público:**
- ❌ Código 401/500 - Autenticación requerida correctamente aplicada
- ✅ Middleware `auth:sanctum` protege la ruta de registro

**Login con Email/Password:**
- ✅ Admin puede loguearse: `admin@example.com` / `password123`
- ✅ Agent puede loguearse: `agent@example.com` / `password123`
- ✅ Client puede loguearse: `client@example.com` / `password123`

**Registro Jerárquico:**
- ✅ Admin puede registrar: clients ✅, admins ✅
- ✅ Agent puede registrar: client (validado en código)
- ✅ Agent NO puede registrar: admin ❌ (correctamente rechazado)
- ✅ Client NO puede registrar: nadie (requiere autenticación)
- ✅ **Email único**: Restricción validada (base de datos + aplicación)

**Google OAuth:**
- ✅ Solo usuarios registrados pueden usar Google OAuth
- ✅ No hay restricciones por tipo de usuario
- ✅ Redirección correcta al frontend con token y datos

**Permisos Verificados:**
- ✅ Autenticación requerida para todas las operaciones
- ✅ Policies y Gates funcionando correctamente
- ✅ Tokens JWT via Sanctum operativos
- ✅ **Restricción de email único** validada (base de datos + aplicación)

### 📊 Usuarios de Prueba Disponibles

| Email | Password | Tipo | Permisos |
|-------|----------|------|----------|
| admin@example.com | password123 | admin | Crear admin, agent, client |
| agent@example.com | password123 | agent | Crear client |
| client@example.com | password123 | client | Solo acceso propio |
| john@example.com | password123 | client | Solo acceso propio |
| jane@example.com | password123 | client | Solo acceso propio |

---

**Desarrollado con ❤️ para LatinGroup - Sistema de gestión de usuarios y clientes con permisos avanzados**