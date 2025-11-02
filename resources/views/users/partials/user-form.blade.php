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
    <div class="flex items-center justify-between mb-2">
        <x-input-label for="roles" :value="__('Assign Roles')" />
        <div class="flex gap-2">
            <button type="button" id="select-all-roles"
                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-medium">
                {{ __('Select All') }}
            </button>
            <span class="text-gray-400">|</span>
            <button type="button" id="select-none-roles"
                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-medium">
                {{ __('Select None') }}
            </button>
        </div>
    </div>

    <div class="mt-2 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            {{ __('Select the roles to assign to this user. Selected:') }}
            <span id="selected-roles-count" class="font-semibold text-indigo-600 dark:text-indigo-400">0</span>
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($roles as $role)
                <label class="flex items-start p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-indigo-300 dark:hover:border-indigo-600 cursor-pointer transition-colors">
                    <input type="checkbox"
                        name="roles[]"
                        value="{{ $role->name }}"
                        class="role-checkbox mt-1 rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                        {{ isset($user) && $user->hasRole($role->name) ? 'checked' : '' }}>
                    <div class="ml-3 flex-1">
                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $role->name }}
                        </span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $role->permissions->count() }} {{ __('permissions') }}
                            @if($role->guard_name)
                                · {{ $role->guard_name }}
                            @endif
                        </span>
                    </div>
                </label>
            @endforeach
        </div>
    </div>
    <x-input-error :messages="$errors->get('roles')" class="mt-2" />
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
