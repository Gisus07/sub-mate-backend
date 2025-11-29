# SubMate Backend - Architecture Documentation

**Documentación de decisiones técnicas y arquitectura del sistema.**

---

## Vista General del Sistema

SubMate es una aplicación de gestión de suscripciones con un backend PHP basado en **arquitectura en capas** y un frontend React.

```
┌─────────────────────────────────────────────────────────────┐
│                      FRONTEND (React)                       │
│  Chart.js │ Axios │ React Router │ Context API              │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTP/JSON + JWT
                           │ CORS habilitado
┌──────────────────────────┴──────────────────────────────────┐
│                  BACKEND (PHP 8.1+)                         │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │  ROUTER (index.php)                                │    │
│  │  - Manejo de CORS                                 │    │
│  │  - Dispatch de rutas                              │    │
│  └─────────────┬──────────────────────────────────────┘    │
│                │                                             │
│  ┌─────────────┴──────────────────────────────────────┐    │
│  │  CONTROLLERS (API Layer)                           │    │
│  │  - AuthController                                  │    │
│  │  - UsuarioController                               │    │
│  │  - SuscripcionController (CRUD)                   │    │
│  │  - SuscripcionOperacionesController (RPC)         │    │
│  │  - DashboardController                             │    │
│  └─────────────┬──────────────────────────────────────┘    │
│                │                                             │
│  ┌─────────────┴──────────────────────────────────────┐    │
│  │  SERVICES (Business Logic)                         │    │
│  │  - AuthService (JWT, Passwords)                    │    │
│  │  - UsuarioService                                  │    │
│  │  - SuscripcionService                              │    │
│  │  - DashboardService (Chart.js formatting)          │    │
│  └─────────────┬──────────────────────────────────────┘    │
│                │                                             │
│  ┌─────────────┴──────────────────────────────────────┐    │
│  │  MODELS (Data Access)                              │    │
│  │  - UsuarioModel                                    │    │
│  │  - SuscripcionModel                                │    │
│  │  - SuscripcionOperacionesModel                     │    │
│  │  - DashboardModel (Analytics)                      │    │
│  └─────────────┬──────────────────────────────────────┘    │
│                │ PDO                                         │
└────────────────┴─────────────────────────────────────────────┘
                 │
┌────────────────┴─────────────────────────────────────────────┐
│              DATABASE (MySQL 8.0)                            │
│  - td_usuarios_ahjr                                          │
│  - td_suscripciones_ahjr                                     │
│  - td_historial_pagos_ahjr                                   │
│  - td_registro_pendiente_ahjr                                │
│  - td_reset_clave_ahjr                                       │
└──────────────────────────────────────────────────────────────┘
```

---

## Arquitectura en Capas

### 1. Router Layer (Enrutamiento)

**Archivo:** `public/index.php`, `app/routes/api.php`

**Responsabilidades:**

- Manejar headers CORS
- Mapear rutas HTTP a controladores
- Extraer parámetros de URL (ej: `/suscripciones/15` → `$id = 15`)

**Tecnología:** Regex-based router personalizado

```php
// Ejemplo de definición de ruta
$router->add_ahjr('GET', '/api/suscripciones/(\d+)', function ($id) use ($controller) {
    $controller->show((int) $id);
});
```

---

### 2. Controller Layer (API)

**Ubicación:** `app/controllers/`

**Responsabilidades:**

- Recibir peticiones HTTP
- Validar entrada básica
- Llamar a servicios
- Formatear respuesta JSON
- Manejar errores HTTP

**Ejemplo:**

```php
class SuscripcionController {
    public function index(): void {
        $usuario = $this->middleware->handle(); // Auth
        $suscripciones = $this->service->obtenerLista($usuario['sub']);
        $this->responder(['suscripciones' => $suscripciones]);
    }
}
```

---

### 3. Service Layer (Lógica de Negocio)

**Ubicación:** `app/services/`

**Responsabilidades:**

- Validaciones complejas
- Lógica de negocio
- **Mapper pattern** (sufijos `_ahjr` ↔ nombres limpios)
- Orquestar múltiples modelos

**Ejemplo de Mapper:**

```php
// Entrada del frontend (limpia)
$input = ['nombre_servicio' => 'Netflix', 'costo' => 7.99];

// Mapper convierte a formato DB
$datosDB = [
    'nombre_servicio_ahjr' => $input['nombre_servicio'],
    'costo_ahjr' => $input['costo']
];

// Salida al frontend (limpia de nuevo)
$output = ['nombre_servicio' => $row['nombre_servicio_ahjr']];
```

---

### 4. Model Layer (Acceso a Datos)

**Ubicación:** `app/models/`

**Responsabilidades:**

- Queries SQL (PDO)
- CRUD básico
- Stored Procedures
- NO lógica de negocio

**Ejemplo:**

```php
class SuscripcionModel {
    public function listarPorUsuario(int $uid): array {
        $sql = "SELECT * FROM td_suscripciones_ahjr
                WHERE id_usuario_suscripcion_ahjr = :uid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid]);
        return $stmt->fetchAll();
    }
}
```

---

## Decisiones Clave de Diseño

### 1. Regla de los 5 Métodos Públicos

**Restricción autoimpuesta:** Ninguna clase debe tener más de 5 métodos públicos.

**Justificación:**

- Fomenta **Single Responsibility Principle**
- Evita clases "God Object"
- Mejora mantenibilidad y testing

**Ejemplo problemático previo:**

```
❌ SuscripcionController:
   1. index()
   2. store()
   3. show()
   4. update()
   5. destroy()
   6. cambiarEstado()      ← VIOLACIÓN
   7. simularPago()        ← VIOLACIÓN
```

**Solución aplicada:**

```
✅ SuscripcionController (CRUD estándar - 5 métodos):
   1. index()
   2. store()
   3. show()
   4. update()
   5. destroy()

✅ SuscripcionOperacionesController (Operaciones especiales - 2 métodos):
   1. cambiarEstado()
   2. simularPago()
```

**Beneficios:**

- Responsabilidades claras y separadas
- Código más limpio y entendible
- Facilita testing unitario

---

### 2. Patrón Mapper (Sufijos `_ahjr`)

**Problema:** La base de datos usa sufijo `_ahjr` en todas las columnas para identificación del proyecto.

**Solución:** El Service layer actúa como **Mapper** bidireccional:

```
Frontend (Limpio)  ←→  Service (Mapper)  ←→  Database (_ahjr)
┌──────────────┐      ┌──────────────┐      ┌─────────────────────┐
│ nombre_servicio │  →  │  Convertir   │  →  │ nombre_servicio_ahjr│
│ costo          │  →  │  a formato   │  →  │ costo_ahjr          │
│ metodo_pago    │  →  │  de DB       │  →  │ metodo_pago_ahjr    │
└──────────────┘      └──────────────┘      └─────────────────────┘
```

**Implementación:**

```php
// Service Layer
private function limpiarSufijos(array $datos): array {
    return [
        'id' => $datos['id_suscripcion_ahjr'],
        'nombre_servicio' => $datos['nombre_servicio_ahjr'],
        'costo' => (float) $datos['costo_ahjr'],
        'metodo_pago' => $datos['metodo_pago_ahjr']
    ];
}
```

**Ventajas:**

- API limpia y profesional para el frontend
- Base de datos con nomenclatura interna consistente
- Separación clara de preocupaciones

---

### 3. Lógica Financiera Híbrida

**Tablas involucradas:**

- `td_suscripciones_ahjr` → Costos fijos (configuración)
- `td_historial_pagos_ahjr` → Pagos reales registrados

**Problema:** ¿Cómo calcular el gasto mensual actual?

**Solución - Lógica Híbrida en Dashboard:**

```
┌──────────────────────────────────────────────────────────┐
│ Dashboard: obtenerGastoTotalMes(uid, mes, año)           │
└──────────────────────────────────────────────────────────┘
                          │
                    ¿Mes Pasado?
                    ┌────┴────┐
                   SI        NO (Mes Actual)
                    │          │
         ┌──────────┘          └──────────┐
         │                                 │
         ▼                                 ▼
┌──────────────────┐         ┌─────────────────────────────┐
│ SOLO HISTORIAL   │         │ HISTORIAL + PROYECCIÓN      │
│                  │         │                             │
│ SELECT SUM(...)  │         │ (SELECT SUM historial)      │
│ FROM historial   │         │ +                           │
│ WHERE mes = X    │         │ (SELECT SUM suscripciones   │
│                  │         │  WHERE estado = 'activa')   │
└──────────────────┘         └─────────────────────────────┘
```

**Código simplificado:**

```php
public function obtenerGastoTotalMes(int $uid, int $mes, int $anio): float {
    $esMesPasado = ($anio < date('Y')) ||
                   ($anio === date('Y') && $mes < date('n'));

    if ($esMesPasado) {
        // Solo suma historial real
        return $this->sumarHistorial($uid, $mes, $anio);
    } else {
        // Historial + proyección de activas este mes
        return $this->sumarHistorial($uid, $mes, $anio) +
               $this->sumarActivasMensuales($uid);
    }
}
```

**Justificación:**

- **Meses pasados:** Solo datos reales (precisión histórica)
- **Mes actual:** Incluye suscripciones activas que aún no se cobraron (proyección realista)
- **Frontend:** Puede mostrar gráficas precisas sin lógica adicional

---

### 4. Tabla de Historial de Pagos

**Motivación:** Las gráficas requieren datos históricos.

**Problema inicial:**

- Tabla `td_suscripciones_ahjr` solo tiene `fecha_ultimo_pago`
- No hay registro de pagos anteriores

**Solución:**
Crear tabla `td_historial_pagos_ahjr` con:

- `fecha_pago_ahjr` → Cuando se realizó el pago
- `monto_pagado_ahjr` → Cuánto se pagó (snapshot)
- `metodo_pago_snapshot_ahjr` → Método usado (puede cambiar en suscripción)

```sql
CREATE TABLE td_historial_pagos_ahjr (
    id_historial_ahjr INT PRIMARY KEY AUTO_INCREMENT,
    id_suscripcion_historial_ahjr INT,
    monto_pagado_ahjr DECIMAL(10,2),
    fecha_pago_ahjr DATE,  -- CRÍTICO para gráficas
    metodo_pago_snapshot_ahjr VARCHAR(20)
);
```

**Uso en Dashboard:**

```sql
-- Gasto por mes (últimos 6 meses)
SELECT
    DATE_FORMAT(fecha_pago_ahjr, '%Y-%m') as mes,
    SUM(monto_pagado_ahjr) as total
FROM td_historial_pagos_ahjr
WHERE id_usuario = ?
AND fecha_pago_ahjr >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY mes;
```

**Ventajas:**

- Gráficas precisas con datos históricos
- Snapshots de costos (captura cambios de precio)
- Analytics detallados por método de pago

---

### 5. Singleton para Database

**Implementación:**

```php
class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private function __construct() { }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
}
```

**Uso:**

```php
$db = Database::getDB(); // Método estático conveniente
```

**Beneficios:**

- Una sola conexión PDO por request
- Lazy loading (solo conecta si se usa)
- Evita múltiples conexiones simultáneas

---

## Seguridad

### 1. Autenticación JWT

**Flujo:**

```
1. Usuario → POST /api/auth/login
2. Backend → Valida credenciales
3. Backend → Genera JWT con payload:
   {
     "sub": 15,          // User ID
     "email": "user@...",
     "rol": "beta",      // Importante para permisos
     "exp": 1234567890   // Expiración
   }
4. Frontend → Guarda token en localStorage
5. Frontend → Incluye "Authorization: Bearer {token}" en requests
6. Backend → Valida token en cada request protegido
```

**Implementación:**

```php
// AuthService
public function generarToken(array $usuario): string {
    $payload = [
        'sub' => $usuario['id'],
        'email' => $usuario['email'],
        'rol' => $usuario['rol'],
        'exp' => time() + (60 * 60 * 24) // 24 horas
    ];
    return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
}
```

### 2. Middleware de Autenticación

**Patrón aplicado:**

```php
class SuscripcionController {
    public function index() {
        // Middleware valida token y extrae usuario
        $usuario = $this->middleware->handle();

        // $usuario = ['sub' => 15, 'email' => '...', 'rol' => 'beta']
        $suscripciones = $this->service->obtenerLista($usuario['sub']);
    }
}
```

### 3. Validación de Permisos por Rol

**Ejemplo - Simular Pago (solo beta/admin):**

```php
public function simularPago(int $id): void {
    $usuario = $this->middleware->handle();

    // Validar rol
    if (!in_array($usuario['rol'], ['beta', 'admin'])) {
        throw new Exception('Acceso denegado. Solo usuarios beta.', 403);
    }

    // Continuar con la operación...
}
```

---

## Patrones de Diseño Aplicados

### 1. MVC (Model-View-Controller)

- **Model:** Acceso a datos (PDO, SQL)
- **View:** JSON responses (no HTML templates)
- **Controller:** Manejo de HTTP requests/responses

### 2. Service Layer Pattern

- Desacopla controllers de lógica de negocio
- Facilita testing y reutilización

### 3. Repository Pattern (Implícito en Models)

- Models actúan como repositories de datos
- Abstraen SQL del resto del sistema

### 4. Mapper/DTO Pattern

- Services mapean entre API DTOs y Database entities
- Limpieza de sufijos `_ahjr`

### 5. Singleton (Database)

- Una sola instancia de conexión PDO

---

## Justificación para la Defensa del Proyecto

### Escalabilidad

**Arquitectura en capas permite:**

- Cambiar DB sin tocar controllers
- Agregar nuevos endpoints fácilmente
- Testing unitario por capa

### Mantenibilidad

**Regla de 5 métodos:**

- Clases pequeñas y enfocadas
- Fácil de entender y modificar
- Reducción de bugs

### Profesionalismo

**API Limpia:**

- JSON sin ruido técnico (`_ahjr`)
- Nombres descriptivos
- Documentación completa

### Performance

**Optimizaciones:**

- Singleton de DB (1 conexión)
- Indexes en tablas (fecha_pago, id_usuario)
- Queries optimizadas con JOINs

### Seguridad

**Múltiples capas:**

- JWT con expiración
- Validación de permisos por rol
- CORS configurado correctamente
- PDO con prepared statements (previene SQL injection)

---

## Futuras Mejoras

### 1. Caching

```php
// Redis para dashboard (datos analíticos pesados)
$cache->remember('dashboard_user_15', 600, function() {
    return $this->dashboardService->generarResumen(15);
});
```

### 2. Rate Limiting

```php
// Limitar login attempts
if ($attempts > 5) {
    throw new Exception('Demasiados intentos', 429);
}
```

### 3. Logs Estructurados

```php
// Monolog para debugging
$logger->info('Simulación de pago', [
    'user_id' => $uid,
    'subscription_id' => $id,
    'amount' => $monto
]);
```

### 4. Testing Automatizado

```php
// PHPUnit
public function testCrearSuscripcion() {
    $service = new SuscripcionService();
    $id = $service->crear($this->datosValidos, 15);
    $this->assertIsInt($id);
}
```

---

**Documento creado para:** Defensa de proyecto académico  
**Audiencia:** Profesor/evaluadores técnicos  
**Última actualización:** Noviembre 2025
