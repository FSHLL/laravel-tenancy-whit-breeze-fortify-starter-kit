@props([
    'action',
    'method' => 'POST',
    'role' => null,
    'permissions',
    'submitText' => 'Create Role',
    'cancelRoute' => 'roles.index'
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <!-- Role Name -->
    <div>
        <x-input-label for="name" :value="__('Role Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
            :value="old('name', $role?->name)" required autofocus autocomplete="name"
            placeholder="{{ __('Enter role name (e.g., Admin, Editor, Viewer)') }}" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Choose a descriptive name for this role. It will be used to identify the role throughout the application.') }}
        </p>
    </div>

    <!-- Current Role Info (only for edit) -->
    @if($role)
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Current Role Information') }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('Guard:') }}</span>
                    <span class="ml-1 font-medium">{{ $role->guard_name }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('Users:') }}</span>
                    <span class="ml-1 font-medium">{{ $role->users->count() }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('Created:') }}</span>
                    <span class="ml-1 font-medium">{{ $role->created_at->format('M j, Y') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Permissions -->
    <div>
        <x-input-label :value="__('Permissions')" class="mb-3" />
        <div class="space-y-4">
            @if ($permissions->count() > 0)
                <!-- Select All/None buttons -->
                <div class="flex gap-2 mb-4">
                    <button type="button" id="select-all"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('Select All') }}
                    </button>
                    <button type="button" id="select-none"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('Select None') }}
                    </button>
                    <div class="flex items-center ml-4 text-sm text-gray-600 dark:text-gray-400">
                        <span id="selected-count">{{ $role ? $role->permissions->count() : 0 }}</span> {{ __('of') }} {{ $permissions->count() }} {{ __('selected') }}
                    </div>
                </div>

                <!-- Permissions Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($permissions as $permission)
                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input id="permission_{{ $permission->id }}" name="permissions[]"
                                    type="checkbox" value="{{ $permission->name }}"
                                    {{
                                        $role
                                            ? ($role->hasPermissionTo($permission->name) ? 'checked' : '')
                                            : (in_array($permission->name, old('permissions', [])) ? 'checked' : '')
                                    }}
                                    class="permission-checkbox focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="permission_{{ $permission->id }}"
                                    class="font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                                    {{ $permission->name }}
                                </label>
                                @if ($permission->guard_name)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200 ml-2">
                                        {{ $permission->guard_name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
                <x-input-error :messages="$errors->get('permissions.*')" class="mt-2" />
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if($role)
                        {{ __('Select the permissions that users with this role should have. Changes will affect all users with this role.') }}
                    @else
                        {{ __('Select the permissions that users with this role should have. You can select multiple permissions.') }}
                    @endif
                </p>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.623 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ __('No permissions available') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('There are no permissions available to assign to this role.') }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end mt-6 space-x-3">
        <a href="{{ route($cancelRoute, $role ?? null) }}"
            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            {{ __('Cancel') }}
        </a>

        <x-primary-button>
            {{ $submitText }}
        </x-primary-button>
    </div>
</form>

<!-- JavaScript for Select All/None functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllBtn = document.getElementById('select-all');
        const selectNoneBtn = document.getElementById('select-none');
        const checkboxes = document.querySelectorAll('.permission-checkbox');
        const selectedCountSpan = document.getElementById('selected-count');

        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('.permission-checkbox:checked').length;
            selectedCountSpan.textContent = checkedCount;
        }

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateSelectedCount();
            });
        }

        if (selectNoneBtn) {
            selectNoneBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSelectedCount();
            });
        }

        // Update count when individual checkboxes are clicked
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        // Initialize count on page load
        updateSelectedCount();
    });
</script>
