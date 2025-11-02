<?php

namespace App\Http\Controllers;

use App\Enums\CentralPermissions;
use App\Enums\Permissions;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Permission\Middleware\PermissionMiddleware;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using([CentralPermissions::CREATE_ROLE, Permissions::CREATE_ROLE_BY_TENANT]), only: ['create', 'store']),
            new Middleware(PermissionMiddleware::using([CentralPermissions::VIEW_ROLE, Permissions::VIEW_ROLE_BY_TENANT]), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using([CentralPermissions::UPDATE_ROLE, Permissions::UPDATE_ROLE_BY_TENANT]), only: ['edit', 'update']),
            new Middleware(PermissionMiddleware::using([CentralPermissions::DELETE_ROLE, Permissions::DELETE_ROLE_BY_TENANT]), only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::with(['permissions'])
                ->withCount('users')
                ->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->input('name'),
        ]);

        $role->syncPermissions($request->input('permissions'));

        return redirect()->route('roles.index')
            ->with('success', __('Role created successfully.'));
    }

    public function show(Role $role): View
    {
        $role->load(['permissions', 'users'])->loadCount('users');

        return view('roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('roles.edit', [
            'role' => $role,
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => $request->input('name'),
        ]);

        $role->syncPermissions($request->input('permissions'));

        return redirect()->route('roles.show', $role)
            ->with('success', __('Role updated successfully.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()->back()
                ->with('error', __('Cannot delete role :name because it has users assigned to it.', ['name' => $role->name]));
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', __('Role :name deleted successfully.', ['name' => $role->name]));
    }
}
