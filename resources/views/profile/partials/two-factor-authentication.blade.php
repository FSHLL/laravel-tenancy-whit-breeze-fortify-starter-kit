<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Two Factor Authentication') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Add additional security to your account using two factor authentication.') }}
        </p>
    </header>

    <form method="post"
        action="{{ auth()->user()->two_factor_secret ? route('two-factor.disable') : route('two-factor.enable') }}"
        class="mt-6 space-y-6">
        @csrf

        <div class="gap-4">

            @if (auth()->user()->two_factor_secret)
                @method('DELETE')
                <x-danger-button>{{ __('Disable') }}</x-danger-button>
            @else
                <x-primary-button>{{ __('Enable') }}</x-primary-button>
            @endif
        </div>
    </form>

    <div class="gap-4 mt-4">

        @if (auth()->user()->two_factor_secret)
            {{-- QR Code --}}
            <div class="mb-6">
                {!! auth()->user()->twoFactorQrCodeSvg() !!}
            </div>

            @if (!auth()->user()->two_factor_confirmed_at)
                <form class="mb-6" method="POST" action="{{ url('user/confirmed-two-factor-authentication') }}">
                    @csrf

                    <div class="mb-2">
                        <x-input-label for="code" :value="__('Code')" />
                        <x-text-input id="code" name="code" type="password" class="mt-1 block w-full" autocomplete="code" />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Confirm') }}</x-primary-button>

                        @if (session('status') === 'two-factor-authentication-confirmed')
                            <p
                                x-data="{ show: true }"
                                x-show="show"
                                x-transition
                                x-init="setTimeout(() => show = false, 3000)"
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >{{ __('Two-factor authentication confirmed and enabled successfully.') }}</p>
                        @endif
                    </div>
                </form>
            @endif

            <div class="mb-6">
                <p class="mt-1 text-sm text-gray-600">
                    <strong>{{ __('Recovery Codes:') }}</strong>
                </p>
                @foreach ((array) auth()->user()->recoveryCodes() as $recoveryCode)
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $recoveryCode }}
                    </p>
                @endforeach
            </div>

            {{-- Re-Generating Recovery Codes --}}
            <form method="POST" action="{{ url('user/two-factor-recovery-codes') }}">
                @csrf
                <x-primary-button>{{ __('Re-Generate Recovery Codes') }}</x-primary-button>
            </form>
        @endif

        @php
            $sessionStatus =
                session('status') === 'two-factor-authentication-enabled'
                    ? 'Two factor authentication is enabled.'
                    : (session('status') === 'two-factor-authentication-disabled'
                        ? 'Two factor authentication is disabled.'
                        : '');
        @endphp

        @if ($sessionStatus)
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)" class="text-sm text-gray-600">
                {{ __($sessionStatus) }}</p>
        @endif

    </div>
</section>
