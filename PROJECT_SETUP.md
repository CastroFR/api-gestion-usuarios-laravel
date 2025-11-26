# Configuración del Proyecto

## Requisitos
- PHP 8.1+
- Composer
- MySQL 5.7+

## Instalación
1. Clonar repositorio
2. `composer install`
3. Copiar `.env.example` a `.env`
4. Configurar base de datos en `.env`
5. `php artisan key:generate`
6. `php artisan migrate`
7. `php artisan serve`

## Flujo de Trabajo
1. Cada persona crea su rama desde `main`: `git checkout -b mi-rama`
2. Desarrollar en la rama
3. Hacer commit de los cambios regularmente
4. Hacer push de la rama: `git push origin mi-rama`
5. Crear Pull Request para revisión (opcional)
6. Merge a `main`

## Al crear su rama, cada uno ejecuta esto:
git clone https://github.com/tu-usuario/api-gestion-usuarios-laravel.git
cd api-gestion-usuarios-laravel

## Crean su rama personal/feature
git checkout -b persona1/autenticacion
## O pueden usar: feature/autenticacion-juan

## Configuración de Base de Datos

1. Crear base de datos MySQL local:
```sql
CREATE DATABASE api_gestion_usuarios;