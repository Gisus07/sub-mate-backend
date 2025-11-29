# SubMate - Database Schema

**Referencia rápida del esquema de base de datos.**

---

## Diagrama General

```
┌─────────────────────────┐
│  td_usuarios_ahjr       │
│  ├─ id_ahjr (PK)        │
│  ├─ email_ahjr (UNIQUE) │
│  ├─ rol_ahjr            │──┐
│  └─ estado_ahjr         │  │
└─────────────────────────┘  │
                             │ FK: id_usuario_suscripcion_ahjr
                             │
┌─────────────────────────┐  │
│ td_suscripciones_ahjr   │◄─┘
│ ├─ id_suscripcion_ahjr  │──┐
│ ├─ nombre_servicio_ahjr │  │
│ ├─ costo_ahjr           │  │
│ ├─ frecuencia_ahjr      │  │
│ ├─ estado_ahjr          │  │
│ └─ dia_cobro_ahjr       │  │
└─────────────────────────┘  │
                             │ FK: id_suscripcion_historial_ahjr
                             │
┌────────────────────────────┐│
│ td_historial_pagos_ahjr   ││
│ ├─ id_historial_ahjr (PK)◄┘
│ ├─ monto_pagado_ahjr      │
│ ├─ fecha_pago_ahjr        │
│ └─ metodo_pago_snapshot  │
└────────────────────────────┘

┌─────────────────────────────┐
│ td_registro_pendiente_ahjr  │
│ ├─ id_pendiente_ahjr (PK)   │
│ ├─ email_ahjr (UNIQUE)      │
│ └─ otp_hash_ahjr            │
└─────────────────────────────┘

┌─────────────────────────┐
│ td_reset_clave_ahjr     │
│ ├─ id_reset_ahjr (PK)   │
│ ├─ email_ahjr           │
│ └─ otp_hash_ahjr        │
└─────────────────────────┘
```

---

## Tablas Principales

### 1. `td_usuarios_ahjr`

**Descripción:** Usuarios del sistema.

| Columna               | Tipo                | Descripción                   |
| --------------------- | ------------------- | ----------------------------- |
| `id_ahjr`             | INT (PK)            | ID único del usuario          |
| `nombre_ahjr`         | VARCHAR(80)         | Nombre                        |
| `apellido_ahjr`       | VARCHAR(100)        | Apellido                      |
| `email_ahjr`          | VARCHAR(120) UNIQUE | Email (login)                 |
| `clave_ahjr`          | VARCHAR(255)        | Password hasheado (bcrypt)    |
| `fecha_registro_ahjr` | DATETIME            | Fecha de registro             |
| `estado_ahjr`         | ENUM                | `'activo'`, `'inactivo'`      |
| `rol_ahjr`            | ENUM                | `'admin'`, `'beta'`, `'user'` |

**ENUM `rol_ahjr`:**

- `admin` → Acceso total
- `beta` → Puede simular pagos
- `user` → Usuario estándar

**ENUM `estado_ahjr`:**

- `activo` → Puede usar el sistema
- `inactivo` → Cuenta deshabilitada

**Índices:**

- PRIMARY KEY: `id_ahjr`
- UNIQUE: `email_ahjr`

---

### 2. `td_suscripciones_ahjr`

**Descripción:** Suscripciones de los usuarios.

| Columna                       | Tipo          | Descripción                      |
| ----------------------------- | ------------- | -------------------------------- |
| `id_suscripcion_ahjr`         | INT (PK)      | ID único de la suscripción       |
| `id_usuario_suscripcion_ahjr` | INT (FK)      | ID del usuario propietario       |
| `nombre_servicio_ahjr`        | VARCHAR(100)  | Nombre del servicio              |
| `costo_ahjr`                  | DECIMAL(10,2) | Costo de la suscripción          |
| `estado_ahjr`                 | ENUM          | `'activa'`, `'inactiva'`         |
| `frecuencia_ahjr`             | ENUM          | `'mensual'`, `'anual'`           |
| `metodo_pago_ahjr`            | ENUM          | Método de pago                   |
| `dia_cobro_ahjr`              | TINYINT       | Día del mes (1-31)               |
| `mes_cobro_ahjr`              | TINYINT NULL  | Mes del año (1-12), solo anuales |
| `fecha_ultimo_pago_ahjr`      | DATE          | Última fecha de pago             |
| `fecha_creacion_ahjr`         | TIMESTAMP     | Cuándo se creó                   |
| `fecha_actualizacion_ahjr`    | TIMESTAMP     | Última modificación              |

**ENUM `frecuencia_ahjr`:**

- `mensual` → Se cobra cada mes
- `anual` → Se cobra una vez al año

**ENUM `metodo_pago_ahjr`:**

- `MasterCard`
- `Visa`
- `GPay`
- `PayPal`

**Restricciones:**

- `dia_cobro_ahjr`: BETWEEN 1 AND 31
- `mes_cobro_ahjr`: NULL OR BETWEEN 1 AND 12
- Foreign Key: `id_usuario_suscripcion_ahjr` → `td_usuarios_ahjr.id_ahjr` (ON DELETE CASCADE)

**Índices:**

- PRIMARY KEY: `id_suscripcion_ahjr`
- INDEX: `id_usuario_suscripcion_ahjr`

---

### 3. `td_historial_pagos_ahjr`

**Descripción:** Historial de todos los pagos realizados (para gráficas).

| Columna                         | Tipo          | Descripción                                |
| ------------------------------- | ------------- | ------------------------------------------ |
| `id_historial_ahjr`             | INT (PK)      | ID único del registro                      |
| `id_suscripcion_historial_ahjr` | INT (FK)      | ID de la suscripción                       |
| `monto_pagado_ahjr`             | DECIMAL(10,2) | Monto pagado (snapshot)                    |
| `fecha_pago_ahjr`               | DATE          | Fecha del pago (**crítico para gráficas**) |
| `metodo_pago_snapshot_ahjr`     | VARCHAR(20)   | Método usado en ese momento                |
| `creado_en_ahjr`                | TIMESTAMP     | Cuándo se registró                         |

**Propósito:**

- Almacenar **snapshots** de pagos para analytics
- Permite gráficas históricas precisas
- Captura cambios de precio (el costo puede variar en la suscripción)

**Restricciones:**

- Foreign Key: `id_suscripcion_historial_ahjr` → `td_suscripciones_ahjr.id_suscripcion_ahjr` (ON DELETE CASCADE)

**Índices:**

- PRIMARY KEY: `id_historial_ahjr`
- INDEX: `id_suscripcion_historial_ahjr`
- INDEX: `fecha_pago_ahjr` (**importante para queries de dashboard**)

**Ejemplo de uso:**

```sql
-- Gasto mensual de los últimos 6 meses
SELECT
    DATE_FORMAT(fecha_pago_ahjr, '%Y-%m') as mes,
    SUM(monto_pagado_ahjr) as total
FROM td_historial_pagos_ahjr h
INNER JOIN td_suscripciones_ahjr s
    ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
WHERE s.id_usuario_suscripcion_ahjr = ?
AND fecha_pago_ahjr >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY mes
ORDER BY mes;
```

---

### 4. `td_registro_pendiente_ahjr`

**Descripción:** Registros pendientes de verificación (OTP).

| Columna             | Tipo                | Descripción          |
| ------------------- | ------------------- | -------------------- |
| `id_pendiente_ahjr` | INT (PK)            | ID único             |
| `nombre_ahjr`       | VARCHAR(80)         | Nombre temporal      |
| `apellido_ahjr`     | VARCHAR(100)        | Apellido temporal    |
| `email_ahjr`        | VARCHAR(120) UNIQUE | Email                |
| `clave_ahjr`        | VARCHAR(255)        | Password hasheado    |
| `otp_hash_ahjr`     | VARCHAR(255)        | Código OTP hasheado  |
| `otp_expira_ahjr`   | DATETIME            | Expiración del OTP   |
| `creado_ahjr`       | DATETIME            | Fecha de creación    |
| `usado_ahjr`        | TINYINT(1)          | Si ya fue verificado |

**Flujo:**

1. Usuario se registra → Se crea registro aquí
2. Se envía email con OTP
3. Usuario verifica OTP → Se mueve a `td_usuarios_ahjr`
4. Registro se marca como `usado_ahjr = 1`

**Índices:**

- PRIMARY KEY: `id_pendiente_ahjr`
- UNIQUE: `email_ahjr`

---

### 5. `td_reset_clave_ahjr`

**Descripción:** Solicitudes de reset de contraseña.

| Columna           | Tipo                | Descripción         |
| ----------------- | ------------------- | ------------------- |
| `id_reset_ahjr`   | INT (PK)            | ID único            |
| `email_ahjr`      | VARCHAR(120) UNIQUE | Email del usuario   |
| `otp_hash_ahjr`   | VARCHAR(255)        | Código OTP hasheado |
| `otp_expira_ahjr` | DATETIME            | Expiración del OTP  |
| `creado_ahjr`     | DATETIME            | Fecha de solicitud  |
| `usado_ahjr`      | TINYINT(1)          | Si ya fue usado     |

**Flujo:**

1. Usuario solicita reset → Se crea registro
2. Se envía email con OTP
3. Usuario verifica OTP y cambia contraseña
4. Registro se marca como `usado_ahjr = 1`

**Índices:**

- PRIMARY KEY: `id_reset_ahjr`
- UNIQUE: `email_ahjr`

---

## Triggers

### `tr_actualizar_fecha_ahjr`

**Tipo:** BEFORE UPDATE  
**Tabla:** `td_suscripciones_ahjr`

**Propósito:** Actualizar automáticamente `fecha_actualizacion_ahjr` en cada modificación.

```sql
CREATE TRIGGER tr_actualizar_fecha_ahjr
BEFORE UPDATE ON td_suscripciones_ahjr
FOR EACH ROW
BEGIN
    SET NEW.fecha_actualizacion_ahjr = CURRENT_TIMESTAMP;
END;
```

---

## Stored Procedures

### `sp_crear_suscripcion_ahjr`

**Propósito:** Crear una suscripción y calcular automáticamente `fecha_ultimo_pago`.

**Parámetros:**

- `p_id_usuario` INT
- `p_nombre_servicio` VARCHAR(100)
- `p_costo` DECIMAL(10,2)
- `p_frecuencia` ENUM('mensual','anual')
- `p_metodo_pago` ENUM(...)
- `p_dia_cobro` TINYINT
- `p_mes_cobro` TINYINT

**Lógica:**

```
SI frecuencia = 'mensual' ENTONCES
    SI día_actual >= dia_cobro ENTONCES
        fecha_ultimo_pago = este_mes + dia_cobro
    SINO
        fecha_ultimo_pago = mes_anterior + dia_cobro
    FIN SI

SI frecuencia = 'anual' ENTONCES
    SI ya_pasó_fecha_este_año ENTONCES
        fecha_ultimo_pago = este_año + mes_cobro + dia_cobro
    SINO
        fecha_ultimo_pago = año_anterior + mes_cobro + dia_cobro
    FIN SI
FIN SI
```

**Retorna:** `id_suscripcion_ahjr` del registro creado

---

## Relaciones (Foreign Keys)

```
td_usuarios_ahjr (id_ahjr)
  ↓ ON DELETE CASCADE
td_suscripciones_ahjr (id_usuario_suscripcion_ahjr)
  ↓ ON DELETE CASCADE
td_historial_pagos_ahjr (id_suscripcion_historial_ahjr)
```

**Comportamiento CASCADE:**

- Si eliminas un usuario → Se eliminan todas sus suscripciones
- Si eliminas una suscripción → Se elimina todo su historial

---

## Nomenclatura

**Sufijo `_ahjr`:**  
Todas las tablas y columnas llevan el sufijo `_ahjr` para identificación del proyecto.

**Convención:**

- Tablas: `td_[nombre]_ahjr` (td = tabla de datos)
- Triggers: `tr_[nombre]_ahjr`
- Stored Procedures: `sp_[nombre]_ahjr`
- Columnas: `[nombre]_ahjr`

**Nota para API:**  
El Service Layer hace el mapeo para eliminar estos sufijos en las respuestas JSON.

---

## Seeding de Datos de Prueba

El script `scripts/crear.php` crea 3 usuarios:

1. **admin@submate.app** (rol: admin)  
   Password: `Admin123!`

2. **beta@submate.app** (rol: beta)  
   Password: `Beta123!`

   - 2 suscripciones: Netflix ($7.99), Spotify ($11.49)
   - 12 registros de historial (6 meses × 2 suscripciones)

3. **usuario@submate.app** (rol: user)  
   Password: `User123!`

---

## Queries Importantes

### Gasto total por usuario

```sql
SELECT
    u.email_ahjr,
    SUM(h.monto_pagado_ahjr) as total_gastado
FROM td_usuarios_ahjr u
INNER JOIN td_suscripciones_ahjr s
    ON u.id_ahjr = s.id_usuario_suscripcion_ahjr
INNER JOIN td_historial_pagos_ahjr h
    ON s.id_suscripcion_ahjr = h.id_suscripcion_historial_ahjr
GROUP BY u.id_ahjr;
```

### Suscripciones próximas a vencer

```sql
SELECT
    nombre_servicio_ahjr,
    costo_ahjr,
    dia_cobro_ahjr
FROM td_suscripciones_ahjr
WHERE id_usuario_suscripcion_ahjr = ?
AND estado_ahjr = 'activa'
ORDER BY dia_cobro_ahjr ASC;
```

### Distribución por método de pago

```sql
SELECT
    metodo_pago_snapshot_ahjr,
    SUM(monto_pagado_ahjr) as total
FROM td_historial_pagos_ahjr h
INNER JOIN td_suscripciones_ahjr s
    ON h.id_suscripcion_historial_ahjr = s.id_suscripcion_ahjr
WHERE s.id_usuario_suscripcion_ahjr = ?
GROUP BY metodo_pago_snapshot_ahjr;
```

---

## Optimizaciones Implementadas

1. **Índices en columnas frecuentemente buscadas:**

   - `td_usuarios_ahjr.email_ahjr` (UNIQUE)
   - `td_suscripciones_ahjr.id_usuario_suscripcion_ahjr`
   - `td_historial_pagos_ahjr.fecha_pago_ahjr` (**crítico para dashboard**)

2. **Charset UTF8MB4:**

   - Soporte completo de emojis y caracteres especiales

3. **ON DELETE CASCADE:**

   - Integridad referencial automática

4. **Stored Procedures:**
   - Lógica de fechas centralizada en la DB

---

## Backup y Migración

### Exportar BD

```bash
mysqldump -u root -p db_submate_ahjr > backup_submate.sql
```

### Importar BD

```bash
mysql -u root -p db_submate_ahjr < backup_submate.sql
```

### Re-inicializar BD

```bash
php scripts/crear.php
```

---

**Motor:** InnoDB  
**Charset:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  
**Última actualización:** Noviembre 2025
