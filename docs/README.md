# SubMate Backend

**API REST para gestión de suscripciones financieras**

Sistema backend profesional desarrollado con arquitectura en capas para el control y análisis de suscripciones recurrentes.

---

## 📋 Descripción

SubMate es una plataforma que permite a los usuarios gestionar sus suscripciones mensuales y anuales a servicios digitales (Netflix, Spotify, etc.), proporcionando:

- **Dashboard Analytics**: Gráficas de gasto mensual y distribución por método de pago
- **Gestión Completa**: CRUD de suscripciones con control de estados
- **Historial Financiero**: Tracking de pagos para análisis detallado
- **Sistema de Roles**: Admin, Beta Tester, y usuarios estándar

---

## 🛠️ Tecnologías

### Backend

- **PHP 8.1+** (Nativo, sin frameworks)
- **MySQL 8.0** con InnoDB y UTF8MB4
- **JWT** (Firebase PHP-JWT) para autenticación stateless
- **PDO** con prepared statements (seguridad anti-injection)
- **Composer** para gestión de dependencias

### Arquitectura

- **Patrón MVC** en capas (Router → Controller → Service → Model)
- **Singleton Pattern** para Database connection
- **Mapper Pattern** para transformación API ↔ DB
- **SOLID Principles** (límite de 5 métodos públicos por clase)

### Seguridad

- Passwords hasheados con **bcrypt**
- Tokens JWT con expiración (24 horas)
- CORS configurado para frontend
- Validación de roles por endpoint

---

## 📁 Estructura del Proyecto

```
submate-backend/
├── app/
│   ├── controllers/     # API Layer (HTTP handling)
│   ├── services/        # Business Logic Layer
│   ├── models/          # Data Access Layer (SQL)
│   ├── core/            # Router, Database, Auth, Middleware
│   └── routes/          # Route definitions (api.php)
├── docs/
│   ├── README.md        # Este archivo
│   ├── endpoints.md     # 📖 Documentación completa de API
│   ├── architecture.md  # Decisiones técnicas y patrones
│   └── database_schema.md  # Esquema de base de datos
├── public/
│   └── index.php        # Entry point (CORS + Router dispatch)
├── scripts/
│   ├── crear.php        # 🔧 Script de inicialización de BD
│   └── tests/           # Scripts de testing
├── vendor/              # Dependencias de Composer
├── .env                 # Variables de entorno (no en Git)
└── composer.json        # Dependencias del proyecto
```

---

## 🚀 Instalación

### 1. Requisitos Previos

- PHP >= 8.1
- MySQL >= 8.0
- Composer
- Servidor web (Apache/Nginx) o PHP built-in server

### 2. Clonar Repositorio

```bash
git clone https://github.com/tu-usuario/submate-backend.git
cd submate-backend
```

### 3. Instalar Dependencias

```bash
composer install
```

Esto instalará:

- `firebase/php-jwt` - Autenticación JWT
- `vlucas/phpdotenv` - Variables de entorno
- `phpmailer/phpmailer` - Envío de emails (OTP)

### 4. Configurar Entorno

Copia el archivo de ejemplo y configura tus credenciales:

```bash
copy .env.example .env
```

Edita `.env` con tus datos:

```env
DB_HOST=localhost
DB_NAME=db_submate_ahjr
DB_USER=root
DB_PASS=tu_password
DB_CHARSET=utf8mb4

JWT_SECRET=tu_clave_secreta_super_segura_aqui
JWT_ISSUER=submate-api
JWT_AUDIENCE=submate-frontend

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_contraseña_app
MAIL_FROM=noreply@submate.com
MAIL_FROM_NAME=SubMate
```

### 5. Inicializar Base de Datos

Ejecuta el script de creación (esto **crea la BD, tablas, y usuarios de prueba**):

```bash
php scripts/crear.php
```

**Usuarios creados automáticamente:**

| Email                 | Password    | Rol   | Descripción                                |
| --------------------- | ----------- | ----- | ------------------------------------------ |
| `admin@submate.app`   | `Admin123!` | admin | Administrador                              |
| `beta@submate.app`    | `Beta123!`  | beta  | Beta Tester (con suscripciones de ejemplo) |
| `usuario@submate.app` | `User123!`  | user  | Usuario estándar                           |

> **💡 Nota**: El usuario **Beta** tiene 2 suscripciones (Netflix, Spotify) y 6 meses de historial pre-cargado para testing de dashboards.

---

## ▶️ Ejecución

### Servidor PHP Integrado (Desarrollo)

```bash
php -S localhost:8000 -t public
```

La API estará disponible en: `http://localhost:8000`

### Apache/Nginx (Producción)

Configura el DocumentRoot hacia la carpeta `public/`:

**Apache `.htaccess`** (ya incluido en `public/`):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

**Nginx**:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## 📡 Uso de la API

### Endpoint de Bienvenida

```bash
curl http://localhost:8000/
```

Respuesta:

```json
{
  "message": "Bienvenido al backend de SubMate 🚀",
  "version": "2.0",
  "endpoints": { ... }
}
```

### Autenticación

**Login:**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"beta@submate.app","clave":"Beta123!"}'
```

Respuesta:

```json
{
  "message": "Login exitoso.",
  "usuario": {
    "id": 2,
    "nombre": "Usuario",
    "apellido": "Beta",
    "email": "beta@submate.app",
    "rol": "beta"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Petición Autenticada

```bash
curl http://localhost:8000/api/suscripciones \
  -H "Authorization: Bearer {token}"
```

---

## 📚 Documentación

Para información detallada sobre:

- **Endpoints disponibles**: Ver [`docs/endpoints.md`](./endpoints.md)
  - Todos los endpoints con ejemplos JSON
  - Integración con React/Axios
  - Uso de Chart.js
- **Arquitectura del sistema**: Ver [`docs/architecture.md`](./architecture.md)

  - Decisiones de diseño
  - Patrones aplicados
  - Justificaciones técnicas

- **Esquema de base de datos**: Ver [`docs/database_schema.md`](./database_schema.md)
  - Tablas y relaciones
  - Triggers y stored procedures
  - Queries importantes

---

## 🧪 Testing

Los scripts de testing están en `scripts/tests/`:

```bash
# Test de autenticación
php scripts/tests/test_auth_module.php

# Test de suscripciones
php scripts/tests/test_suscripciones.php

# Test de dashboard
php scripts/tests/test_dashboard.php

# Test de infraestructura general
php scripts/tests/test_infrastructure.php
```

---

## 🏗️ Características Principales

### Módulo Auth

- ✅ Registro con validación de email
- ✅ Login con JWT
- ✅ Verificación de tokens
- ✅ Reset de contraseña con OTP

### Módulo Suscripciones

- ✅ CRUD completo (Create, Read, Update, Delete)
- ✅ Cambio de estado (activa/inactiva)
- ✅ Simulación de pagos (solo Beta/Admin)
- ✅ Cálculo automático de fechas

### Módulo Dashboard

- ✅ KPIs financieros
- ✅ Gráfica de gasto mensual (últimos 6 meses)
- ✅ Distribución por método de pago
- ✅ Próximo vencimiento
- ✅ **Datos listos para Chart.js**

---

## 🔐 Seguridad

- **JWT** con expiración automática (24h)
- **Passwords** hasheados con bcrypt (cost 12)
- **SQL Injection** prevenido con PDO prepared statements
- **CORS** configurado para frontends permitidos
- **Validación de roles** por endpoint
- **Tokens** verificados en cada petición protegida

---

## 🌐 CORS

El backend permite peticiones desde:

- `http://localhost:3000` (desarrollo - React)
- Configurable en `public/index.php`

---

## 📊 Arquitectura SOLID

### Regla de los 5 Métodos

Cada clase tiene **máximo 5 métodos públicos**, promoviendo:

- Single Responsibility Principle
- Clases pequeñas y enfocadas
- Mejor mantenibilidad

**Ejemplo:**

```
✅ SuscripcionController (CRUD - 5 métodos):
   1. index()   2. store()   3. show()   4. update()   5. destroy()

✅ SuscripcionOperacionesController (Operaciones - 2 métodos):
   1. cambiarEstado()   2. simularPago()
```

---

## 🤝 Contribución

Para contribuir al proyecto:

1. Fork el repositorio
2. Crea una branch (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -m 'feat: nueva funcionalidad'`)
4. Push branch (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

---

## 📝 Licencia

Este proyecto es parte de un trabajo académico.  
**Universidad:** [Tu Universidad]  
**Materia:** Desarrollo de Aplicaciones Web  
**Año:** 2025

---

## ✨ Autor

Desarrollado por **[Tu Nombre]** como proyecto académico de grado.

**Contacto:**

- Email: tu.email@ejemplo.com
- GitHub: [@tu-usuario](https://github.com/tu-usuario)

---

## 🙏 Agradecimientos

- Firebase PHP-JWT por la librería de autenticación
- PHP Dotenv por la gestión de variables
- PHPMailer por el envío de emails
- La comunidad PHP por el soporte y documentación

---

**SubMate Backend v2.0** | Gestión Inteligente de Suscripciones 💳
