<?php

namespace App\Http\Controllers;

use App\Enums\CentralPermissions;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Spatie\Permission\Middleware\PermissionMiddleware;

class TenantController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using(CentralPermissions::CREATE_TENANT), only: ['create', 'store']),
            new Middleware(PermissionMiddleware::using(CentralPermissions::VIEW_TENANT), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using(CentralPermissions::UPDATE_TENANT), only: ['edit', 'update']),
            new Middleware(PermissionMiddleware::using(CentralPermissions::DELETE_TENANT), only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('tenants.index', [
            'tenants' => Tenant::with('domains')->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('tenants.create');
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $tenantData = collect($request->only('id'))
            ->merge(json_decode($request->input('data'), true) ?? [])
            ->toArray();

        $tenant = Tenant::create($tenantData);

        $tenant->domains()->createMany(
            $request->collect('domains')
                ->map(fn ($domain) => ['domain' => $domain])
        );

        $tenant->run(function () {
            foreach (Permissions::cases() as $permission) {
                Permission::create(['name' => $permission->value]);
            }

            foreach (Roles::cases() as $roleEnum) {
                $role = Role::create(['name' => $roleEnum->value]);
                $role->syncPermissions(Permission::whereIn('name', Permissions::byRole($roleEnum))->get());
            }
        });

        return redirect()->intended(route('tenants.index'));
    }

    public function show(Tenant $tenant): View
    {
        return view('tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant): View
    {
        return view('tenants.edit', compact('tenant'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenantData = json_decode($request->input('data'), true) ?? [];

        $attributes = Arr::except($tenant->getAttributes(), Tenant::getCustomColumns());

        foreach (array_keys($attributes) as $key) {
            unset($tenant->$key);
        }

        $tenant->update($tenantData);

        $tenant->domains()->delete();
        $tenant->domains()->createMany(
            $request->collect('domains')
                ->map(fn ($domain) => ['domain' => $domain])
        );

        return redirect()->intended(route('tenants.index'));
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validateWithBag($tenant->id, [
            'password' => ['required', 'current_password'],
        ]);

        Permission::withoutCentralApp()->where('tenant_id', $tenant->id)->delete();
        Role::withoutCentralApp()->where('tenant_id', $tenant->id)->delete();
        User::withoutCentralApp()->where('tenant_id', $tenant->id)->delete();

        $tenant->delete();

        return redirect()->intended(route('tenants.index'));
    }
}
