@php
    $isEdit = isset($tenant);
    $formAction = $isEdit ? route('tenants.update', $tenant) : route('tenants.store');
    $submitText = $isEdit ? __('Update Tenant') : __('Create Tenant');

    $currentDomains =
        $isEdit && !old('domains') ? $tenant->domains->pluck('domain')->toArray() : (old('domains') ?: ['']);
@endphp

<form method="POST" action="{{ $formAction }}" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <!-- Tenant ID -->
    <div>
        <x-input-label for="id" :value="__('Tenant ID')" />
        <x-text-input id="id" name="id" type="text"
            class="mt-1 block w-full {{ $isEdit ? 'bg-gray-100 dark:bg-gray-700' : '' }}" :value="old('id', $isEdit ? $tenant->id : '')"
            {{ $isEdit ? 'readonly' : 'required' }} autofocus autocomplete="off" placeholder="unique-tenant-id" />
        <x-input-error class="mt-2" :messages="$errors->get('id')" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            @if ($isEdit)
                {{ __('Tenant ID cannot be changed after creation.') }}
            @else
                {{ __('A unique identifier for this tenant. Use lowercase letters, numbers, and hyphens only.') }}
            @endif
        </p>
    </div>

    <!-- Domains -->
    <div x-data="{ domains: {{ json_encode($currentDomains) }} }">
        <x-input-label :value="__('Domains')" />
        <div class="mt-2 space-y-3">
            <template x-for="(domain, index) in domains" :key="index">
                <div class="flex gap-2">
                    <x-text-input x-model="domains[index]" :name="'domains[]'" type="text" required
                        class="block w-full" placeholder="example.com" />

                    <button type="button" @click="domains.splice(index, 1)" x-show="domains.length > 1"
                        class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
        <button type="button" @click="domains.push('')"
            class="mt-3 inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-4 h-4 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ __('Add Domain') }}
        </button>
        <x-input-error class="mt-2" :messages="$errors->get('domains.*')" />
    </div>

    <!-- Data (JSON) - Optional -->
    <div>
        <x-input-label for="data" :value="__('Additional Data (JSON) - Optional')" />
        <textarea id="data" name="data" rows="4"
            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
            placeholder='{"key": "value"}'>{{ old('data', $isEdit ? json_encode($tenant->getAdditionalData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('data')" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Optional custom data in JSON format.') }}
        </p>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('tenants.index') }}"
            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            {{ __('Cancel') }}
        </a>
        <x-primary-button>
            {{ $submitText }}
        </x-primary-button>
    </div>
</form>
