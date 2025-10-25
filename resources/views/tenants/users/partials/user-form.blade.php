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

@if(isset($user))
    <!-- Current User Info for Edit -->
    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('Current Information') }}
        </h4>
        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            <p><strong>{{ __('Name') }}:</strong> {{ $user->name }}</p>
            <p><strong>{{ __('Email') }}:</strong> {{ $user->email }}</p>
            <p><strong>{{ __('Tenant') }}:</strong> {{ $tenant->id }}</p>
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
@else
    <!-- Tenant Info for Create -->
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('Creating user for tenant') }}
        </h4>
        <div class="text-sm text-gray-600 dark:text-gray-400">
            <p><strong>{{ __('Tenant ID') }}:</strong> {{ $tenant->id }}</p>
        </div>
    </div>
@endif
