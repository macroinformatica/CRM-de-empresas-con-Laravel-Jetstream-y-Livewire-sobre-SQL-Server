# CRM Empresas

CRM de empresas construido con **Laravel, Jetstream y Livewire** sobre **SQL Server**.

## Características
- Importación masiva desde Excel/CSV con staging y control de lotes
- Normalización de teléfonos, correos, sitios web y direcciones
- Detección y fusión de duplicados
- Segmentos y exportación
- Búsqueda inteligente por nombre, correo, teléfono o dominio

## Requisitos
- PHP 8.2+
- Composer y Node.js
- SQL Server 2017+ (extensiones `sqlsrv` y `pdo_sqlsrv` de PHP)

## Instalación
```bash
git clone https://github.com/macroinformatica/CRM-de-empresas-con-Laravel-Jetstream-y-Livewire-sobre-SQL-Server.git
cd CRM-de-empresas-con-Laravel-Jetstream-y-Livewire-sobre-SQL-Server
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Configura la conexión a SQL Server en `.env`, ejecuta `php artisan migrate`
y luego el script `database/sql/crm_install.sql` en tu base de datos.

## Uso
```bash
php artisan serve
```
Abre http://127.0.0.1:8000
