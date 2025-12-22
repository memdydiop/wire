<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Symfony\Component\HttpFoundation\Response;

new class extends Component {
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';

    #[Locked]
    public bool $twoFactorEnabled;

    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), Response::HTTP_FORBIDDEN);

        if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
            $disableTwoFactorAuthentication(auth()->user());
        }

        $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    /**
     * Validation rules with custom messages
     */
    public function rules()
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(3)
            ],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages()
    {
        return [
            'current_password.required' => 'Le mot de passe actuel est requis.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required' => 'Le nouveau mot de passe est requis.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
            'password.letters' => 'Le mot de passe doit contenir au moins une lettre.',
            'password.mixedCase' => 'Le mot de passe doit contenir des majuscules et des minuscules.',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'password.symbols' => 'Le mot de passe doit contenir au moins un caractère spécial.',
            'password.uncompromised' => 'Ce mot de passe a été compromis dans une fuite de données. Veuillez en choisir un autre.',
            'code.required' => 'Le code de vérification est requis.',
            'code.size' => 'Le code de vérification doit contenir exactement 6 chiffres.',
        ];
    }

    public function updatePassword()
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(3)
                ],
            ], $this->messages());
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        flash()->success('Votre mot de passe a été modifié avec succès.');
        $this->dispatch('password-updated');
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $enableTwoFactorAuthentication(auth()->user());

        if (!$this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();

        $this->showModal = true;
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Échec de la récupération des données de configuration.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Le code de vérification est requis.',
            'code.size' => 'Le code de vérification doit contenir exactement 6 chiffres.',
        ]);

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->closeModal();

        $this->twoFactorEnabled = true;
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;

        flash()->success('L\'authentification à deux facteurs a été désactivée avec succès.');
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showModal',
            'showVerificationStep',
        );

        $this->resetErrorBag();

        if (!$this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }

    /**
     * Get the current modal configuration state.
     */
    public function getModalConfigProperty(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => __('Two-Factor Authentication Enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify Authentication Code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable Two-Factor Authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }
}; 
?>

<div>
    <div class="mb-4">
        <flux:subheading class="text-sm text-zinc-500">Mot de passe et authentification</flux:subheading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Changement de mot de passe -->
        <div class="col-span-1 bg-gray-50 rounded-lg p-4 space-y-4">
            <flux:heading level="3" size="xs" class="mb-3">Modifier le mot de passe</flux:heading>
            <div class="text-xs text-gray-500">
                <p>Votre mot de passe doit contenir :</p>
                <ul class="list-disc list-inside mt-1">
                    <li>Au moins 8 caractères</li>
                    <li>Des lettres majuscules et minuscules</li>
                    <li>Au moins un chiffre</li>
                    <li>Au moins un caractère spécial</li>
                </ul>
            </div>
            <form wire:submit="updatePassword">
                <div class="space-y-3">
                    <flux:input wire:model="current_password" :label="__('Current password')" type="password" required
                        autocomplete="current-password" :error="$errors->first('current_password')" />

                    <flux:input wire:model="password" :label="__('New password')" type="password" required
                        autocomplete="new-password" :error="$errors->first('password')" />

                    <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password"
                        required autocomplete="new-password" />

                    <div class="flex justify-end pt-2">
                        <flux:button variant="primary" icon="lock-closed" icon:variant="outline" type="submit"
                            data-test="update-password-button">
                            Modifier le mot de passe
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Authentification à deux facteurs -->
        <div class="col-span-1 bg-gray-50 rounded-lg p-4">
            <flux:heading level="3" size="xs" class="mb-3 ">{{ __('Two Factor Authentication') }}</flux:heading>

            @if ($twoFactorEnabled)
                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    <div class="flex flex-1 items-center gap-3">
                        <flux:badge color="green">{{ __('Enabled') }}</flux:badge>
                    </div>

                    <flux:text>
                        {{ __('With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </flux:text>

                    <livewire:settings.two-factor.recovery-codes :$requiresConfirmation />
                    <flux:spacer />
                    <div class="flex justify-start">
                        <flux:button variant="danger" icon="shield-exclamation" icon:variant="outline" wire:click="disable">
                            {{ __('Disable 2FA') }}
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="w-full flex flex-col items-center justify-center text-sm" wire:cloak>

                    <flux:text variant="subtle" class="text-center pb-4">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </flux:text>

                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="shield-check" icon:variant="outline" wire:click="enable">
                            {{ __('Enable 2FA') }}
                        </flux:button>
                    </div>

                </div>
            @endif
        </div>
    </div>

    <flux:modal name="two-factor-setup-modal" class="max-w-md md:min-w-md" @close="closeModal" wire:model="showModal">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div
                    class="p-0.5 w-auto rounded-full border border-stone-100 bg-white shadow-sm">
                    <div
                        class="p-2.5 rounded-full border border-stone-200 overflow-hidden bg-stone-100 relative">
                        <div
                            class="flex items-stretch absolute inset-0 w-full h-full divide-x [&>div]:flex-1 divide-stone-200  justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <div
                            class="flex flex-col items-stretch absolute w-full h-full divide-y [&>div]:flex-1 inset-0 divide-stone-200 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <flux:icon.qr-code class="relative z-20" />
                    </div>
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                    <flux:text>{{ $this->modalConfig['description'] }}</flux:text>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-3">
                        <x-input-otp :digits="6" name="code" wire:model="code" autocomplete="one-time-code" />
                        @error('code')
                            <flux:text color="red">
                                {{ $message }}
                            </flux:text>
                        @enderror
                    </div>

                    <div class="flex items-center space-x-3">
                        <flux:button variant="outline" class="flex-1" wire:click="resetVerification">
                            {{ __('Back') }}
                        </flux:button>

                        <flux:button variant="primary" class="flex-1" wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6">
                            {{ __('Confirm') }}
                        </flux:button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                @enderror

                <div class="flex justify-center">
                    <div
                        class="relative w-64 overflow-hidden border rounded-lg border-stone-200 aspect-square">
                        @empty($qrCodeSvg)
                            <div
                                class="absolute inset-0 flex items-center justify-center bg-white animate-pulse">
                                <flux:icon.loading />
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full p-4">
                                <div class="bg-white p-3 rounded">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <flux:button :disabled="$errors->has('setupData')" variant="primary" class="w-full"
                        wire:click="showVerificationIfNecessary">
                        {{ $this->modalConfig['buttonText'] }}
                    </flux:button>
                </div>

                <div class="space-y-4">
                    <div class="relative flex items-center justify-center w-full">
                        <div class="absolute inset-0 w-full h-px top-1/2 bg-stone-200 "></div>
                        <span class="relative px-2 text-sm bg-white  text-stone-600 ">
                            {{ __('or, enter the code manually') }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-2" x-data="{
                                    copied: false,
                                    async copy() {
                                        try {
                                            await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 1500);
                                        } catch (e) {
                                            console.warn('Could not copy to clipboard');
                                        }
                                    }
                                }">
                        <div class="flex items-stretch w-full border rounded-xl ">
                            @empty($manualSetupKey)
                                <div class="flex items-center justify-center w-full p-3 bg-stone-100 ">
                                    <flux:icon.loading variant="mini" />
                                </div>
                            @else
                                <input type="text" readonly value="{{ $manualSetupKey }}"
                                    class="w-full p-3 bg-transparent outline-none text-stone-900 " />

                                <button @click="copy()"
                                    class="px-3 transition-colors border-l cursor-pointer border-stone-200">
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline"></flux:icon>
                                        <flux:icon.check x-show="copied" variant="solid" class="text-green-500">
                                            </flux:icon>
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>