# SubMate API - Endpoints Documentation

**Documentación completa de la API de SubMate para desarrolladores Frontend.**

## Base URL

```
http://localhost/submate-backend/public/index.php
```

**Producción:**

```
https://api.submate.com
```

---

## Formato de Respuesta Estándar

**TODAS** las respuestas de la API siguen este formato estandarizado. El payload útil siempre vendrá dentro de `data`.

```json
{
  "status": 200,
  "success": true,
  "message": "Operación exitosa",
  "data": { ... }
}
```

**Campos:**

- `status`: Código HTTP numérico (200, 201, 400, 401, 404, 500, etc.)
- `success`: Booleano indicando éxito (`true`) o error (`false`)
- `message`: Mensaje descriptivo de la operación (opcional en respuestas exitosas, obligatorio en errores)
- `data`: Objeto o Array con la información solicitada (opcional).

---

## Autenticación

La mayoría de los endpoints requieren autenticación mediante **JWT (JSON Web Token)**.

### Header de Autorización

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

---

## Módulo: Auth

### POST `/api/auth/register`

**Registra un nuevo usuario** y envía código OTP por email para verificación.

**Request Body:**

```json
{
  "nombre": "Juan",
  "apellido": "Pérez",
  "email": "juan@example.com",
  "clave": "MiPassword123!"
}
```

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Código de verificación enviado al correo.",
  "data": null
}
```

---

### POST `/api/auth/register-verify`

**Verifica el código OTP** y crea la cuenta de usuario.

**Request Body:**

```json
{
  "email": "juan@example.com",
  "otp": "123456"
}
```

**Response:** `201 Created`

```json
{
  "status": 201,
  "success": true,
  "message": "Cuenta verificada y creada exitosamente.",
  "data": {
    "id": 15
  }
}
```

---

### POST `/api/auth/login`

Inicia sesión y devuelve el token JWT.

**Request Body:**

```json
{
  "email": "juan@example.com",
  "clave": "MiPassword123!"
}
```

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Login exitoso.",
  "data": {
    "usuario": {
      "id": 15,
      "nombre": "Juan",
      "apellido": "Pérez",
      "email": "juan@example.com",
      "rol": "user",
      "estado": "activo",
      "fecha_registro": "2025-11-01 10:30:00"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

---

### POST `/api/auth/logout`

Cierra la sesión del usuario.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Sesión cerrada correctamente.",
  "data": null
}
```

---

### GET `/api/auth/me`

Obtiene información del usuario autenticado.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Usuario autenticado",
  "data": {
    "usuario": {
      "id": 15,
      "nombre": "Juan",
      "apellido": "Pérez",
      "email": "juan@example.com",
      "rol": "user",
      "estado": "activo"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

---

### GET `/api/auth/email-available`

Verifica si un correo electrónico está disponible.

**Query Parameters:** `?email=juan@example.com`

**Response (Disponible):** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Correo disponible",
  "data": {
    "available": true
  }
}
```

**Response (No Disponible):** `409 Conflict`

```json
{
  "status": 409,
  "success": false,
  "message": "Este correo ya está en uso.",
  "data": {
    "available": false
  }
}
```

---

### POST `/api/auth/password-reset`

Solicita reset de contraseña.

**Request Body:**

```json
{
  "email": "juan@example.com"
}
```

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Si el correo existe, se ha enviado un código de recuperación.",
  "data": null
}
```

---

### POST `/api/auth/password-reset-verify`

Verifica OTP y cambia contraseña.

**Request Body:**

```json
{
  "email": "juan@example.com",
  "otp": "654321",
  "clave": "NuevaPassword456!"
}
```

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Contraseña actualizada correctamente.",
  "data": null
}
```

---

## Módulo: Usuario (Perfil)

### PUT `/api/perfil`

Actualiza el perfil.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**

```json
{
  "nombre": "Juan Carlos",
  "apellido": "Pérez García"
}
```

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Perfil actualizado correctamente.",
  "data": null
}
```

---

### PATCH `/api/perfil/password`

Cambia la contraseña del usuario autenticado.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**

```json
{
  "clave_actual": "MiPassword123!",
  "clave_nueva": "NuevaPassword456!"
}
```

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Contraseña actualizada correctamente.",
  "data": null
}
```

**Error Response:** `401 Unauthorized` (contraseña actual incorrecta)

```json
{
  "status": 401,
  "success": false,
  "message": "La contraseña actual es incorrecta.",
  "data": null
}
```

---

### DELETE `/api/perfil`

Elimina la cuenta.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Cuenta eliminada correctamente.",
  "data": null
}
```

---

## Módulo: Suscripciones

### GET `/api/suscripciones`

Lista todas las suscripciones.

**Headers:** `Authorization: Bearer {token}`

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Suscripciones obtenidas",
  "data": {
    "suscripciones": [
      {
        "id": 1,
        "nombre_servicio": "Netflix",
        "costo": 7.99,
        "estado": "activa",
        "frecuencia": "mensual",
        "metodo_pago": "Visa",
        "dia_cobro": 15,
        "mes_cobro": null,
        "fecha_ultimo_pago": "2025-11-15",
        "fecha_proximo_pago": "2025-12-15",
        "dias_restantes": 15
      }
    ]
  }
}
```

> 💡 **Nota de Implementación:**
>
> - `dias_restantes`: Entero positivo (días faltantes) o negativo (días de retraso).
> - **Lógica de Colores (Frontend):**
>   - **Verde:** > 7 días
>   - **Amarillo:** 3 - 7 días
>   - **Rojo:** < 3 días

---

### POST `/api/suscripciones`

Crea una nueva suscripción.

**Request Body:**

```json
{
  "nombre_servicio": "Amazon Prime",
  "costo": 14.99,
  "frecuencia": "anual",
  "metodo_pago": "Visa",
  "dia_cobro": 20,
  "mes_cobro": 3
}
```

**Response:** `201 Created`

```json
{
  "status": 201,
  "success": true,
  "message": "Suscripción creada exitosamente.",
  "data": {
    "id": 3
  }
}
```

---

### GET `/api/suscripciones/{id}`

Obtiene detalle de una suscripción.

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Detalle obtenido",
  "data": {
    "suscripcion": {
      "id": 1,
      "nombre_servicio": "Netflix",
      "costo": 7.99,
      "estado": "activa",
      "frecuencia": "mensual"
    }
  }
}
```

---

### PUT `/api/suscripciones/{id}`

Actualiza una suscripción.

**Request Body:**

```json
{
  "nombre_servicio": "Netflix Premium",
  "costo": 15.99
}
```

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Suscripción actualizada correctamente.",
  "data": null
}
```

---

### DELETE `/api/suscripciones/{id}`

Elimina una suscripción.

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Suscripción eliminada correctamente.",
  "data": null
}
```

---

## Módulo: Suscripciones - Operaciones

### PATCH `/api/suscripciones/{id}/estado`

Cambia el estado (Activar/Desactivar).

**Request Body Parameters:**

| Parámetro    | Tipo   | Requerido | Descripción                                               |
| ------------ | ------ | --------- | --------------------------------------------------------- |
| `estado`     | string | Sí        | Nuevo estado de la suscripción (`activa` o `inactiva`).   |
| `frecuencia` | string | No        | `mensual` o `anual`. Si se omite, mantiene la anterior.   |
| `costo`      | float  | No        | Nuevo costo. **Solo se procesa si la frecuencia cambia**. |

### Ejemplo de Solicitud (Desactivar)

```json
{
  "estado": "inactiva"
}
```

### Ejemplo de Solicitud (Reactivación con cambio de plan)

```json
{
  "estado": "activa",
  "frecuencia": "anual",
  "costo": 99.99
}
```

> 💡 **Nota**: El campo `frecuencia` es opcional. Si se envía al reactivar, se recalcularán las fechas base a esa nueva frecuencia.

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Estado actualizado correctamente.",
  "data": {
    "id": 1,
    "nombre_servicio": "Netflix",
    "costo": 99.99,
    "estado": "activa",
    "frecuencia": "anual",
    "fecha_proximo_pago": "2026-12-02"
  }
}
```

---

### POST `/api/suscripciones/{id}/simular-pago`

Simula un pago manual (Rol Beta/Admin).

**Request Body:**

```json
{
  "metodo_pago": "Visa"
}
```

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Pago simulado correctamente.",
  "data": null
}
```

> 💡 **Nota de Implementación:**
>
> - Esta función solo está disponible para usuarios con rol `beta` o `admin`.
> - Útil para probar la generación de historial y gráficas sin esperar fechas reales.

---

## Módulo: Dashboard

### GET `/api/dashboard`

Obtiene analytics completos para el dashboard principal.

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Datos del dashboard",
  "data": {
    "resumen": {
      "total_activas": 7,
      "gasto_mes_actual": 89.95,
      "gasto_mensual_estimado": 65.45,
      "proyeccion_anual": 785.4,
      "mayor_gasto": {
        "nombre": "Google One",
        "costo": 29.99
      },
      "proximo_vencimiento": {
        "id": 5,
        "nombre_servicio": "Spotify",
        "fecha": "2024-12-05",
        "monto": 11.49
      }
    },
    "grafica_mensual": {
      "labels": [
        "Jul 2024",
        "Ago 2024",
        "Sep 2024",
        "Oct 2024",
        "Nov 2024",
        "Dic 2024"
      ],
      "data": [45.0, 48.5, 50.0, 55.0, 60.0, 75.48]
    },
    "distribucion": {
      "labels": ["Mensual", "Anual"],
      "data": [5, 2]
    },
    "distribucion_metodos": {
      "labels": ["Visa", "PayPal"],
      "data": [450.0, 320.5]
    },
    "top_3_costosas": [
      {
        "nombre": "Google One",
        "costo": 29.99
      },
      {
        "nombre": "Disney+",
        "costo": 16.99
      },
      {
        "nombre": "Spotify",
        "costo": 11.49
      }
    ]
  }
}
```

> 💡 **Notas de Implementación:**
>
> - **Smart Start (Gráfica Mensual):** Los arrays `labels` y `data` pueden tener longitud variable. El sistema recorta automáticamente los meses vacíos al inicio del año, comenzando desde el primer mes con actividad (gasto > 0) o mostrando los últimos 3 meses si no hay actividad reciente.
> - **Lógica Híbrida:** El gasto del mes actual combina historial real + proyecciones de pagos pendientes.

---

## Módulo: Home

### GET `/api/home`

Obtiene datos resumidos para la vista de inicio (Home), enfocada en urgencia financiera y distribución semanal.

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "data": {
    "semaforo": {
      "gasto_7_dias": 45.0,
      "proximo_gran_cargo": {
        "nombre": "Adobe Creative Cloud",
        "monto": 59.99,
        "fecha": "2024-12-15"
      },
      "total_suscripciones": 8
    },
    "proximos_vencimientos": [
      {
        "id_suscripcion_ahjr": 12,
        "nombre_servicio_ahjr": "Netflix",
        "costo_ahjr": 15.99,
        "fecha_proximo_pago_ahjr": "2024-12-03",
        "dias_restantes": 1
      },
      {
        "id_suscripcion_ahjr": 5,
        "nombre_servicio_ahjr": "Spotify",
        "costo_ahjr": 11.49,
        "fecha_proximo_pago_ahjr": "2024-12-05",
        "dias_restantes": 3
      }
    ],
    "gasto_semanal": {
      "labels": ["Semana 1", "Semana 2", "Semana 3", "Semana 4"],
      "data": [10.0, 0.0, 45.5, 12.0]
    }
  }
}
```

> 💡 **Notas de Implementación:**
>
> - **Semáforo:** Indicadores rápidos de salud financiera. `gasto_7_dias` es la suma de pagos programados para la próxima semana.
> - **Gasto Semanal:** Distribución del gasto proyectado para el mes actual, dividido en 4 semanas.

---

## Módulo: Contacto

### POST `/api/contacto`

Envía un mensaje de contacto y confirmación por correo. **Público** (No requiere Auth).

**Request Body:**

```json
{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "telefono": "+52 55 1234 5678",
  "asunto": "consulta",
  "mensaje": "Hola, tengo una duda sobre..."
}
```

> **Valores permitidos para `asunto`:** `consulta`, `propuesta`, `soporte`.

**Response:** `200 OK`

```json
{
  "status": 200,
  "success": true,
  "message": "Mensaje enviado con éxito. Te responderemos pronto.",
  "data": true
}
```
