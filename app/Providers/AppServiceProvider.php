<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verify your VyaparHub email address')
                ->greeting('Hi '.($notifiable->name ?? 'there').',')
                ->line('Thanks for signing up for VyaparHub! Please verify your email address to activate your account.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create a VyaparHub account, no further action is required.')
                ->salutation('— The VyaparHub Team');
        });
    }
}
