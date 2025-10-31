<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::where('id', '!=', auth()->id())->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        $user->syncRoles($request->input('roles', []));

        return redirect()->route('users.show', $user)
            ->with('success', __('User created successfully.'));
    }

    public function show(User $user): View
    {
        $user->load(['roles.permissions']);

        return view('users.show', [
            'user' => $user,
        ]);
    }

    public function edit(int $userId): View
    {
        $user = User::with('roles')->findOrFail($userId);

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        if ($request->input('email') !== $user->email) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('users.show', $user)
            ->with('success', __('User updated successfully.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $request->validateWithBag($user->id, [
            'password' => ['required', 'current_password'],
        ]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', __('User deleted successfully.'));
    }
}
