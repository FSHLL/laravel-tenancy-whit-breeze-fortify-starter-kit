<x-modal name="confirm-role-deletion-{{ $role->id }}" :show="$errors->{$role->id}->isNotEmpty()" focusable>
    <form method="post" action="{{ route('roles.destroy', $role) }}" class="p-6">
        @csrf
        @method('delete')

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Are you sure you want to delete this role?') }}
        </h2>

        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                        {{ __('Role: :name', ['name' => $role->name]) }}
                    </h3>
                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li>{{ __('This role has :count permission(s) assigned', ['count' => $role->permissions->count()]) }}</li>
                            <li>{{ __('This role is assigned to :count user(s)', ['count' => $role->users_count]) }}</li>
                            @if($role->users_count > 0)
                                <li class="font-medium">{{ __('Users with this role will lose their permissions!') }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once this role is deleted, all of its resources and data will be permanently deleted. This action cannot be undone.') }}
        </p>

        @if($role->users_count > 0)
            <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200 font-medium">
                            {{ __('Warning: This role cannot be deleted because it has users assigned to it.') }}
                        </p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            {{ __('Please remove all users from this role before attempting to delete it.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-6 flex justify-end space-x-3">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            @if($role->users_count === 0)
                <x-danger-button class="ml-3">
                    {{ __('Delete Role') }}
                </x-danger-button>
            @else
                <x-danger-button disabled class="ml-3 opacity-50 cursor-not-allowed">
                    {{ __('Cannot Delete') }}
                </x-danger-button>
            @endif
        </div>
    </form>
</x-modal>
