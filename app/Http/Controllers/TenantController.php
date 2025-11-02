<?php

namespace App\Http\Controllers;

use App\Enums\CentralPermissions;
use App\Enums\Permissions;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Permission;
use App\Models\Tenant;
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

        foreach (Permissions::cases() as $permission) {
            Permission::create(['name' => $permission->value, 'tenant_id' => $tenant->id]);
        }

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

        $tenant->delete();

        return redirect()->intended(route('tenants.index'));
    }
}
