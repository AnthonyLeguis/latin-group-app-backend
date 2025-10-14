# LatinGroup App - Backend

Backend de la aplicación LatinGroup desarrollado con Laravel 11, implementando una arquitectura limpia para la gestión de usuarios y clientes.

## 🚀 Tecnologías

- **Laravel 11** - Framework PHP
- **MySQL** - Base de datos
- **Laravel Sanctum** - Autenticación API
- **Laravel Socialite** - OAuth con Google
- **Spatie Laravel Data** - Data Transfer Objects

## 🏗️ Arquitectura

El proyecto sigue una arquitectura limpia con separación de responsabilidades:

```
app/
├── Data/           # DTOs para validación y transferencia de datos
├── Http/Controllers/Api/V1/  # Controladores de API
├── Models/         # Modelos Eloquent
├── Services/       # Lógica de negocio
└── Providers/      # Proveedores de servicios
```

## 📋 Características

### Autenticación
- ✅ Registro con email y contraseña
- ✅ Login con email y contraseña
- ✅ Autenticación con Google OAuth
- ✅ Tres tipos de usuario: Admin, Agent, Client
- ✅ Tokens de acceso con Sanctum

### Gestión de Clientes
- ✅ CRUD completo de clientes
- ✅ Asociación cliente-usuario
- ✅ Validación de datos
- ✅ Autorización por usuario

## 🛠️ Instalación

### Prerrequisitos
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js (para el frontend)

### Pasos de instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/tu-usuario/latin-group-app-backend.git
   cd latin-group-app-backend
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   ```

3. **Configurar entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar base de datos**
   - Crear base de datos MySQL: `latin_group_app`
   - Actualizar credenciales en `.env`

5. **Ejecutar migraciones**
   ```bash
   php artisan migrate
   ```

6. **Iniciar servidor**
   ```bash
   php artisan serve
   ```

## 📚 Uso de la API

### Autenticación

#### Registro
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "type": "client"
}
```

#### Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "juan@example.com",
  "password": "password123"
}
```

#### Google OAuth
```http
GET /api/v1/auth/google
GET /api/v1/auth/google/callback
```

### Gestión de Clientes

**Headers requeridos para todas las rutas protegidas:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Crear cliente
```http
POST /api/v1/clients

{
  "name": "Cliente Ejemplo",
  "email": "cliente@example.com",
  "phone": "+1234567890",
  "address": "Dirección del cliente"
}
```

#### Listar clientes
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
  "email": "cliente_actualizado@example.com",
  "phone": "+0987654321",
  "address": "Nueva dirección"
}
```

#### Eliminar cliente
```http
DELETE /api/v1/clients/{id}
```

## 🔐 Tipos de Usuario

- **Admin**: Acceso completo al sistema
- **Agent**: Acceso limitado para gestión de clientes
- **Client**: Solo puede gestionar sus propios datos

## 📁 Estructura del Proyecto

```
backend/
├── app/
│   ├── Data/           # DTOs
│   ├── Http/Controllers/Api/V1/  # API Controllers
│   ├── Models/         # Eloquent Models
│   ├── Services/       # Business Logic
│   └── Providers/      # Service Providers
├── config/             # Configuración
├── database/
│   └── migrations/     # Migraciones de BD
├── routes/
│   └── api.php         # Rutas de API
├── storage/            # Archivos temporales
├── tests/              # Pruebas
└── vendor/             # Dependencias
```

## 🧪 Pruebas

Para ejecutar las pruebas:
```bash
php artisan test
```

## 📝 Notas de Desarrollo

- El proyecto usa Laravel Sanctum para autenticación stateless
- Los tokens de acceso tienen duración indefinida (configurable)
- La validación se realiza mediante DTOs con Spatie Laravel Data
- La base de datos usa UTF8MB4 para soporte completo de Unicode

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## ✨ Estado del Proyecto

- ✅ Autenticación completa
- ✅ Gestión básica de clientes
- 🔄 Frontend en desarrollo
- 🔄 Funcionalidades avanzadas pendientes

---

**Desarrollado con ❤️ para LatinGroup**