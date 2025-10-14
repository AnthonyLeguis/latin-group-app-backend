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
- ✅ **Registro público** - Solo permite crear usuarios tipo `client`
- ✅ **Login tradicional** - Email y contraseña
- ✅ **Google OAuth** - Autenticación social
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

7. **Iniciar servidor de desarrollo**
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

## � Usuarios de Prueba

Después de ejecutar los seeders, tendrás estos usuarios disponibles:

| Tipo | Email | Password | Permisos |
|------|-------|----------|----------|
| **Admin** | `admin@example.com` | `password123` | Crear/ver/editar/eliminar cualquier usuario |
| **Agent** | `agent@example.com` | `password123` | Crear/ver usuarios tipo `client` |
| **Client** | `client@example.com` | `password123` | Solo gestionar sus propios datos |
| **Client** | `john@example.com` | `password123` | Solo gestionar sus propios datos |
| **Client** | `jane@example.com` | `password123` | Solo gestionar sus propios datos |

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
GET /api/v1/auth/google/callback
```

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

## 🔐 Matriz de Permisos

| Acción | Endpoint | Admin | Agent | Client |
|--------|----------|-------|-------|--------|
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

## 🧪 Pruebas con Postman

### Configuración de Postman
1. Crear colección "LatinGroup API"
2. Configurar variable `base_url`: `http://127.0.0.1:8000`
3. Usar variables para tokens: `admin_token`, `agent_token`, `client_token`

### Flujo de pruebas recomendado:
1. **Login** con diferentes usuarios
2. **Probar permisos** - Intentar acciones no permitidas
3. **Crear recursos** - Usuarios y clientes según permisos
4. **Verificar ownership** - Recursos solo accesibles por owner

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
- 🔄 **Frontend** pendiente de desarrollo
- 🔄 **Documentación API** puede mejorarse

---

**Desarrollado con ❤️ para LatinGroup - Sistema de gestión de usuarios y clientes con permisos avanzados**