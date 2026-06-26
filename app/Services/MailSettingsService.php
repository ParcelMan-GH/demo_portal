<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Config;

class MailSettingsService
{
    public const TEST_FAILURE_MESSAGE = 'Failed to send test email. Please check the SMTP host, port, encryption, username, and password, then try again.';

    public function apply(): void
    {
        $mailer = $this->setting('mail_mailer', Config::get('mail.default', 'smtp')) ?: 'smtp';
        $host = $this->setting('mail_host', Config::get('mail.mailers.smtp.host'));
        $port = (int) ($this->setting('mail_port', Config::get('mail.mailers.smtp.port', 587)) ?: 587);
        $username = $this->setting('mail_username', Config::get('mail.mailers.smtp.username'), allowBlank: true);
        $password = $this->setting('mail_password', Config::get('mail.mailers.smtp.password'), allowBlank: true);
        $encryption = $this->setting('mail_encryption', Config::get('mail.mailers.smtp.encryption'));
        $scheme = $this->smtpScheme($encryption);
        $fromAddress = $this->setting('mail_from_address', Config::get('mail.from.address'));
        $fromName = $this->setting('mail_from_name', Config::get('mail.from.name'));

        Config::set('mail.default', $mailer);

        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.scheme', $scheme);
            Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
            Config::set('mail.mailers.smtp.auto_tls', filled($encryption));
        }

        if ($fromAddress) {
            Config::set('mail.from.address', $fromAddress);
        }

        if ($fromName) {
            Config::set('mail.from.name', $fromName);
        }

        $manager = app('mail.manager');
        $isMock = interface_exists(\Mockery\MockInterface::class)
            && $manager instanceof \Mockery\MockInterface;

        if (!$isMock && $manager instanceof MailManager) {
            $manager->forgetMailers();
        }
    }

    private function setting(string $key, mixed $fallback = null, bool $allowBlank = false): mixed
    {
        $exists = PlatformSetting::query()->where('key', $key)->exists();
        $value = PlatformSetting::getValue($key, null);

        if ($value === null || (!$allowBlank && $value === '')) {
            return $fallback;
        }

        if ($allowBlank && $exists && $value === '') {
            return null;
        }

        return $value;
    }

    private function smtpScheme(mixed $encryption): string
    {
        return $encryption === 'ssl' ? 'smtps' : 'smtp';
    }
}
