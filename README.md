<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# API de Gestión de Usuarios - Laravel

## Descripción
API RESTful para gestión de usuarios desarrollada en Laravel con autenticación JWT, operaciones CRUD y sistema de estadísticas.

## Equipo de Desarrollo
- Kevin Castro
- Mauricio Bustillo
- Luz Fuentes
- Alejandro Soto
- Alvin Merino

## Características
- ✅ Autenticación JWT con refresh token
- ✅ CRUD completo de usuarios
- ✅ Estadísticas por día, semana y mes
- ✅ Documentación con Postman
- ✅ API RESTful

## Tecnologías
- Laravel 10+
- Laravel Passport/Sanctum
- MySQL
- PHP 8.1+

## Estado del Proyecto
- ✅ **API Completada y Probada** - 15 tests pasando (100%)
- ✅ **Postman Collection** - Disponible en [postman_collection.json](postman_collection.json)
- ✅ **Documentación** - Completa en [INSTALLATION.md](INSTALLATION.md)

## Instalación
Ver [PROJECT_SETUP.md](PROJECT_SETUP.md) para detalles de instalación y configuración.

## Flujo de Trabajo
Cada miembro del equipo trabajará en una rama individual y luego hará merge a `main`. Ver [PROJECT_SETUP.md](PROJECT_SETUP.md) para más detalles.


## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Estadísticas de usuarios

Todos los endpoints de estadísticas requieren autenticación (`Bearer Token`).

### 1. GET /api/statistics/summary

Devuelve un resumen global de usuarios:

```json
{
  "success": true,
  "data": {
    "today": 3,
    "this_week": 10,
    "this_month": 25,
    "last_month": 18,
    "total": 100,
    "active": 90,
    "deleted": 10,
    "growth_vs_last_month": {
      "current": 25,
      "previous": 18,
      "percentage": 38.89,
      "direction": "up"
    }
  }
}
