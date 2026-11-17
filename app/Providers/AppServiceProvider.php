<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        if (request()->isSecure() || request()->server('HTTP_X_FORWARDED_PROTO') === 'https' || request()->server('HTTPS') === 'on') {
            URL::forceScheme('https');
        }

        $this->bootEmailVerificationNotification();
        $this->bootRateLimiters();
    }

    /**
     * Configure email verification mail message and official sender notice.
     */
    protected function bootEmailVerificationNotification(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $senderEmail = (string) config('mail.from.address', 'gusti.police.duty@12655999.brevosend.com');
            $senderName = (string) config('mail.from.name', 'Gusti Police Duty - IME Roleplay');

            return (new MailMessage)
                ->subject('Verifikasi Alamat Email - Police Duty Multiview')
                ->greeting('Halo, ' . ($notifiable->name ?? 'Officer') . '!')
                ->line('Terima kasih telah mendaftar di Police Duty Multiview (SASP MotionLife RP).')
                ->line('Silakan klik tombol di bawah ini untuk memverifikasi alamat email akun Anda:')
                ->action('Verifikasi Alamat Email', $url)
                ->line('INFORMASI KEAMANAN: Email verifikasi resmi sistem HANYA dikirimkan melalui alamat ' . $senderEmail . ' (' . $senderName . '). Harap waspadai segala bentuk pesan mencurigakan yang mengatasnamakan IME Police.')
                ->line('Jika Anda tidak merasa mendaftar di platform ini, Anda dapat mengabaikan email ini dengan aman.')
                ->salutation("Salam Hormat,\nSASP Command Team");
        });
    }

    /**
     * Each feature gets its own bucket. Laravel's unnamed `throttle:N,M` on
     * authenticated routes keys only by user id, so without this all save
     * endpoints across the app would share one counter — a user who just
     * saved a bio-link could hit "Too Many Attempts" on their first squad.
     */
    protected function bootRateLimiters(): void
    {
        $perUser = fn (Request $request) => (string) ($request->user()?->getAuthIdentifier() ?: $request->ip());

        RateLimiter::for('squad-save', fn (Request $r) => Limit::perMinute(60)->by($perUser($r)));
        RateLimiter::for('bio-link-save', fn (Request $r) => Limit::perMinute(60)->by($perUser($r)));
        RateLimiter::for('bio-profile-save', fn (Request $r) => Limit::perMinute(30)->by($perUser($r)));
        RateLimiter::for('officer-profile-save', fn (Request $r) => Limit::perMinute(30)->by($perUser($r)));
        RateLimiter::for('clip-trim', fn (Request $r) => Limit::perMinute(20)->by($perUser($r)));
    }
}

