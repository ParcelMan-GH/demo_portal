<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EmailTemplateService
{
    public function send(string $key, Model $recipient, array $variables = [], array $options = []): bool
    {
        $template = EmailTemplate::query()->where('key', $key)->first();

        if (!$template?->is_enabled) {
            return false;
        }

        $email = $options['email'] ?? $recipient->email ?? null;
        $email = is_string($email) ? trim($email) : null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $variables = array_merge($this->defaultVariables(), $variables);
        $subject = $this->render($template->subject, $variables);
        $html = $this->render($template->body_html ?: nl2br(e((string) $template->body_text)), $variables);
        $text = $this->render($template->body_text ?: trim(strip_tags($html)), $variables);
        $notifiable = $options['notifiable'] ?? $recipient;

        try {
            Mail::html($html, function ($message) use ($email, $recipient, $subject) {
                $message->to($email, $recipient->name ?? null)->subject($subject);
            });

            $this->log($notifiable, $template, $subject, $text, $variables, 'sent');

            return true;
        } catch (Throwable $e) {
            $this->log($notifiable, $template, $subject, $text, $variables, 'failed', $e->getMessage());

            return false;
        }
    }

    public function preview(EmailTemplate $template, ?array $sampleData = null): array
    {
        $variables = array_merge(
            $this->defaultVariables(),
            $sampleData ?: $this->sampleVariables($template)
        );
        $html = $this->render($template->body_html ?: nl2br(e((string) $template->body_text)), $variables);

        return [
            'subject' => $this->render($template->subject, $variables),
            'body_html' => $html,
            'body_text' => $this->render($template->body_text ?: trim(strip_tags($html)), $variables),
            'variables' => $variables,
        ];
    }

    public function render(string $content, array $variables): string
    {
        return preg_replace_callback('/{{\s*([A-Za-z0-9_.-]+)\s*}}/', function (array $matches) use ($variables) {
            $value = data_get($variables, $matches[1], '');

            if (is_scalar($value) || $value === null) {
                return (string) $value;
            }

            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }, $content) ?? $content;
    }

    public function sampleVariables(EmailTemplate $template): array
    {
        $samples = [
            'vendor_name' => 'Akua Mensah',
            'recipient_name' => 'Kwame Dela',
            'user_name' => 'Kwame Dela',
            'platform_name' => PlatformSetting::getValue('platform_name', config('app.name', 'Parcelman')),
            'login_url' => url('/vendor/login'),
            'reset_url' => url('/reset-password/sample-token'),
            'expires_in' => '60 minutes',
            'shipment_number' => 'PCM-2026-00025',
            'tracking_code' => 'TRK1SRFFMPF',
            'warehouse_name' => 'Accra Main Hub',
            'warehouse_address' => 'Accra Main Hub',
            'driver_name' => 'John Driver',
            'driver_phone' => '+233244111111',
            'run_number' => 'DR-2026-0007',
            'amount' => '25.00',
            'currency' => 'GHS',
            'payment_reference' => 'MOMO-12345',
            'delivered_at' => now()->format('d M Y, h:i A'),
        ];

        return collect($template->variables ?: [])
            ->mapWithKeys(fn (string $variable) => [$variable => $samples[$variable] ?? Str::headline($variable)])
            ->all();
    }

    private function defaultVariables(): array
    {
        return [
            'platform_name' => PlatformSetting::getValue('platform_name', config('app.name', 'Parcelman')),
            'support_email' => PlatformSetting::getValue('platform_email', config('mail.from.address')),
            'support_phone' => PlatformSetting::getValue('platform_phone', ''),
        ];
    }

    private function log(
        Model $notifiable,
        EmailTemplate $template,
        string $subject,
        string $body,
        array $variables,
        string $status,
        ?string $error = null
    ): void {
        NotificationLog::query()->create([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => (int) $notifiable->getKey(),
            'type' => $template->key,
            'channel' => 'email',
            'title' => $subject,
            'body' => $body,
            'data' => [
                'template_key' => $template->key,
                'variables' => $variables,
            ],
            'status' => $status,
            'error' => $error,
        ]);
    }
}
