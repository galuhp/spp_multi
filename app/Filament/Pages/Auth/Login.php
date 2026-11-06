<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;
use Throwable;

class Login extends BaseLogin
{
    /**
     * Use username instead of email for authentication.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username / NISN')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * Show a visible notification whenever authentication fails.
     */
    #[Exposed]
    public function authenticate(): ?LoginResponse
    {
        try {
            return parent::authenticate();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?: 'Username atau kata sandi tidak sesuai.';

            Notification::make()
                ->title('Login gagal')
                ->body($message)
                ->danger()
                ->send();

            throw $exception;
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Login gagal')
                ->body('Terjadi kesalahan saat memproses login. Silakan coba lagi.')
                ->danger()
                ->send();

            throw $exception;
        }
    }

    /**
     * Keep password visibility available on the custom login page.
     */
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    /**
     * Map the username field to credentials for authentication.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    /**
     * Attach the failed login message to the username field.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => 'Username atau kata sandi tidak sesuai.',
        ]);
    }
}
