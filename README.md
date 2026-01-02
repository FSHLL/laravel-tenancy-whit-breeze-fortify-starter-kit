# Laravel Multi-Tenancy Starter Kit

<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/stancl/tenancy"><img src="https://img.shields.io/packagist/v/stancl/tenancy" alt="Tenancy Version"></a>
<a href="https://packagist.org/packages/laravel/fortify"><img src="https://img.shields.io/packagist/v/laravel/fortify" alt="Fortify Version"></a>
<a href="https://packagist.org/packages/laravel/breeze"><img src="https://img.shields.io/packagist/v/laravel/breeze" alt="Breeze Version"></a>
<a href="https://packagist.org/packages/spatie/laravel-permission"><img src="https://img.shields.io/packagist/v/spatie/laravel-permission" alt="Laravel Permission Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About This Project

This starter kit is a fully configured Laravel application that combines multi-tenancy best practices with robust and secure authentication. Perfect for SaaS applications that require:

- 🏢 **Complete multi-tenancy** with Laravel Tenancy
- 🔐 **Advanced authentication** with Laravel Fortify
- 🛡️ **Roles & Permissions** with Spatie Laravel Permission
- 📱 **Two-factor authentication (2FA)** 
- 🎨 **Modern UI** with Laravel Breeze and Tailwind CSS
- 🔧 **Development tools** with Laravel Telescope and Debugbar

## Available Branches

This repository contains multiple branches with different feature sets. Choose the branch that best fits your project needs:

### 🌿 `main` (Base)
The base branch contains the core multi-tenancy setup with authentication:
- Laravel 12 with multi-tenancy (Stancl/Tenancy)
- Laravel Fortify authentication
- Two-factor authentication (2FA)
- Laravel Breeze UI
- Basic tenant isolation

### 🌿 `feature/tenant-and-user-management`
Builds on `main` by adding comprehensive management interfaces:
- ✅ **Tenant Management**: Complete CRUD interface for managing tenants from central app
- ✅ **User Management**: Manage users across all tenants from central app
- ✅ **Domain Management**: Associate multiple domains with each tenant
- ✅ **Central User Command**: CLI tool to create central application users
- ✅ **Secure Operations**: Password confirmation for destructive actions
- ✅ **Tenant Isolation**: Automatic data scoping per tenant

**Use this branch if you need:** Admin interfaces to manage tenants and users centrally without role-based access control.

### 🌿 `feature/tenant-user-management-and-permissions` (Recommended)
Builds on `feature/tenant-and-user-management` by adding a complete role and permission system:
- ✅ All features from `feature/tenant-and-user-management`
- ✅ **Spatie Laravel Permission**: Full RBAC implementation
- ✅ **Central Permissions**: Control tenant and user management operations
  - `CREATE_TENANT`, `VIEW_TENANT`, `UPDATE_TENANT`, `DELETE_TENANT`
  - `CREATE_TENANT_USER`, `VIEW_TENANT_USER`, `UPDATE_TENANT_USER`, `DELETE_TENANT_USER`
  - `CREATE_ROLE`, `VIEW_ROLE`, `UPDATE_ROLE`, `DELETE_ROLE`
- ✅ **Tenant-Scoped Permissions**: Per-tenant role and permission management
  - `VIEW_TENANT_USER_BY_TENANT`, `CREATE_TENANT_USER_BY_TENANT`, etc.
  - `VIEW_ROLE_BY_TENANT`, `CREATE_ROLE_BY_TENANT`, etc.
- ✅ **Role Management Interface**: CRUD operations for roles and permissions
- ✅ **Enum-Based Permissions**: Type-safe permission definitions
- ✅ **Middleware Protection**: Route-level permission enforcement
- ✅ **Automatic Seeding**: Permissions and roles auto-created per tenant
- ✅ **Super Admin Role**: Central role with all permissions

**Use this branch if you need:** Complete SaaS application with granular access control, multi-tenant role management, and secure permission-based operations.

### 📋 Branch Comparison

| Feature | main | tenant-and-user-management | tenant-user-management-and-permissions |
|---------|------|---------------------------|----------------------------------------|
| Multi-Tenancy | ✅ | ✅ | ✅ |
| Authentication (Fortify) | ✅ | ✅ | ✅ |
| Two-Factor Auth (2FA) | ✅ | ✅ | ✅ |
| Tenant Management UI | ❌ | ✅ | ✅ |
| User Management UI | ❌ | ✅ | ✅ |
| Role Management UI | ❌ | ❌ | ✅ |
| Permission System | ❌ | ❌ | ✅ |
| Central Permissions | ❌ | ❌ | ✅ |
| Tenant-Scoped Permissions | ❌ | ❌ | ✅ |
| Middleware Protection | ❌ | ❌ | ✅ |
| CLI User Creation | ❌ | ✅ | ✅ |

### 🔄 Switching Branches

```bash
# Switch to tenant and user management
git checkout feature/tenant-and-user-management

# Switch to full permissions system (recommended)
git checkout feature/tenant-user-management-and-permissions

# Return to base
git checkout main
```

After switching branches, remember to:
```bash
composer install
npm install
php artisan migrate:fresh
php artisan db:seed --class=CentralPermissionsSeeder  # Only for permissions branch
```

## Key Features

### 🏗️ Multi-Tenant Architecture
- Single database with tenant isolation using scopes
- Automatic identification by domain/subdomain
- Shared database with tenant-aware models
- Centralized tenant management
- Data isolation through global scopes and middleware

### 🔒 Authentication & Security
- **Laravel Fortify** for robust authentication
- **Two-factor authentication (2FA)** with QR codes
- Email verification
- Password recovery
- Brute force attack protection

### 🎨 User Interface
- **Laravel Breeze** for authentication views
- **Tailwind CSS** for modern styling
- Reusable components
- Responsive design

### 🛠️ Development Tools
- **Laravel Telescope** for debugging and monitoring
- **Laravel Debugbar** for development
- **Laravel Pint** for code formatting
- Ready configuration for Laravel Sail

## System Requirements

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- MySQL/PostgreSQL/SQLite
- PHP Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

## Installation

### 1. Clone the repository
```bash
git clone https://github.com/your-username/tenancy-fortify-app.git
cd tenancy-fortify-app
```

### 2. Install dependencies
```bash
composer install
npm install
```

### 3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure database
Edit the `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tenancy_app
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run migrations
```bash
php artisan migrate
```

### 6. Compile assets
```bash
npm run dev
# or for production
npm run build
```

### 7. Start the server
```bash
php artisan serve
```

## Multi-Tenancy Configuration

### Configure central domains
In `config/tenancy.php`, configure the domains that will host your central application:
```php
'central_domains' => [
    '127.0.0.1',
    'localhost',
    'your-main-domain.com',
],
```

### Single Database Implementation
This starter kit uses a **single database** approach for multi-tenancy with the following features:
- All tenant data is stored in the same database
- Tables include a `tenant_id` column for data isolation
- Global scopes automatically filter data by tenant
- Middleware ensures proper tenant context
- Models are automatically scoped to the current tenant

### Route structure
- **Central routes**: `routes/web.php`
- **Tenant routes**: `routes/tenant.php`
- **Shared routes**: `routes/shared.php`

## 2FA Configuration

### Enable 2FA for a user
1. User must have a verified email
2. Access the profile section
3. Enable two-factor authentication
4. Scan the QR code with an app like Google Authenticator
5. Confirm with a verification code

### Recovery codes
- 8 recovery codes are automatically generated
- Each code can only be used once
- Can be regenerated at any time

## Useful Commands

### Tenancy
```bash
# Run command for all tenants (single database)
php artisan tenants:run "cache:clear"

# List all tenants
php artisan tenants:list
```

### Fortify
```bash
# Publish Fortify views
php artisan vendor:publish --tag=fortify-views

# Publish Fortify configuration
php artisan vendor:publish --tag=fortify-config
```

### Development
```bash
# Format code
vendor/bin/pint

# Clear cache
php artisan optimize:clear

# Run tests
php artisan test
```

## Project Structure

```
├── app/
│   ├── Models/
│   │   ├── Tenant.php          # Tenant model
│   │   └── User.php            # User model with 2FA and tenant scopes
│   ├── Providers/
│   │   ├── FortifyServiceProvider.php
│   │   └── TenancyServiceProvider.php
│   ├── Scopes/                 # Global scopes for tenant isolation
│   └── Actions/Fortify/        # Custom Fortify actions
├── config/
│   ├── tenancy.php            # Tenancy configuration (single database)
│   └── fortify.php            # Fortify configuration
├── database/
│   └── migrations/            # Single database migrations with tenant_id columns
├── routes/
│   ├── web.php               # Central routes
│   ├── shared.php            # Shared routes between tenant and web centrar app
│   └── tenant.php            # Tenant-specific routes
└── resources/views/
    ├── auth/                 # Authentication views
    └── profile/              # Profile views with 2FA
```

## Development and Contributing

### Development environment setup
```bash
# Using Laravel Sail
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

### Code standards
- PSR-12 for PHP
- Prettier for JavaScript/CSS
- Run `vendor/bin/pint` before commits

## Additional Documentation

- [Laravel Tenancy Documentation](https://tenancyforlaravel.com/docs)
- [Laravel Fortify Documentation](https://laravel.com/docs/fortify)
- [Laravel Breeze Documentation](https://laravel.com/docs/starter-kits#laravel-breeze)
- [Laravel Documentation](https://laravel.com/docs)

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Support

If you encounter any issues or have questions, please:

1. Check the documentation
2. Search existing issues
3. Create a new issue with problem details

## Credits

- [Laravel Framework](https://laravel.com)
- [Laravel Tenancy](https://tenancyforlaravel.com)
- [Laravel Fortify](https://github.com/laravel/fortify)
- [Laravel Breeze](https://github.com/laravel/breeze)
- [Programming Fields - Laravel 11 Fortify Auth](https://www.youtube.com/watch?v=FpJkr5cS_7k&list=PLei32-mZRyeX1bQokcEOOvb1XE0VZbp6s)
