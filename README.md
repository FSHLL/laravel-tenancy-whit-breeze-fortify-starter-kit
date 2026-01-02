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

### 🌿 [`main` (Base)](https://github.com/FSHLL/laravel-tenancy-whit-breeze-fortify-starter-kit)
The base branch contains the core multi-tenancy setup with authentication:
- Laravel 12 with multi-tenancy (Stancl/Tenancy)
- Laravel Fortify authentication
- Two-factor authentication (2FA)
- Laravel Breeze UI
- Basic tenant isolation

### 🌿 [`feature/tenant-and-user-management`](https://github.com/FSHLL/laravel-tenancy-whit-breeze-fortify-starter-kit/tree/feature/tenant-and-user-management)
Builds on `main` by adding comprehensive management interfaces:
- ✅ **Tenant Management**: Complete CRUD interface for managing tenants from central app
- ✅ **User Management**: Manage users across all tenants from central app
- ✅ **Domain Management**: Associate multiple domains with each tenant
- ✅ **Central User Command**: CLI tool to create central application users
- ✅ **Secure Operations**: Password confirmation for destructive actions
- ✅ **Tenant Isolation**: Automatic data scoping per tenant

**Use this branch if you need:** Admin interfaces to manage tenants and users centrally without role-based access control.

### 🌿 [`feature/tenant-user-management-and-permissions` (Recommended)](https://github.com/FSHLL/laravel-tenancy-whit-breeze-fortify-starter-kit/tree/feature/tenant-user-management-and-permissions)
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
- **Complete tenant management interface** with CRUD operations
- **Advanced user management per tenant** with role-based access
- Data isolation through global scopes and middleware

### 🔒 Authentication & Security
- **Laravel Fortify** for robust authentication
- **Two-factor authentication (2FA)** with QR codes
- Email verification
- Password recovery
- **Central user management** with dedicated commands
- **Tenant-specific user isolation** and management
- Brute force attack protection

### 🛡️ Roles & Permissions System
- **Spatie Laravel Permission** for role-based access control (RBAC)
- **Central Permissions**: Manage tenant and user operations from central app
- **Tenant Permissions**: Scoped permissions per tenant for granular control
- **Enum-based Permissions**: Type-safe permission definitions
- **Automatic Permission Seeding**: Auto-create permissions and roles per tenant
- **Middleware Protection**: Route-level permission enforcement
- **Flexible Role Assignment**: Assign multiple roles and permissions to users

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

You have multiple options to install this starter kit:

### Option 1: Using Laravel Installer (Recommended) ⭐

```bash
# Install using Laravel installer with the branch you need
laravel new my-saas-app --using=FSHLL/laravel-tenancy-whit-breeze-fortify-starter-kit

# Or specify a specific branch
laravel new my-saas-app --using=FSHLL/laravel-tenancy-whit-breeze-fortify-starter-kit:feature/tenant-user-management-and-permissions
```

### Option 2: Using Composer Create-Project

```bash
# Create project from main branch
composer create-project FSHLL/laravel-tenancy-whit-breeze-fortify-starter-kit my-saas-app

# Or from a specific branch
composer create-project FSHLL/laravel-tenancy-whit-breeze-fortify-starter-kit:dev-feature/tenant-user-management-and-permissions my-saas-app
```

### Option 3: Clone from GitHub

```bash
# Clone the repository
git clone https://github.com/FSHLL/laravel-tenancy-whit-breeze-fortify-starter-kit.git my-saas-app
cd my-saas-app

# Switch to desired branch (optional)
git checkout feature/tenant-user-management-and-permissions

# Install dependencies
composer install
npm install
```

---

After installation using any of the options above, continue with the following steps:

### 1. Configure environment
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

### 3. Run migrations
```bash
php artisan migrate
```

### 4. Seed permissions (only for permissions branch)
```bash
# Only if using feature/tenant-user-management-and-permissions branch
php artisan db:seed --class=CentralPermissionsSeeder

# Create your first super admin user
php artisan app:create-central-user

# Note: Tenant permissions are automatically created when a tenant is created
```

### 5. Compile assets
```bash
npm run dev
# or for production
npm run build
```

### 6. Start the server
```bash
php artisan serve
```

Your application will be available at `http://localhost:8000`

## Management Interfaces

### 🏢 Tenant Management
This starter kit includes a complete tenant management interface accessible from the central application:

- **Tenant CRUD Operations**: Create, read, update, and delete tenants
- **Domain Management**: Associate multiple domains with each tenant
- **User Management per Tenant**: Manage users belonging to specific tenants
- **Tenant Status Monitoring**: View tenant activity and statistics
- **Secure Tenant Operations**: Password confirmation for destructive actions

#### Tenant Management Features:
- **Tenant Creation**: Form-based tenant creation with custom data fields
- **Domain Association**: Multiple domain support per tenant
- **User Listing**: View all users associated with a specific tenant
- **Tenant Editing**: Update tenant information and domain associations
- **Secure Deletion**: Password-protected tenant deletion with confirmation

### 👥 User Management
Advanced user management system with both central and tenant-specific capabilities:

#### Central User Management:
- **Artisan Command**: `php artisan app:create-central-user` for creating central users
- **Interactive Mode**: Step-by-step user creation with validation
- **Batch Creation**: Create users via command-line options
- **Validation**: Email uniqueness, password strength, and required fields validation

#### Tenant User Management:
- **User CRUD Operations**: Complete user lifecycle management per tenant
- **Email Verification Status**: Visual indicators for verified/unverified emails
- **2FA Status Display**: Clear indication of two-factor authentication status
- **Tenant Association**: Automatic user assignment to specific tenants
- **Password Management**: Secure password updates with hash verification
- **User Profile Management**: Comprehensive user information editing

#### User Management Features:
- **User Creation**: Form-based user creation with validation
- **User Editing**: Update user information, email, and passwords
- **Email Verification**: Reset email verification when email changes
- **Security**: Password confirmation for user deletion
- **User Search**: Easy user lookup and management
- **Responsive Design**: Mobile-friendly user management interface

### 🛡️ Roles & Permissions Management
This starter kit includes a comprehensive role-based access control (RBAC) system using **Spatie Laravel Permission**:

#### Central Permissions (Central App)
Permissions for managing the central application and tenant operations:

```php
// CentralPermissions Enum
CREATE_TENANT    // Create new tenants
VIEW_TENANT      // View tenant information
UPDATE_TENANT    // Update tenant details
DELETE_TENANT    // Delete tenants

CREATE_TENANT_USER  // Create users for any tenant
VIEW_TENANT_USER    // View users from any tenant
UPDATE_TENANT_USER  // Update users from any tenant
DELETE_TENANT_USER  // Delete users from any tenant

CREATE_ROLE      // Create roles
VIEW_ROLE        // View roles
UPDATE_ROLE      // Update roles
DELETE_ROLE      // Delete roles
```

#### Central Roles
```php
// CentralRoles Enum
SUPER_ADMIN      // Has all central permissions
```

#### Tenant Permissions (Per Tenant)
Scoped permissions for operations within a specific tenant:

```php
// Permissions Enum (Tenant-scoped)
VIEW_TENANT_USER_BY_TENANT
CREATE_TENANT_USER_BY_TENANT
UPDATE_TENANT_USER_BY_TENANT
DELETE_TENANT_USER_BY_TENANT

VIEW_ROLE_BY_TENANT
CREATE_ROLE_BY_TENANT
UPDATE_ROLE_BY_TENANT
DELETE_ROLE_BY_TENANT
```

#### Tenant Roles
```php
// Roles Enum (Tenant-scoped)
ADMIN            // Tenant administrator with all tenant permissions
USER             // Regular tenant user with limited permissions
```

#### Permission Features:
- **Automatic Seeding**: Permissions and roles are automatically created when a tenant is created
- **Middleware Protection**: All controllers use permission middleware for route protection
- **Type-Safe Enums**: Permissions defined as PHP enums for type safety and IDE support
- **Scope Isolation**: Tenant permissions are isolated per tenant using `tenant_id`
- **Flexible Assignment**: Assign roles and permissions to users dynamically
- **Role Hierarchies**: Define role hierarchies with specific permission sets

#### Using Permissions in Code:

```php
// Check if user has permission
if (auth()->user()->can(CentralPermissions::CREATE_TENANT->value)) {
    // User can create tenants
}

// Check tenant-scoped permission
$tenant->run(function () {
    if (auth()->user()->can(Permissions::CREATE_TENANT_USER_BY_TENANT->value)) {
        // User can create users in this tenant
    }
});

// Assign role to user
$user->assignRole(CentralRoles::SUPER_ADMIN->value);

// Assign permission directly
$user->givePermissionTo(CentralPermissions::VIEW_TENANT->value);

// Check role
if ($user->hasRole(CentralRoles::SUPER_ADMIN->value)) {
    // User is super admin
}
```

#### Middleware Usage:

```php
// Protect routes with permissions
Route::middleware(['auth', 'permission:' . CentralPermissions::CREATE_TENANT->value])
    ->group(function () {
        // Protected routes
    });

// Controller middleware (already implemented)
public static function middleware(): array
{
    return [
        new Middleware(
            PermissionMiddleware::using(CentralPermissions::VIEW_TENANT),
            only: ['index', 'show']
        ),
    ];
}
```
### 8. Start the server
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

### Tenant & User Management
```bash
# Create a central user interactively
php artisan app:create-central-user

# Create a central user with options
php artisan app:create-central-user --name="Admin User" --email="admin@example.com" --password="SecurePass123"

# Access tenant management interface
# Navigate to /tenants in your central application

# Access user management for a specific tenant
# Navigate to /tenants/{tenant}/users in your central application
```

### Tenancy
```bash
# Run command for all tenants (single database)
php artisan tenants:run "cache:clear"

# List all tenants
php artisan tenants:list
```

### Roles & Permissions
```bash
# Seed central permissions and roles
php artisan db:seed --class=CentralPermissionsSeeder

# Seed permissions for all existing tenants
php artisan db:seed --class=PermissionsSeeder   
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
│   ├── Http/Controllers/
│   │   ├── TenantController.php        # Central tenant management
│   │   ├── TenantUserController.php    # Tenant-specific user management
│   │   ├── UserController.php          # Tenant user management (within tenant)
│   │   └── RoleController.php          # Role management
│   ├── Console/Commands/
│   │   └── CreateCentralUserCommand.php # Central user creation command
│   ├── Enums/
│   │   ├── CentralPermissions.php      # Central app permissions enum
│   │   ├── CentralRoles.php            # Central app roles enum
│   │   ├── Permissions.php             # Tenant-scoped permissions enum
│   │   └── Roles.php                   # Tenant-scoped roles enum
│   ├── Models/
│   │   ├── Tenant.php                  # Tenant model with domain relationships
│   │   ├── User.php                    # User model with 2FA and tenant scopes
│   │   ├── Role.php                    # Custom Role model (extends Spatie)
│   │   └── Permission.php              # Custom Permission model (extends Spatie)
│   ├── Http/Requests/
│   │   ├── Tenant/                     # Tenant validation requests
│   │   └── User/                       # User validation requests
│   ├── Providers/
│   │   ├── FortifyServiceProvider.php
│   │   └── TenancyServiceProvider.php
│   ├── Scopes/                         # Global scopes for tenant isolation
│   └── Actions/Fortify/                # Custom Fortify actions
├── config/
│   ├── tenancy.php                     # Tenancy configuration (single database)
│   ├── fortify.php                     # Fortify configuration
│   └── permission.php                  # Spatie permission configuration
├── database/
│   ├── migrations/                     # Single database migrations with tenant_id columns
│   └── seeders/
│       ├── CentralPermissionsSeeder.php # Seeds central permissions and roles
│       └── PermissionsSeeder.php       # Seeds tenant permissions and roles
├── routes/
│   ├── web.php                         # Central routes (includes tenant/user management)
│   ├── shared.php                      # Shared routes between tenant and central app
│   └── tenant.php                      # Tenant-specific routes
├── resources/views/
│   ├── tenants/                        # Tenant management views
│   │   ├── index.blade.php             # Tenant listing
│   │   ├── create.blade.php            # Tenant creation form
│   │   ├── edit.blade.php              # Tenant editing form
│   │   ├── show.blade.php              # Tenant details view
│   │   └── users/                      # Tenant user management views
│   │       ├── index.blade.php         # User listing per tenant
│   │       ├── create.blade.php        # User creation form
│   │       ├── edit.blade.php          # User editing form
│   │       └── show.blade.php          # User details view
│   ├── auth/                           # Authentication views
│   └── profile/                        # Profile views with 2FA
├── tests/Feature/
│   ├── Http/Controllers/
│   │   └── TenantUser/                 # Comprehensive TenantUserController tests
│   └── Console/Commands/
│       └── CreateCentralUserCommandTest.php # Central user command tests
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

### Testing
This project includes comprehensive test coverage for all major functionality:

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature

# Run tenant user controller tests
php artisan test tests/Feature/Http/Controllers/TenantUser/

# Run command tests
php artisan test tests/Feature/Console/Commands/

# Run tests with coverage
php artisan test --coverage
```

#### Test Coverage Includes:
- **TenantUserController**: Complete CRUD operation testing
- **CreateCentralUserCommand**: Interactive and option-based user creation
- **Authentication Tests**: Login, registration, 2FA functionality
- **Validation Tests**: Form validation and security checks
- **Database Tests**: Data integrity and tenant isolation
- **Feature Tests**: End-to-end functionality testing

## Quick Start Guide

### Initial Setup
1. Follow the installation steps above
2. Run migrations to create the database structure
3. Access the application at `http://localhost:8000`

### Creating Central Users
Use the Artisan command for administrative users:
```bash
php artisan app:create-central-user
```

### Creating Your First Tenant
1. Navigate to `/tenants` in your central application
2. Click "Create Tenant" button
3. Fill in the tenant ID and associated domains
4. Optionally add custom tenant data in JSON format
5. Save the tenant

### Managing Tenant Users
1. From the tenant list, click the "Manage Users" icon for any tenant
2. Use the "Create User" button to add new users to the tenant
3. Users will be automatically associated with the selected tenant
4. Manage user details, passwords, and email verification status

### Accessing Different Tenant Contexts
- Central application: Access via your main domain
- Tenant applications: Access via tenant-specific domains configured during tenant creation

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
