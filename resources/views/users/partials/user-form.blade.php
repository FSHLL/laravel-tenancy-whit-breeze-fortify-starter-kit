<!-- Name -->
<div class="mb-6">
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
        :value="old('name', $user->name ?? '')" required autofocus autocomplete="name" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<!-- Email Address -->
<div class="mb-6">
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
        :value="old('email', $user->email ?? '')" required autocomplete="username" />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<!-- Password -->
<div class="mb-6">
    <x-input-label for="password" :value="__('Password')" />
    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
        :required="!isset($user)" autocomplete="new-password" />
    @if(isset($user))
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Leave blank to keep current password') }}
        </p>
    @endif
    <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>

<!-- Confirm Password -->
<div class="mb-6">
    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
        name="password_confirmation" autocomplete="new-password" />
</div>

<!-- Roles -->
@if(isset($roles) && $roles->count() > 0)
    <div class="mb-6">
        <x-input-label :value="__('Roles')" class="mb-3" />
        <div class="space-y-4">
            <!-- Select All/None buttons -->
            <div class="flex gap-2 mb-4">
                <button type="button" id="select-all-roles"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Select All') }}
                </button>
                <button type="button" id="select-none-roles"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Select None') }}
                </button>
                <div class="flex items-center ml-4 text-sm text-gray-600 dark:text-gray-400">
                    <span id="selected-roles-count">{{ isset($user) && $user->roles ? $user->roles->count() : 0 }}</span> {{ __('of') }} {{ $roles->count() }} {{ __('selected') }}
                </div>
            </div>

            <!-- Roles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($roles as $role)
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input id="role_{{ $role->id }}" name="roles[]"
                                type="checkbox" value="{{ $role->name }}"
                                {{
                                    isset($user) && $user->hasRole($role->name)
                                        ? 'checked'
                                        : (in_array($role->name, old('roles', [])) ? 'checked' : '')
                                }}
                                class="role-checkbox focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="role_{{ $role->id }}"
                                class="font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                                {{ $role->name }}
                            </label>
                            @if ($role->permissions && $role->permissions->count() > 0)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $role->permissions->count() }} {{ __('permission(s)') }}
                                </p>
                            @endif
                            @if ($role->guard_name)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200 ml-0 mt-1">
                                    {{ $role->guard_name }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <x-input-error :messages="$errors->get('roles')" class="mt-2" />
            <x-input-error :messages="$errors->get('roles.*')" class="mt-2" />
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @if(isset($user))
                    {{ __('Select the roles that this user should have. Changes will take effect immediately after saving.') }}
                @else
                    {{ __('Select the roles that this new user should have. You can modify roles later.') }}
                @endif
            </p>
        </div>
    </div>
@endif

@if(isset($user))
    <!-- Current User Info for Edit -->
    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('Current Information') }}
        </h4>
        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            <p><strong>{{ __('Name') }}:</strong> {{ $user->name }}</p>
            <p><strong>{{ __('Email') }}:</strong> {{ $user->email }}</p>
            <p><strong>{{ __('Created') }}:</strong> {{ $user->created_at->format('M d, Y') }}</p>
            @if($user->email_verified_at)
                <p><strong>{{ __('Email Verified') }}:</strong>
                    <span class="text-green-600 dark:text-green-400">{{ __('Yes') }}</span>
                </p>
            @else
                <p><strong>{{ __('Email Verified') }}:</strong>
                    <span class="text-yellow-600 dark:text-yellow-400">{{ __('No') }}</span>
                </p>
            @endif
        </div>
    </div>
@endif

<!-- JavaScript for Role Selection -->
@if(isset($roles) && $roles->count() > 0)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllBtn = document.getElementById('select-all-roles');
        const selectNoneBtn = document.getElementById('select-none-roles');
        const roleCheckboxes = document.querySelectorAll('.role-checkbox');
        const selectedCountSpan = document.getElementById('selected-roles-count');

        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('.role-checkbox:checked').length;
            selectedCountSpan.textContent = checkedCount;
        }

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                roleCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateSelectedCount();
            });
        }

        if (selectNoneBtn) {
            selectNoneBtn.addEventListener('click', function() {
                roleCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSelectedCount();
            });
        }

        roleCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        updateSelectedCount();
    });
</script>
@endif
