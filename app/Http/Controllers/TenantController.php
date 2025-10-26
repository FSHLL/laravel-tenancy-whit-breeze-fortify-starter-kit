<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class TenantController extends Controller
{
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

        Tenant::create($tenantData)
            ->domains()->createMany(
                $request->collect('domains')
                    ->map(fn ($domain) => ['domain' => $domain])
            );

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
