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
  "status": "Activo",
  "status_comment": "Planilla aprobada y completa"
}
```

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

## � Flujo de Planillas de Aplicación

### 📋 Proceso de Creación

1. **Agent registra usuario tipo client** usando `/api/v1/auth/register`
2. **Agent crea planilla de aplicación** usando `/api/v1/application-forms`
3. **Planilla se crea con status "En Revisión"** y `confirmed = false`
4. **Agent puede editar** la planilla mientras no esté confirmada
5. **Agent confirma la planilla** marcando `confirmed = true`
6. **Admin puede cambiar status** a "Activo" o "Inactivo" con comentarios
7. **Cualquier usuario autorizado** puede subir documentos a la planilla

### 🔐 Estados y Transiciones

| Estado | Descripción | Transiciones | Editable |
|--------|-------------|--------------|----------|
| **En Revisión** | Planilla creada, pendiente de confirmación | → Activo, → Inactivo | ✅ Agent |
| **Activo** | Planilla confirmada y aprobada | → Inactivo | ❌ |
| **Inactivo** | Planilla rechazada o suspendida | → Activo, → En Revisión | ❌ |

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

#### ⚙️ **Campos de Control**
- `status`: Estado (Activo/Inactivo/En Revisión)
- `status_comment`: Comentario del status
- `confirmed`: Confirmación del agente (boolean)

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
| **Ver planillas** | ✅ Todas | ✅ Propias | ✅ Propia |
| **Crear planilla** | ❌ | ✅ Para sus clients | ❌ |
| **Editar planilla** | ✅ Siempre | ✅ Solo no confirmadas | ❌ |
| **Confirmar planilla** | ❌ | ✅ Propias | ❌ |
| **Cambiar status** | ✅ Todas | ❌ | ❌ |
| **Subir documentos** | ✅ Todas | ✅ Propias | ❌ |
| **Eliminar planilla** | ✅ Todas | ❌ | ❌ |

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

### 📊 **Flujo de Trabajo**

1. **Agent registra cliente** → Usuario tipo `client` creado
2. **Agent crea planilla** → Formulario con todos los datos
3. **Agent confirma planilla** → `confirmed = true`
4. **Admin revisa y aprueba** → Cambia `status` a "Activo"
5. **Agent sube documentos** → Archivos adjuntos a la planilla
6. **Client puede ver** → Su propia planilla (solo lectura)

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

#### **application_forms**
- 47 campos de datos + control
- Relaciones: `client_id`, `agent_id`
- Índices optimizados

#### **application_documents**
- Metadatos de archivos
- Relación con planilla
- Eliminación automática de archivos

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
- ✅ **Registro jerárquico** implementado y probado
- ✅ **Sistema de autenticación probado y verificado**
- ✅ **Planillas de aplicación** completamente implementadas y probadas
- ✅ **Sistema de documentos** con subida y gestión de archivos
- ✅ **Permisos avanzados** por rol implementados
- 🔄 **Frontend** pendiente de desarrollo
- 🔄 **Documentación API** puede mejorarse con Swagger

## 🧪 Pruebas Realizadas

### ✅ Verificación del Sistema de Planillas de Aplicación

**Creación de Planillas:**
- ✅ Agent puede crear planillas para sus clients
- ✅ Validación completa de 47+ campos
- ✅ Un cliente solo puede tener una planilla

**Permisos y Control:**
- ✅ Agent puede confirmar planillas (`confirmed = true`)
- ✅ Admin puede cambiar status (Activo/Inactivo/En Revisión)
- ✅ Agent NO puede editar planillas confirmadas
- ✅ Admin puede editar cualquier planilla
- ✅ Client solo puede ver su propia planilla

**Sistema de Documentos:**
- ✅ Subida de archivos (imágenes/PDF hasta 5MB)
- ✅ Almacenamiento seguro en directorio dedicado
- ✅ Eliminación automática de archivos al borrar planilla
- ✅ Metadatos completos (tipo, tamaño, nombre original)

**Flujo de Trabajo Validado:**
1. ✅ Agent crea client → Agent crea planilla → Agent confirma
2. ✅ Admin revisa → Admin aprueba (status: Activo)
3. ✅ Agent sube documentos → Sistema operativo completo

**Pruebas Automatizadas:**
- ✅ Script `test_application_forms.php` ejecutado exitosamente
- ✅ Todos los endpoints probados y funcionales
- ✅ Manejo de errores y permisos validado

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