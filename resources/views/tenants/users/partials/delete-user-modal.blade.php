<x-modal name="confirm-user-deletion-{{ $user->id }}" :show="$errors->{$user->id}->isNotEmpty()" focusable>
    <form method="post" action="{{ route('tenants.users.destroy', [$tenant, $user]) }}" class="p-6">
        @csrf
        @method('delete')

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Are you sure you want to delete this user?') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once this user is deleted, all of their data will be permanently deleted. Please enter your password to confirm you would like to permanently delete this user.') }}
        </p>

        <div class="mt-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <x-input-label for="password-{{ $user->id }}" value="{{ __('Password') }}" class="sr-only" />

            <x-text-input
                id="password-{{ $user->id }}"
                name="password"
                type="password"
                class="mt-1 block w-3/4"
                placeholder="{{ __('Password') }}"
            />

            @if($errors->getBag($user->id)->has('password'))
                <x-input-error :messages="$errors->getBag($user->id)->get('password')" class="mt-2" />
            @endif
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{ __('Delete User') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
