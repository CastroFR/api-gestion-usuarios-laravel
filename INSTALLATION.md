# 🚀 Instalación - API Gestión de Usuarios Laravel

## 📋 Requisitos Previos

- PHP 8.1 o superior
- Composer 2.x
- MySQL 5.7+ (o MariaDB 10.2+)
- Git

---

## 🔧 Pasos de Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/api-gestion-usuarios-laravel.git
cd api-gestion-usuarios-laravel
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar variables de entorno

```bash
# Copiar el archivo de ejemplo
cp .env.example .env
```

### 4. Configurar base de datos

Edita el archivo `.env` con tus credenciales:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_gestion_usuarios
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Generar clave de aplicación

```bash
php artisan key:generate
```

### 6. Configurar autenticación (Sanctum)

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 7. Ejecutar migraciones

```bash
php artisan migrate
```

### 8. (Opcional) Crear datos de prueba

```bash
php artisan db:seed
```

---

## 🚦 Iniciar el Servidor

### Desarrollo

```bash
php artisan serve
```

La API estará disponible en: `http://localhost:8000`

### Verificar instalación

```bash
curl http://localhost:8000/api/health
```

---

## 📊 Endpoints Principales

### 🔐 Autenticación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/register` | Registrar usuario |
| POST | `/api/login` | Iniciar sesión |
| POST | `/api/logout` | Cerrar sesión |
| POST | `/api/refresh` | Refrescar token |
| GET | `/api/me` | Información del usuario actual |

### 👥 Usuarios (CRUD)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/users` | Listar usuarios |
| POST | `/api/users` | Crear usuario |
| GET | `/api/users/{id}` | Mostrar usuario |
| PUT | `/api/users/{id}` | Actualizar usuario |
| DELETE | `/api/users/{id}` | Eliminar usuario |
| POST | `/api/users/{id}/restore` | Restaurar usuario eliminado |
| DELETE | `/api/users/{id}/force` | Eliminar permanentemente |

### 📈 Estadísticas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/statistics/daily` | Registros por día |
| GET | `/api/statistics/weekly` | Registros por semana |
| GET | `/api/statistics/monthly` | Registros por mes |
| GET | `/api/statistics/summary` | Resumen general |

---

## 🧪 Testing

### Ejecutar todas las pruebas

```bash
php artisan test
```

### Ejecutar pruebas específicas

```bash
php artisan test --filter AuthTest
php artisan test --filter UserTest
```

### Ver cobertura de tests

```bash
php artisan test --coverage
```

---

## 🛠 Comandos Útiles

### Limpiar caché

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Optimizar aplicación

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Ver rutas disponibles

```bash
php artisan route:list
```

### Crear nuevo controlador

```bash
php artisan make:controller API/NuevoController
```

---

## 🔗 Configuración de Postman

1. Importa el archivo `postman_collection.json` en Postman
2. Configura las variables de entorno:
   - **base_url**: `http://localhost:8000`
   - **token**: (se autocompletará al hacer login)

---

## ❓ Solución de Problemas Comunes

### Error: "Class 'Laravel\Sanctum\SanctumServiceProvider' not found"

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### Error de conexión a MySQL

Verifica que:

- MySQL esté corriendo
- Las credenciales en `.env` sean correctas
- La base de datos exista

### Error: "Token expirado"

Los tokens expiran cada 5 minutos por seguridad. Usa el endpoint `/api/refresh` para obtener uno nuevo.

---

## 📁 Estructura del Proyecto

```
api-gestion-usuarios-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/API/
│   │   │   ├── AuthController.php
│   │   │   ├── UserController.php
│   │   │   └── StatisticsController.php
│   │   └── Middleware/
│   │       └── CheckTokenExpiration.php
│   └── Models/
│       └── User.php
├── database/
│   └── migrations/
│       └── create_users_table.php
├── routes/
│   └── api.php
├── tests/
│   └── Feature/
│       ├── AuthTest.php
│       └── UserTest.php
└── INSTALLATION.md (este archivo)
```

---

## 👥 Colaboración

### Flujo de trabajo Git

**Cada desarrollador crea su rama:**

```bash
git checkout -b feature/nombre-feature
```

**Trabajar en la rama y hacer commits:**

```bash
git add .
git commit -m "feat: descripción del cambio"
```

**Subir cambios y crear Pull Request:**

```bash
git push origin feature/nombre-feature
```

---

## 📞 Soporte

Para problemas o dudas:

- Revisar los logs: `storage/logs/laravel.log`
- Verificar migraciones: `php artisan migrate:status`
- Revisar configuración: `php artisan config:show`

---

## ✅ Instalación completada

Cuando puedas acceder a: `http://localhost:8000/api/health`
