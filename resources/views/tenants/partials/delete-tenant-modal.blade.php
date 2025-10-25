<x-modal name="{{ 'confirm-tenant-deletion-' . $tenant->id }}" :show="$errors->{$tenant->id}->isNotEmpty()" focusable>
    <form method="post" action="{{ route('tenants.destroy', $tenant) }}" class="p-6">
        @csrf
        @method('delete')

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Are you sure you want to delete this tenant?') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once this tenant is deleted, all of its data, domains, and resources will be permanently deleted. This action cannot be undone.') }}
        </p>

        <div class="mt-6">
            <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-3/4"
                placeholder="{{ __('Password') }}" />
            <x-input-error :messages="$errors->tenantDeletion->get('password')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{ __('Delete Tenant') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
