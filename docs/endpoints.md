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

## Autenticación

La mayoría de los endpoints requieren autenticación mediante **JWT (JSON Web Token)**.

### Header de Autorización

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

**Flujo de autenticación:**

1. Usuario hace login → Recibe `token` en la respuesta
2. Guardar token en `localStorage` o state management
3. Incluir token en todas las peticiones protegidas

**Ejemplo con Axios:**

```javascript
const api = axios.create({
  baseURL: "http://localhost/submate-backend/public/index.php",
  headers: { "Content-Type": "application/json" },
});

// Interceptor para agregar token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
```

---

## Módulo: Auth

### POST `/api/auth/register`

Registra un nuevo usuario en el sistema.

**Request Body:**

```json
{
  "nombre": "Juan",
  "apellido": "Pérez",
  "email": "juan@example.com",
  "clave": "MiPassword123!"
}
```

**Response:** `201 Created`

```json
{
  "message": "Usuario registrado exitosamente.",
  "id": 15
}
```

**Errores comunes:**

- `400` - Campos incompletos o email inválido
- `409` - Email ya registrado

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
  "message": "Login exitoso.",
  "usuario": {
    "id": 15,
    "nombre": "Juan",
    "apellido": "Pérez",
    "email": "juan@example.com",
    "rol": "user", // IMPORTANTE: 'admin', 'beta', o 'user'
    "estado": "activo"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

> **💡 Tip para Frontend:**  
> El campo `usuario.rol` es crítico para renderizar condicionalmente funcionalidades:
>
> - `admin` → Acceso total
> - `beta` → Puede usar "Simular Pago"
> - `user` → Funcionalidades estándar

**Ejemplo de lógica de UI:**

```javascript
const canSimulatePayment = user.rol === "beta" || user.rol === "admin";

{
  canSimulatePayment && (
    <button onClick={handleSimulatePayment}>Simular Pago 💳</button>
  );
}
```

**Errores comunes:**

- `400` - Email o contraseña vacíos
- `401` - Credenciales incorrectas

---

### GET `/api/auth/me`

Obtiene información del usuario autenticado desde el token.

**Headers:**

```http
Authorization: Bearer {token}
```

**Response:** `200 OK`

```json
{
  "usuario": {
    "id": 15,
    "email": "juan@example.com",
    "rol": "user"
  }
}
```

**Uso típico:**  
Validar sesión activa al cargar la app o verificar rol.

---

## Módulo: Usuario (Perfil)

### PUT `/api/perfil`

Actualiza el perfil del usuario autenticado.

**Headers:**

```http
Authorization: Bearer {token}
```

**Request Body:** (todos los campos son opcionales)

```json
{
  "nombre": "Juan Carlos",
  "apellido": "Pérez García",
  "email": "juancarlos@example.com"
}
```

**Response:** `200 OK`

```json
{
  "message": "Perfil actualizado correctamente."
}
```

**Errores:**

- `400` - Email inválido
- `401` - Token inválido o expirado

---

### DELETE `/api/perfil`

Elimina la cuenta del usuario autenticado.

**Headers:**

```http
Authorization: Bearer {token}
```

**Response:** `200 OK`

```json
{
  "message": "Cuenta eliminada correctamente."
}
```

> **⚠️ Advertencia:**  
> Esta acción es irreversible. Elimina el usuario y todas sus suscripciones (CASCADE).

---

## Módulo: Suscripciones (CRUD)

> **📋 Nota importante:**  
> Los JSON de entrada/salida usan nombres **limpios** (sin sufijo `_ahjr`).  
> Ejemplo: `nombre_servicio`, no `nombre_servicio_ahjr`.

### GET `/api/suscripciones`

Lista todas las suscripciones del usuario autenticado.

**Headers:**

```http
Authorization: Bearer {token}
```

**Response:** `200 OK`

```json
{
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
      "fecha_creacion": "2025-01-10 14:30:00"
    },
    {
      "id": 2,
      "nombre_servicio": "Spotify",
      "costo": 11.49,
      "estado": "activa",
      "frecuencia": "mensual",
      "metodo_pago": "PayPal",
      "dia_cobro": 5,
      "mes_cobro": null,
      "fecha_ultimo_pago": "2025-11-05",
      "fecha_creacion": "2025-02-20 10:15:00"
    }
  ]
}
```

**Campos clave:**

- `frecuencia`: `"mensual"` o `"anual"`
- `estado`: `"activa"` o `"inactiva"`
- `metodo_pago`: `"MasterCard"`, `"Visa"`, `"GPay"`, o `"PayPal"`
- `dia_cobro`: Día del mes (1-31)
- `mes_cobro`: Solo para anuales, mes del año (1-12)

---

### POST `/api/suscripciones`

Crea una nueva suscripción.

**Headers:**

```http
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "nombre_servicio": "Amazon Prime",
  "costo": 14.99,
  "frecuencia": "anual",
  "metodo_pago": "Visa",
  "dia_cobro": 20,
  "mes_cobro": 3 // Marzo (solo para anuales)
}
```

**Response:** `201 Created`

```json
{
  "message": "Suscripción creada exitosamente.",
  "id": 3
}
```

> **💡 Tip:**  
> El campo `fecha_ultimo_pago` se calcula automáticamente según:
>
> - **Mensual**: Si hoy es >= día_cobro → este mes, sino → mes anterior
> - **Anual**: Si ya pasó la fecha este año → este año, sino → año anterior

**Errores:**

- `400` - Campos requeridos faltantes
- `401` - No autenticado

---

### GET `/api/suscripciones/{id}`

Obtiene el detalle de una suscripción específica.

**Headers:**

```http
Authorization: Bearer {token}
```

**Response:** `200 OK`

```json
{
  "suscripcion": {
    "id": 1,
    "nombre_servicio": "Netflix",
    "costo": 7.99,
    "estado": "activa",
    "frecuencia": "mensual",
    "metodo_pago": "Visa",
    "dia_cobro": 15,
    "mes_cobro": null,
    "fecha_ultimo_pago": "2025-11-15",
    "fecha_creacion": "2025-01-10 14:30:00"
  }
}
```

**Errores:**

- `404` - Suscripción no encontrada o no pertenece al usuario

---

### PUT `/api/suscripciones/{id}`

Actualiza una suscripción existente.

**Headers:**

```http
Authorization: Bearer {token}
```

**Request Body:** (todos los campos son opcionales)

```json
{
  "nombre_servicio": "Netflix Premium",
  "costo": 15.99,
  "metodo_pago": "MasterCard",
  "dia_cobro": 20
}
```

**Response:** `200 OK`

```json
{
  "message": "Suscripción actualizada correctamente."
}
```

> **⚠️ Nota:**  
> No se puede cambiar `frecuencia`. Para cambiar de mensual a anual (o viceversa), elimina y crea una nueva.

---

### DELETE `/api/suscripciones/{id}`

Elimina una suscripción.

**Headers:**

```http
Authorization: Bearer {token}
```

**Response:** `200 OK`

```json
{
  "message": "Suscripción eliminada correctamente."
}
```

> **💡 Tip:**  
> Esto también elimina todo el historial de pagos asociado (CASCADE).

---

## Módulo: Suscripciones - Operaciones Especiales

### PATCH `/api/suscripciones/{id}/estado`

Cambia el estado de una suscripción entre activa/inactiva.

**Headers:**

```http
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "estado": "inactiva" // o "activa"
}
```

**Response:** `200 OK`

```json
{
  "message": "Estado actualizado correctamente.",
  "nuevo_estado": "inactiva"
}
```

**Uso típico:**  
Permitir al usuario pausar suscripciones temporalmente sin eliminarlas.

```javascript
// Toggle estado
const toggleEstado = async (id, estadoActual) => {
  const nuevoEstado = estadoActual === "activa" ? "inactiva" : "activa";
  await api.patch(`/api/suscripciones/${id}/estado`, { estado: nuevoEstado });
};
```

---

### POST `/api/suscripciones/{id}/simular-pago`

**¡FUNCIÓN BETA!** Simula un pago Manual de la suscripción.

**Restricción de acceso:**  
Solo disponible para usuarios con rol `beta` o `admin`.

**Headers:**

```http
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "fecha": "2025-12-15" // Opcional, usa hoy si no se especifica
}
```

**Response:** `200 OK`

```json
{
  "message": "Pago simulado correctamente.",
  "monto": 7.99,
  "fecha": "2025-12-15",
  "nueva_fecha_ultimo_pago": "2025-12-15"
}
```

**Qué hace este endpoint:**

1. Crea un registro en `td_historial_pagos_ahjr`
2. Actualiza `fecha_ultimo_pago` de la suscripción
3. Calcula la próxima fecha de cobro automáticamente

> **💡 Ejemplo de UI:**
>
> ```javascript
> {
>   user.rol === "beta" && (
>     <Tooltip title="Simular pago manual (solo beta)">
>       <button onClick={() => simulatePayment(suscripcion.id)}>
>         💳 Registrar Pago
>       </button>
>     </Tooltip>
>   );
> }
> ```

**Errores:**

- `403` - Usuario no es beta/admin
- `404` - Suscripción no encontrada

---

## Módulo: Dashboard (Analytics)

### GET `/api/dashboard`

Obtiene datos analíticos consolidados para gráficas.

**Headers:**

```http
Authorization: Bearer {token}
```

**Response:** `200 OK`

```json
{
  "resumen": {
    "total_activas": 5,
    "gasto_mes_actual": 89.95,
    "proximo_vencimiento": {
      "id": 2,
      "nombre_servicio": "Spotify",
      "fecha": "2025-12-05",
      "monto": 11.49
    }
  },
  "grafica_mensual": {
    "labels": [
      "Jun 2025",
      "Jul 2025",
      "Ago 2025",
      "Sep 2025",
      "Oct 2025",
      "Nov 2025"
    ],
    "data": [75.48, 82.45, 89.95, 89.95, 95.42, 89.95]
  },
  "distribucion_metodos": {
    "labels": ["Visa", "PayPal", "MasterCard"],
    "data": [450.0, 320.5, 180.0]
  }
}
```

### Estructura Detallada

**`resumen`**: KPIs generales

- `total_activas`: Cantidad de suscripciones activas
- `gasto_mes_actual`: Total gastado este mes (historial + proyección)
- `proximo_vencimiento`: Próxima suscripción a renovarse

**`grafica_mensual`**: Datos para gráfica de tendencia

- `labels`: Meses en español (últimos 6 meses)
- `data`: Gasto total por mes

**`distribucion_metodos`**: Datos para gráfica de torta/dona

- `labels`: Nombres de métodos de pago
- `data`: Total gastado con cada método

---

### Integración con Chart.js

> **🎨 Listo para usar:**  
> Los datos vienen en formato compatible con Chart.js sin transformación adicional.

**Ejemplo - Gráfica de línea:**

```javascript
import { Line } from "react-chartjs-2";

const MonthlyChart = ({ dashboardData }) => {
  const data = {
    labels: dashboardData.grafica_mensual.labels,
    datasets: [
      {
        label: "Gasto Mensual",
        data: dashboardData.grafica_mensual.data,
        borderColor: "rgb(75, 192, 192)",
        tension: 0.1,
      },
    ],
  };

  return <Line data={data} />;
};
```

**Ejemplo - Gráfica de torta:**

```javascript
import { Doughnut } from "react-chartjs-2";

const PaymentMethodChart = ({ dashboardData }) => {
  const data = {
    labels: dashboardData.distribucion_metodos.labels,
    datasets: [
      {
        data: dashboardData.distribucion_metodos.data,
        backgroundColor: [
          "rgba(255, 99, 132, 0.8)",
          "rgba(54, 162, 235, 0.8)",
          "rgba(255, 206, 86, 0.8)",
        ],
      },
    ],
  };

  return <Doughnut data={data} />;
};
```

---

### Integración con Recharts

**Gráfica de área:**

```javascript
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
} from "recharts";

const MonthlyChart = ({ dashboardData }) => {
  const data = dashboardData.grafica_mensual.labels.map((label, index) => ({
    mes: label,
    gasto: dashboardData.grafica_mensual.data[index],
  }));

  return (
    <AreaChart data={data}>
      <CartesianGrid strokeDasharray="3 3" />
      <XAxis dataKey="mes" />
      <YAxis />
      <Tooltip />
      <Area type="monotone" dataKey="gasto" fill="#8884d8" />
    </AreaChart>
  );
};
```

---

## Códigos de Estado HTTP

| Código | Significado  | Cuándo ocurre                              |
| ------ | ------------ | ------------------------------------------ |
| `200`  | OK           | Operación exitosa                          |
| `201`  | Created      | Recurso creado exitosamente                |
| `400`  | Bad Request  | Datos inválidos o incompletos              |
| `401`  | Unauthorized | Token inválido/expirado o no enviado       |
| `403`  | Forbidden    | Usuario no tiene permisos (ej: no es beta) |
| `404`  | Not Found    | Recurso no encontrado                      |
| `500`  | Server Error | Error interno del servidor                 |

---

## Manejo de Errores

Todas las respuestas de error siguen este formato:

```json
{
  "error": "Mensaje descriptivo del error"
}
```

**Ejemplo de manejo con Axios:**

```javascript
try {
  const response = await api.post("/api/suscripciones", data);
  // Éxito
} catch (error) {
  if (error.response) {
    // Error del servidor (4xx, 5xx)
    const message = error.response.data.error;
    toast.error(message);
  } else {
    // Error de red
    toast.error("Error de conexión");
  }
}
```

---

## Endpoints Legacy (Compatibilidad)

Estos endpoints existen por compatibilidad pero están deprecados:

- `POST /auth/login` → Usar `/api/auth/login`
- `GET /auth/session` → Usar `/api/auth/me`

---

## Notas Finales para Frontend

### Gestión de Estado Recomendada

```javascript
// Context de autenticación
const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem("token"));

  const login = async (email, password) => {
    const response = await api.post("/api/auth/login", {
      email,
      clave: password,
    });
    setToken(response.data.token);
    setUser(response.data.usuario);
    localStorage.setItem("token", response.data.token);
  };

  const logout = () => {
    setToken(null);
    setUser(null);
    localStorage.removeItem("token");
  };

  return (
    <AuthContext.Provider value={{ user, token, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};
```

### Refresh de Datos

El dashboard puede tardar en cargar por las queries analíticas. Recomendaciones:

1. **Mostrar skeleton/loading** mientras carga
2. **Cache temporal** (5-10 minutos)
3. **Refresh manual** con botón

```javascript
const [dashboard, setDashboard] = useState(null);
const [loading, setLoading] = useState(true);

const fetchDashboard = async () => {
  setLoading(true);
  const data = await api.get("/api/dashboard");
  setDashboard(data.data);
  setLoading(false);
};

useEffect(() => {
  fetchDashboard();
}, []);
```

---

**Versión de la API:** 2.0  
**Última actualización:** Noviembre 2025
