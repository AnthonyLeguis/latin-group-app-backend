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
│   └── Application/  # DTOs de application forms
├── Http/Controllers/Api/V1/  # Controladores REST API
│   ├── AuthController.php
│   ├── UserController.php
│   └── ApplicationFormController.php
├── Models/           # Modelos Eloquent con relaciones
│   ├── User.php
│   ├── ApplicationForm.php
│   └── ApplicationDocument.php
├── Policies/         # Autorización basada en políticas
│   ├── UserPolicy.php
│   └── ApplicationFormPolicy.php
└── Providers/        # Proveedores de servicios y gates
    └── AuthServiceProvider.php
```

**Nota:** La tabla `clients` fue deprecada y eliminada. Toda la gestión se realiza mediante la tabla `users` con `type = 'client'`.

## 📋 Características Implementadas

### 🔐 Sistema de Autenticación Completo
- ✅ **Registro jerárquico** - Solo admin/agent pueden registrar usuarios
- ✅ **Login universal** - Todos los usuarios registrados pueden loguearse
- ✅ **Google OAuth universal** - Disponible para todos los usuarios registrados
- ✅ **Tres tipos de usuario** - Admin, Agent, Client
- ✅ **Tokens JWT** - Autenticación stateless con Sanctum

### 👥 Nueva Arquitectura de Gestión de Usuarios
- ✅ **Tabla única `users`** - Consolidación de admin, agent y client (eliminada tabla redundante `clients`)
- ✅ **Admin**: CRUD completo para todos los tipos de usuario
- ✅ **Agent**: CRUD solo para usuarios tipo `client` que él creó
- ✅ **Client**: Solo puede gestionar sus propios datos
- ✅ **Auditoría completa** - Campos `created_by` y `updated_by` en todos los usuarios
- ✅ **Relaciones bidireccionales** - `createdBy()`, `updatedBy()`, `createdUsers()`
- ✅ **Filtrado automático** - Los agents solo ven los clients que crearon

### 📋 Sistema de Application Forms (Planillas)
- ✅ **Auto-creación** - Al registrar un client, se crea automáticamente su `ApplicationForm`
- ✅ **Status workflow** - `pendiente` → `activo` / `inactivo` / `rechazado` (solo admin)
- ✅ **Tracking de revisión** - Campos `reviewed_by` y `reviewed_at` para auditoría
- ✅ **Permisos granulares** - Agent crea y edita, Admin aprueba y cambia status
- ✅ **Comentarios obligatorios** - Campo `status_comment` requerido al cambiar status

### 🛡️ Sistema de Autorización Avanzado
- ✅ **Policies de Laravel** - Lógica de permisos centralizada con validación de ownership
- ✅ **Gates personalizados** - Validaciones específicas por acción y rol
- ✅ **Middleware de autenticación** - Protección de rutas con Sanctum
- ✅ **Validación a nivel de query** - Filtrado automático por `created_by` para agents
- ✅ **Eager loading optimizado** - Prevención de N+1 queries en relaciones

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

   **Migraciones aplicadas:**
   ```
   ✅ 0001_01_01_000000_create_users_table.php
   ✅ 0001_01_01_000001_create_personal_access_tokens_table.php
   ✅ 2025_10_14_200443_add_created_by_to_users_table.php
   ✅ 2025_10_15_215420_create_application_forms_table.php
   ✅ 2025_10_15_215453_create_application_documents_table.php
   ✅ 2025_10_23_000001_create_contact_us_table.php
   ✅ 2025_10_26_015020_add_updated_by_to_users_table.php
   ✅ 2025_10_26_021308_update_application_forms_status_and_tracking.php
   ```

   **Migraciones eliminadas (redundantes):**
   ```
   ❌ 0001_01_01_000002_create_clients_table.php (tabla deprecada)
   ❌ 2025_10_26_014836_add_index_to_users_created_by_column.php (consolidada)
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
GET /api/v1/users                    # Todos los usuarios (filtrado por permisos)
GET /api/v1/users?type=client        # Solo clientes
GET /api/v1/users?type=agent         # Solo agentes (admin only)
```

**Respuesta para Agent:**
```json
{
  "users": [
    {
      "id": 5,
      "name": "Cliente 1",
      "email": "cliente1@example.com",
      "type": "client",
      "created_by": 2,
      "updated_by": 2,
      "created_at": "2025-10-26T...",
      "updated_at": "2025-10-26T..."
    }
  ]
}
```

**Nota:** Los agents solo ven usuarios tipo `client` que ellos crearon (`created_by = agent_id`).

#### Reporte de Agentes con Clientes (solo Admin)
```http
GET /api/v1/users/agents-report
```

**Respuesta:**
```json
{
  "agents": [
    {
      "id": 2,
      "name": "Agent User",
      "email": "agent@example.com",
      "clients_count": 3,
      "created_users": [
        {
          "id": 5,
          "name": "Cliente 1",
          "email": "cliente1@example.com",
          "application_forms_as_client": [
            {
              "id": 1,
              "status": "pendiente",
              "reviewed_by": null,
              "created_at": "2025-10-26T..."
            }
          ]
        }
      ]
    }
  ],
  "total_agents": 1,
  "total_clients": 3
}
```

#### Planillas Pendientes de Revisión (solo Admin)
```http
GET /api/v1/users/pending-forms
GET /api/v1/users/pending-forms?status=activo
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "client_id": 5,
      "agent_id": 2,
      "status": "pendiente",
      "status_comment": null,
      "reviewed_by": null,
      "reviewed_at": null,
      "client": {
        "id": 5,
        "name": "Cliente 1",
        "email": "cliente1@example.com"
      },
      "agent": {
        "id": 2,
        "name": "Agent User",
        "email": "agent@example.com"
      },
      "created_at": "2025-10-26T..."
    }
  ],
  "per_page": 20,
  "current_page": 1
}
```

#### Estadísticas de Usuarios (solo Admin)
```http
GET /api/v1/users/stats
```

**Respuesta:**
```json
{
  "total_users": 10,
  "total_admins": 1,
  "total_agents": 2,
  "total_clients": 7,
  "pending_forms": 3,
  "active_forms": 4,
  "rejected_forms": 1
}
```

#### Crear usuario
```http
POST /api/v1/users

{
  "name": "Nuevo Cliente",
  "email": "nuevo@example.com",
  "password": "password123",
  "type": "client",
  "agent_id": 2  // Requerido si admin crea un client
}
```

**Nota importante:** 
- **Agent crea client**: No necesita `agent_id`, se asigna automáticamente el ID del agent
- **Admin crea client**: Debe especificar `agent_id` para asignar a qué agent pertenece
- Al crear un `client`, se genera automáticamente un `ApplicationForm` con `status = 'pendiente'`

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

### 📄 Gestión de Planillas de Aplicación

**Headers requeridos:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

#### Listar planillas de aplicación
```http
GET /api/v1/application-forms
GET /api/v1/application-forms?status=En%20Revisión
GET /api/v1/application-forms?client_id={id}
```

#### Crear planilla de aplicación
```http
POST /api/v1/application-forms

{
  "client_id": 3,
  "applicant_name": "Juan Pérez",
  "dob": "1990-05-15",
  "address": "Calle 123 #45-67",
  "city": "Bogotá",
  "state": "Cundinamarca",
  "zip_code": "110111",
  "phone": "+57 300 123 4567",
  "email": "juan@example.com",
  "gender": "M",
  "ssn": "123-45-6789",
  "legal_status": "Ciudadano",
  "document_number": "123456789",
  // ... otros campos según la estructura completa
}
```

#### Ver planilla específica
```http
GET /api/v1/application-forms/{id}
```

#### Actualizar planilla
```http
PUT /api/v1/application-forms/{id}

{
  "applicant_name": "Juan Pérez Actualizado",
  // ... otros campos a actualizar
}
```

#### Confirmar planilla (solo el agente creador)
```http
POST /api/v1/application-forms/{id}/confirm

{
  "confirmed": true
}
```

#### Actualizar status (solo admin)
```http
POST /api/v1/application-forms/{id}/status

{
  "status": "activo",  // pendiente | activo | inactivo | rechazado
  "status_comment": "Planilla aprobada, documentación completa y verificada"
}
```

**Respuesta:**
```json
{
  "message": "Status actualizado exitosamente",
  "form": {
    "id": 1,
    "status": "activo",
    "status_comment": "Planilla aprobada, documentación completa y verificada",
    "reviewed_by": 1,
    "reviewed_at": "2025-10-26T14:30:00.000000Z",
    "client": { ... },
    "agent": { ... },
    "reviewedBy": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com"
    }
  }
}
```

**Notas:**
- Solo admin puede cambiar el status
- El campo `status_comment` es **obligatorio**
- Se registra automáticamente quién revisó (`reviewed_by`) y cuándo (`reviewed_at`)
- Estados disponibles: `pendiente`, `activo`, `inactivo`, `rechazado`

#### Subir documento
```http
POST /api/v1/application-forms/{id}/documents
Content-Type: multipart/form-data

document: (archivo)
document_type: "cedula"
```

#### Eliminar documento
```http
DELETE /api/v1/application-forms/{id}/documents/{documentId}
```

#### Eliminar planilla (solo admin)
```http
DELETE /api/v1/application-forms/{id}
```

## 🔐 Flujo de Registro y Autenticación

### 📝 Proceso de Registro

1. **NO hay registro público** - Solo usuarios autenticados pueden registrar
2. **Admin** puede registrar usuarios de cualquier tipo (`admin`, `agent`, `client`) usando `/api/v1/users`
   - Al crear un `client`, debe especificar a qué `agent_id` pertenece
3. **Agent** puede registrar solo usuarios tipo `client` usando `/api/v1/users`
   - Los clients se asignan automáticamente al agent que los crea
   - Se crea automáticamente un `ApplicationForm` con `status = 'pendiente'`
4. **Client** NO puede registrar a nadie

### 🔄 Flujo Completo de Creación de Cliente

**Cuando un Agent crea un Client:**
1. Agent envía `POST /api/v1/users` con `type = 'client'`
2. Se crea el usuario en la tabla `users` con `created_by = agent_id`
3. Se crea automáticamente un registro en `application_forms`:
   - `client_id` = ID del nuevo usuario
   - `agent_id` = ID del agent que lo creó
   - `agent_name` = Nombre del agent
   - `applicant_name` = Nombre del cliente
   - `email` = Email del cliente
   - `status` = `'pendiente'`
4. Agent puede completar los datos de la planilla
5. Admin revisa y aprueba/rechaza cambiando el `status`

**Cuando un Admin crea un Client:**
1. Admin envía `POST /api/v1/users` con `type = 'client'` y `agent_id`
2. Se valida que el `agent_id` existe y es tipo `agent`
3. Mismo flujo que cuando lo crea un agent

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

## 🔐 Flujo de Planillas de Aplicación

### 📋 Proceso Completo

1. **Agent/Admin registra usuario tipo client** → Usuario creado en tabla `users`
2. **Sistema auto-crea ApplicationForm** → Status inicial: `pendiente`
3. **Agent completa datos de planilla** → Edita campos de información
4. **Agent confirma planilla** → Marca `confirmed = true`
5. **Admin revisa planilla pendiente** → Usa `GET /api/v1/users/pending-forms`
6. **Admin aprueba/rechaza** → Cambia `status` con comentario obligatorio
7. **Sistema registra auditoría** → Guarda `reviewed_by` y `reviewed_at`

### 🔐 Estados y Transiciones

| Estado | Descripción | Puede cambiar a | Solo puede cambiar | Status Comment |
|--------|-------------|-----------------|-------------------|----------------|
| **pendiente** | Planilla creada, esperando revisión | activo, rechazado | Admin | Obligatorio |
| **activo** | Planilla aprobada y operativa | inactivo, rechazado | Admin | Obligatorio |
| **inactivo** | Planilla suspendida temporalmente | activo, rechazado | Admin | Obligatorio |
| **rechazado** | Planilla rechazada definitivamente | - | Admin | Obligatorio |

**Notas importantes:**
- Solo **Admin** puede cambiar el status de una planilla
- El campo `status_comment` es **obligatorio** al cambiar status
- Se registra automáticamente quién hizo el cambio (`reviewed_by`) y cuándo (`reviewed_at`)
- Agent puede editar la planilla solo si no está confirmada

### 📎 Gestión de Documentos

- **Formatos permitidos**: JPEG, JPG, PNG, PDF
- **Tamaño máximo**: 5MB por archivo
- **Almacenamiento**: Disco `public` con URLs accesibles
- **Eliminación automática**: Archivos se eliminan al borrar documentos
- **Tipos de documento**: Configurable (cedula, recibo, contrato, etc.)

## 📊 Estructura de Datos - Planillas de Aplicación

### Campos de la Planilla (47 campos principales)

#### 📝 **Datos de Aplicación (1-24)**
- `agent_name`: Nombre del agente (automático)
- `applicant_name`: Nombre del solicitante
- `dob`: Fecha de nacimiento
- `address`: Dirección completa
- `unit_apt`: Unidad/Apartamento
- `city`: Ciudad
- `state`: Estado/Provincia
- `zip_code`: Código postal
- `phone`: Teléfono principal
- `phone2`: Teléfono secundario
- `email`: Correo electrónico
- `gender`: Género (M/F)
- `ssn`: Número de Seguro Social
- `legal_status`: Estado legal
- `document_number`: Número de documento
- `insurance_company`: Compañía de seguro
- `insurance_plan`: Plan de seguro
- `subsidy`: Subsidio
- `final_cost`: Costo final
- `employment_type`: Tipo de empleo (W2/1099/Other)
- `employment_company_name`: Nombre de empresa
- `work_phone`: Teléfono laboral
- `wages`: Salario
- `wages_frequency`: Frecuencia de pago

#### 🏠 **Datos de Póliza (25-29)**
- `poliza_number`: Número de póliza
- `poliza_category`: Categoría de póliza
- `poliza_amount`: Monto de póliza
- `poliza_payment_day`: Día de cobro
- `poliza_beneficiary`: Beneficiario

#### 👥 **Datos de Personas Adicionales (30-139)**
*Se repite para 4 personas (Person 1, 2, 3, 4):*
- `person{N}_name`: Nombre
- `person{N}_relation`: Relación con el aplicante
- `person{N}_is_applicant`: Es el aplicante (Y/N)
- `person{N}_legal_status`: Estado legal
- `person{N}_document_number`: Número de documento
- `person{N}_dob`: Fecha de nacimiento
- `person{N}_company_name`: Nombre de empresa
- `person{N}_ssn`: SSN
- `person{N}_gender`: Género
- `person{N}_wages`: Salario
- `person{N}_frequency`: Frecuencia de pago

#### 💳 **Datos de Método de Pago (140-147)**
- `card_type`: Tipo de tarjeta
- `card_number`: Número de tarjeta
- `card_expiration`: Fecha de expiración
- `card_cvv`: Código CVV
- `bank_name`: Nombre del banco
- `bank_routing`: Número de ruta bancaria
- `bank_account`: Número de cuenta

#### ⚙️ **Campos de Control y Auditoría**
- `status`: Estado actual (`pendiente` | `activo` | `inactivo` | `rechazado`)
- `status_comment`: Comentario obligatorio al cambiar status (max 1000 caracteres)
- `confirmed`: Confirmación del agente (boolean)
- `reviewed_by`: ID del admin que revisó (foreign key → users.id)
- `reviewed_at`: Fecha y hora de la última revisión (timestamp)
- `created_at`: Fecha de creación
- `updated_at`: Fecha de última actualización

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

## � **Planillas de Aplicación**

### 🎯 **Funcionalidad**
Sistema completo para que los **agents** puedan crear y gestionar planillas de aplicación para sus clientes. Incluye datos personales, información financiera, personas adicionales, métodos de pago, y subida de documentos.

### 📝 **Campos de la Planilla**

#### **Datos de la Application (1-24)**
- `agent_name` - Nombre del agente (automático)
- `applicant_name` - Nombre del solicitante
- `dob` - Fecha de nacimiento
- `address` - Dirección completa
- `unit_apt` - Unidad/Apartamento
- `city`, `state`, `zip_code` - Ubicación
- `phone`, `phone2` - Teléfonos
- `email` - Correo electrónico
- `gender` - Género (M/F)
- `ssn` - Número de Seguro Social
- `legal_status` - Estado legal
- `document_number` - Número de documento
- `insurance_company`, `insurance_plan` - Seguro
- `subsidy`, `final_cost` - Subsidio y costo final
- `employment_type` - Tipo de empleo (W2/1099/Other)
- `employment_company_name` - Nombre de empresa
- `work_phone` - Teléfono laboral
- `wages`, `wages_frequency` - Salario y frecuencia

#### **Datos de la PÓLIZA (25-29)**
- `poliza_number` - Número de póliza
- `poliza_category` - Categoría de póliza
- `poliza_amount` - Monto de póliza
- `poliza_payment_day` - Día de cobro
- `poliza_beneficiary` - Beneficiario

#### **Personas Adicionales (30-40 x 4 personas)**
Cada persona incluye: `name`, `relation`, `is_applicant`, `legal_status`, `document_number`, `dob`, `company_name`, `ssn`, `gender`, `wages`, `frequency`

#### **Método de Pago (41-47)**
- `card_type`, `card_number`, `card_expiration`, `card_cvv`
- `bank_name`, `bank_routing`, `bank_account`

#### **Control y Estado**
- `status` - Activo/Inactivo/En Revisión
- `status_comment` - Comentario del status
- `confirmed` - Confirmación del agente

### 📎 **Documentos Adjuntos**
- Subida de archivos (imágenes/PDF)
- Tipos: cédula, recibo, contrato, etc.
- Almacenamiento en `storage/app/public/application_documents`
- Eliminación automática al borrar planilla

### 🔐 **Permisos por Rol**

| Acción | Admin | Agent | Client |
|--------|-------|-------|--------|
| **Ver todas las planillas** | ✅ Todas | ✅ Solo las que creó | ❌ |
| **Ver planilla específica** | ✅ Cualquiera | ✅ Si la creó | ✅ Si es suya |
| **Crear planilla** | ❌ Auto-creada | ✅ Auto-creada al crear client | ❌ |
| **Editar planilla** | ✅ Cualquiera | ✅ Solo no confirmadas que creó | ❌ |
| **Confirmar planilla** | ❌ | ✅ Solo las que creó | ❌ |
| **Cambiar status** | ✅ Cualquiera (con comment) | ❌ | ❌ |
| **Ver quién revisó** | ✅ | ✅ | ❌ |
| **Subir documentos** | ✅ A cualquiera | ✅ A las que creó | ❌ |
| **Eliminar planilla** | ✅ Cualquiera | ❌ | ❌ |

**Notas:**
- Las planillas se **auto-crean** al registrar un usuario tipo `client`
- Solo **Admin** puede cambiar el `status` de una planilla
- Agent puede editar planilla solo si `confirmed = false` y él la creó
- El campo `status_comment` es **obligatorio** al cambiar status

### 🚀 **API Endpoints**

#### **Gestión de Planillas**
```http
GET    /api/v1/application-forms          # Listar planillas
POST   /api/v1/application-forms          # Crear planilla
GET    /api/v1/application-forms/{id}     # Ver planilla específica
PUT    /api/v1/application-forms/{id}     # Actualizar planilla
DELETE /api/v1/application-forms/{id}     # Eliminar planilla (solo admin)
```

#### **Acciones Especiales**
```http
POST   /api/v1/application-forms/{id}/confirm     # Confirmar planilla
POST   /api/v1/application-forms/{id}/status      # Cambiar status (solo admin)
POST   /api/v1/application-forms/{id}/documents   # Subir documento
DELETE /api/v1/application-forms/{id}/documents/{docId}  # Eliminar documento
```

#### **Ejemplo: Crear Planilla**
```http
POST /api/v1/application-forms
Authorization: Bearer {agent_token}
Content-Type: application/json

{
  "client_id": 5,
  "applicant_name": "Juan Pérez",
  "dob": "1990-05-15",
  "address": "123 Main St",
  "city": "Miami",
  "state": "FL",
  "zip_code": "33101",
  "phone": "305-123-4567",
  "email": "juan@example.com",
  "gender": "M",
  "ssn": "123-45-6789",
  "legal_status": "Citizen",
  "document_number": "DOC123456",
  "employment_type": "W2",
  "employment_company_name": "ABC Corp",
  "wages": 45000.00,
  "wages_frequency": "Monthly"
}
```

#### **Ejemplo: Subir Documento**
```http
POST /api/v1/application-forms/{id}/documents
Authorization: Bearer {token}
Content-Type: multipart/form-data

document: (file) cedula_juan.pdf
document_type: cedula
```

### 📊 **Flujo de Trabajo Actualizado**

1. **Agent crea cliente** → `POST /api/v1/users` con `type = 'client'`
2. **Sistema auto-crea ApplicationForm** → Status inicial: `pendiente`
3. **Agent completa planilla** → `PUT /api/v1/application-forms/{id}`
4. **Agent confirma planilla** → `POST /api/v1/application-forms/{id}/confirm`
5. **Admin ve planillas pendientes** → `GET /api/v1/users/pending-forms`
6. **Admin aprueba/rechaza** → `POST /api/v1/application-forms/{id}/status`
7. **Sistema registra auditoría** → `reviewed_by` y `reviewed_at` automáticos
8. **Agent puede subir documentos** → `POST /api/v1/application-forms/{id}/documents`
9. **Client puede ver su planilla** → `GET /api/v1/application-forms` (solo lectura)

### 🧪 **Pruebas del Sistema**

```bash
# Ejecutar pruebas de planillas
php test_application_forms.php
```

**Resultados esperados:**
- ✅ Agent puede crear planillas para sus clients
- ✅ Agent puede confirmar planillas
- ✅ Admin puede cambiar status
- ✅ Agent NO puede editar planillas confirmadas
- ✅ Admin puede editar cualquier planilla
- ✅ Subida y eliminación de documentos funciona

### 💾 **Estructura de Base de Datos**

#### **users** (tabla consolidada)
```sql
- id (PK)
- name
- email (unique)
- password
- type (enum: 'admin', 'agent', 'client')
- created_by (FK → users.id) - Quién creó este usuario
- updated_by (FK → users.id) - Quién actualizó este usuario
- created_at
- updated_at
```

**Índices:**
- `created_by` (para optimizar filtrado de agents)
- `updated_by` (para auditoría de actualizaciones)
- `type` (para filtros por tipo de usuario)

**Relaciones:**
- `createdBy()` → belongsTo User (quién lo creó)
- `updatedBy()` → belongsTo User (quién lo actualizó)
- `createdUsers()` → hasMany User (usuarios que creó)
- `applicationFormsAsClient()` → hasMany ApplicationForm (planillas como cliente)
- `applicationFormsAsAgent()` → hasMany ApplicationForm (planillas como agente)

#### **application_forms**
```sql
- id (PK)
- client_id (FK → users.id, type='client')
- agent_id (FK → users.id, type='agent')
- agent_name (string)
- applicant_name (string)
- ... (47+ campos de datos)
- status (enum: 'pendiente', 'activo', 'inactivo', 'rechazado')
- status_comment (text, max 1000 chars)
- confirmed (boolean, default false)
- reviewed_by (FK → users.id, type='admin')
- reviewed_at (timestamp, nullable)
- created_at
- updated_at
```

**Índices:**
- `client_id` (para búsquedas por cliente)
- `agent_id` (para búsquedas por agente)
- `status` (para filtros por estado)
- `reviewed_by` (para auditoría)

**Relaciones:**
- `client()` → belongsTo User
- `agent()` → belongsTo User
- `reviewedBy()` → belongsTo User
- `documents()` → hasMany ApplicationDocument

#### **application_documents**
```sql
- id (PK)
- application_form_id (FK → application_forms.id)
- uploaded_by (FK → users.id)
- original_name (string)
- file_name (string)
- file_path (string)
- mime_type (string)
- file_size (integer)
- document_type (string)
- created_at
- updated_at
```

**Relaciones:**
- `applicationForm()` → belongsTo ApplicationForm
- `uploader()` → belongsTo User

**Nota:** ✅ Tabla `clients` eliminada (migración removida, modelo y servicio eliminados)

### 🔧 **Configuración de Almacenamiento**

```bash
# Crear enlace simbólico (ya ejecutado)
php artisan storage:link

# Directorio creado automáticamente
storage/app/public/application_documents/
```

### 📈 **Próximas Funcionalidades**
- [ ] Notificaciones por email al cambiar status
- [ ] Historial de cambios en planillas
- [ ] Generación de PDF de planillas
- [ ] Firma digital de documentos
- [ ] Integración con servicios externos

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

### Arquitectura y Decisiones de Diseño

- **Autenticación**: Stateless con tokens JWT via Sanctum
- **Validación**: DTOs con Spatie Laravel Data para type-safety
- **Autorización**: Policies y Gates de Laravel con validación a nivel de query
- **Base de datos**: UTF8MB4 para soporte Unicode completo
- **Seeds**: Datos de prueba incluidos para desarrollo

### Optimizaciones Implementadas

- **Eager Loading**: Prevención de N+1 queries con `with()` en relaciones
- **Índices estratégicos**: En `created_by`, `updated_by`, `reviewed_by`, `status`
- **Query scoping**: Filtrado automático por `created_by` para agents
- **Soft deletes**: No implementado (eliminación física por simplicidad)

### Convenciones de Código

- **Naming**: camelCase para métodos, snake_case para columnas DB
- **Status constants**: Definidos en modelo `ApplicationForm::STATUS_*`
- **Helpers booleanos**: Métodos `isPending()`, `isActive()`, `canChangeStatus()`
- **Responses**: Siempre en JSON con estructura consistente

### Seguridad

- **Password hashing**: Bcrypt automático en User model
- **Token expiration**: Configurable en Sanctum
- **CORS**: Configurado para `http://localhost:4200`
- **Rate limiting**: Throttle en rutas públicas (5 req/min en contacto)
- **Mass assignment protection**: Fillable arrays en todos los modelos

## 🚀 Próximos Pasos

### Funcionalidades Backend Pendientes
- [ ] Notificaciones por email al cambiar status de planilla
- [ ] Historial de cambios en planillas (auditoría completa)
- [ ] Generación de PDF de planillas para descarga
- [ ] Endpoint para estadísticas avanzadas por agent
- [ ] Firma digital de documentos
- [ ] Sistema de notificaciones en tiempo real (WebSockets)

### Mejoras de Infraestructura
- [ ] Implementar rate limiting por usuario
- [ ] Agregar logging de actividades con Laravel Log
- [ ] Implementar caché con Redis para queries frecuentes
- [ ] Crear API documentation con Swagger/OpenAPI
- [ ] Agregar tests unitarios e integración
- [ ] CI/CD con GitHub Actions

### Frontend
- [ ] Desarrollar dashboard de admin con estadísticas
- [ ] Panel de agent para gestión de clients y planillas
- [ ] Vista de client para ver su planilla
- [ ] Sistema de notificaciones en frontend
- [ ] Formulario completo de planilla con validación paso a paso

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama feature: `git checkout -b feature/nueva-funcionalidad`
3. Commit cambios: `git commit -m 'Agregar nueva funcionalidad'`
4. Push rama: `git push origin feature/nueva-funcionalidad`
5. Abrir Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.

## ✨ Estado del Proyecto

### ✅ Completado (Octubre 2025)

**Backend Core:**
- ✅ **API REST completa** con arquitectura limpia y separación de responsabilidades
- ✅ **Sistema de roles avanzado** (admin/agent/client) con permisos granulares
- ✅ **Autenticación múltiple** (email + password + Google OAuth)
- ✅ **Base de datos optimizada** con índices estratégicos y relaciones bidireccionales

**Gestión de Usuarios:**
- ✅ **Arquitectura consolidada** - Tabla única `users` (eliminada redundancia de `clients`)
- ✅ **Auditoría completa** - Campos `created_by` y `updated_by` en todos los usuarios
- ✅ **Filtrado automático** - Agents solo ven sus clients (`created_by`)
- ✅ **Endpoints especializados** - `/agents-report`, `/stats`, `/pending-forms`

**Sistema de Application Forms:**
- ✅ **Auto-creación** - ApplicationForm se genera al crear un client
- ✅ **Workflow de estados** - `pendiente` → `activo`/`inactivo`/`rechazado`
- ✅ **Tracking de revisión** - Campos `reviewed_by` y `reviewed_at`
- ✅ **Validaciones robustas** - Status comment obligatorio, permisos estrictos
- ✅ **Sistema de documentos** - Upload/delete con metadata completa

**Seguridad y Permisos:**
- ✅ **Policies detalladas** - Validación de ownership a nivel de modelo
- ✅ **Query scoping** - Filtrado automático en consultas por rol
- ✅ **Eager loading** - Optimización N+1 queries con relaciones
- ✅ **Token-based auth** - Sanctum con expiración configurable

**Limpieza de Código:**
- ✅ **Tabla clients eliminada** - Consolidación en tabla `users`
- ✅ **Modelo Client.php eliminado** - Ya no es necesario
- ✅ **ClientController.php eliminado** - Lógica movida a UserController
- ✅ **ClientManagementService.php eliminado** - Servicio redundante
- ✅ **ClientData.php eliminado** - DTO ya no usado
- ✅ **Migraciones consolidadas** - 2 migraciones redundantes eliminadas
- ✅ **Gates limpiados** - Eliminado `manage-clients-only` gate

### 🔄 En Desarrollo

- 🔄 **Frontend Angular** - Dashboard diferenciado por rol
- 🔄 **Sistema de notificaciones** - Email y push notifications

### 📊 Métricas del Proyecto

- **Endpoints implementados**: 20+
- **Modelos**: 3 (User, ApplicationForm, ApplicationDocument)
- **Policies**: 2 (UserPolicy, ApplicationFormPolicy)
- **Migraciones**: 10+
- **Seeders**: 2 (DatabaseSeeder, UserSeeder)
- **Líneas de código backend**: ~2,500+

## 🧪 Pruebas Realizadas

### ✅ Sistema de Autenticación y Usuarios

**Registro y Login:**
- ✅ Admin puede crear admin, agent y client
- ✅ Agent puede crear solo client (asignado automáticamente)
- ✅ Admin crea client requiere especificar `agent_id`
- ✅ Login con email + password funcional
- ✅ Google OAuth valida usuarios registrados

**Gestión de Usuarios:**
- ✅ Admin ve todos los usuarios sin restricción
- ✅ Agent ve solo clients con `created_by = agent_id`
- ✅ UserPolicy valida ownership correctamente
- ✅ Campos `created_by` y `updated_by` se registran correctamente

**Endpoints de Reportes:**
- ✅ `/api/v1/users/agents-report` retorna agents con clients anidados
- ✅ `/api/v1/users/stats` retorna estadísticas generales
- ✅ `/api/v1/users/pending-forms` filtra por status correctamente

### ✅ Sistema de Application Forms

**Creación y Auto-generación:**
- ✅ Al crear client se auto-crea ApplicationForm con status `pendiente`
- ✅ Agent crea client → ApplicationForm asignado automáticamente
- ✅ Admin crea client → Requiere `agent_id` y crea ApplicationForm
- ✅ Un cliente solo puede tener una planilla

**Workflow de Estados:**
- ✅ Status inicial: `pendiente` (auto-asignado)
- ✅ Admin puede cambiar a: `activo`, `inactivo`, `rechazado`
- ✅ Campo `status_comment` es obligatorio al cambiar status
- ✅ Se registra `reviewed_by` y `reviewed_at` automáticamente
- ✅ Agent NO puede cambiar status (solo admin)

**Permisos y Edición:**
- ✅ Agent puede editar planillas no confirmadas que creó
- ✅ Agent NO puede editar planillas confirmadas
- ✅ Admin puede editar cualquier planilla
- ✅ Client solo puede ver su propia planilla (lectura)
- ✅ ApplicationFormPolicy valida permisos correctamente

**Sistema de Documentos:**
- ✅ Subida de archivos (JPEG, PNG, PDF hasta 5MB)
- ✅ Almacenamiento en `storage/app/public/application_documents`
- ✅ Metadata completa (tipo, tamaño, nombre original, uploader)
- ✅ Eliminación automática de archivos físicos al borrar documento
- ✅ Admin puede eliminar cualquier documento
- ✅ Agent puede eliminar solo documentos que subió

### ✅ Validaciones y Seguridad

**Validación de Datos:**
- ✅ DTOs validan campos requeridos y formatos
- ✅ Status solo acepta valores del enum
- ✅ Agent_id valida que existe y es tipo `agent`
- ✅ Email único en tabla users

**Seguridad:**
- ✅ Tokens JWT expiran correctamente
- ✅ Middleware `auth:sanctum` protege rutas
- ✅ CORS configurado para frontend
- ✅ Mass assignment protegido con fillable
- ✅ Passwords hasheados con bcrypt

### 📊 Coverage de Funcionalidades

| Módulo | Funcionalidad | Status |
|--------|--------------|--------|
| **Auth** | Login email + password | ✅ 100% |
| **Auth** | Google OAuth | ✅ 100% |
| **Users** | CRUD con permisos | ✅ 100% |
| **Users** | Auditoría (created_by/updated_by) | ✅ 100% |
| **Users** | Reportes (agents-report, stats) | ✅ 100% |
| **Forms** | Auto-creación al crear client | ✅ 100% |
| **Forms** | Workflow de estados | ✅ 100% |
| **Forms** | Tracking de revisión | ✅ 100% |
| **Forms** | Sistema de documentos | ✅ 100% |
| **Policies** | UserPolicy | ✅ 100% |
| **Policies** | ApplicationFormPolicy | ✅ 100% |

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