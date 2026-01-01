<?php

namespace App\Http\Controllers;

use App\Enums\CentralPermissions;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Middleware\PermissionMiddleware;

class TenantUserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using(CentralPermissions::CREATE_TENANT_USER), only: ['create', 'store']),
            new Middleware(PermissionMiddleware::using(CentralPermissions::VIEW_TENANT_USER), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using(CentralPermissions::UPDATE_TENANT_USER), only: ['edit', 'update']),
            new Middleware(PermissionMiddleware::using(CentralPermissions::DELETE_TENANT_USER), only: ['destroy']),
        ];
    }

    public function index(Tenant $tenant): View
    {
        return view('tenants.users.index', [
            'tenant' => $tenant,
            'users' => User::withoutCentralApp()->where('tenant_id', $tenant->id)->paginate(),
        ]);
    }

    public function create(Tenant $tenant): View
    {
        $roles = $tenant->run(fn () => Role::with('permissions')->get());

        return view('tenants.users.create', compact('tenant', 'roles'));
    }

    public function store(StoreUserRequest $request, Tenant $tenant): RedirectResponse
    {
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'tenant_id' => $tenant->id,
        ]);

        $tenant->run(function () use ($user, $request) {
            $user->syncRoles($request->input('roles', []));
        });

        return redirect()->route('tenants.users.show', [$tenant, $user]);
    }

    public function show(Tenant $tenant, int $userId): View
    {
        $user = $tenant->run(function () use ($userId) {
            return User::with(['roles.permissions'])
                ->findOrFail($userId);
        });

        return view('tenants.users.show', [
            'tenant' => $tenant,
            'user' => $user,
        ]);
    }

    public function edit(Tenant $tenant, int $userId): View
    {
        [$user, $roles] = $tenant->run(function () use ($tenant, $userId) {
            return [
                User::withoutCentralApp()
                    ->where('tenant_id', $tenant->id)
                    ->with('roles', 'roles.permissions')
                    ->findOrFail($userId),
                Role::with('permissions')->get(),
            ];
        });

        return view('tenants.users.edit', [
            'tenant' => $tenant,
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(UpdateUserRequest $request, Tenant $tenant, int $userId): RedirectResponse
    {
        $user = User::withoutCentralApp()->where('tenant_id', $tenant->id)->findOrFail($userId);

        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        if ($request->input('email') !== $user->email) {
            $data['email_verified_at'] = null;
        }

        $tenant->run(function () use ($user, $data, $request) {
            $user->update($data);
            $user->syncRoles($request->input('roles', []));
        });

        return redirect()->route('tenants.users.show', [$tenant, $user])
            ->with('success', __('User updated successfully.'));
    }

    public function destroy(Request $request, Tenant $tenant, int $userId): RedirectResponse
    {
        $request->validateWithBag($userId, [
            'password' => ['required', 'current_password'],
        ]);

        $user = User::withoutCentralApp()->where('tenant_id', $tenant->id)->findOrFail($userId);

        $user->delete();

        return redirect()->route('tenants.users.index', $tenant)
            ->with('success', __('User deleted successfully.'));
    }
}
