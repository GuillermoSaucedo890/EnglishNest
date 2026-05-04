<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        URL::forceRootUrl(env('APP_URL'));
        URL::forceScheme('https');

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verifica tu correo - EnglishNest')
                ->line('Haz clic en el botón para verificar tu cuenta.')
                ->action('Verificar cuenta', $url)
                ->line('Después de verificar, vuelve a iniciar sesión.');
        });
    }
}
