<?php

namespace App\Services;

use App\Mail\MowsNotificationMail;
use App\Models\Setting;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Check whether a specific notification channel is enabled in Global Settings.
     * Defaults to false ('0') so all notifications remain off until explicitly enabled.
     */
    public static function isChannelEnabled(string $channel): bool
    {
        $key = match ($channel) {
            'email'   => 'notifications_email_enabled',
            'discord' => 'notifications_discord_enabled',
            'pm'      => 'notifications_pm_enabled',
            default   => '',
        };

        if ($key === '') {
            return false;
        }

        $val = Setting::where('key', $key)->value('value');

        return in_array(strtolower((string) $val), ['1', 'true', 'yes', 'enabled'], true);
    }

    /**
     * Dynamically configure Laravel's mailer using database settings.
     */
    protected static function applyMailConfig(): void
    {
        $settings = Setting::whereIn('key', [
            'mail_host',
            'mail_port',
            'mail_encryption',
            'mail_username',
            'mail_password',
            'mail_from_address',
            'mail_from_name',
        ])->pluck('value', 'key');

        Config::set('mail.mailers.smtp.host', $settings['mail_host'] ?? 'smtp-relay.brevo.com');
        Config::set('mail.mailers.smtp.port', $settings['mail_port'] ?? 587);
        Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption'] ?? 'tls');
        Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?? '');
        Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?? '');
        Config::set('mail.from.address', $settings['mail_from_address'] ?? 'no-reply@mowsengine.local');
        Config::set('mail.from.name', $settings['mail_from_name'] ?? 'MOWS Engine');
    }

    /**
     * Send an email with the custom subject prefix applied (if Email channel is enabled).
     */
    public static function sendEmail(string $to, string $subject, string $view = 'emails.notification', array $data = []): bool
    {
        if (!self::isChannelEnabled('email') || empty($to)) {
            return false;
        }

        self::applyMailConfig();

        $prefix = Setting::where('key', 'email_subject_prefix')->value('value') ?? '[MOWS Engine]';
        $fullSubject = trim($prefix . ' ' . $subject);

        Mail::to($to)->send(new MowsNotificationMail($fullSubject, $view, $data));

        return true;
    }

    /**
     * Send an automated on-site Private Message alert (if PM channel is enabled).
     */
    public static function sendPm(?User $sender, iterable $recipients, string $body, string $subject = 'System Notification'): ?Thread
    {
        if (!self::isChannelEnabled('pm')) {
            return null;
        }

        return Thread::createSystemMessage($sender, $recipients, $body, $subject);
    }

    /**
     * Send a webhook notification to Discord (if Discord channel is enabled).
     */
    public static function sendDiscord(string $type, string $content, array $embeds = [])
    {
        if (!self::isChannelEnabled('discord')) {
            return false;
        }

        $webhookKey = match ($type) {
            'chapters' => 'discord_webhook_chapters',
            'news'     => 'discord_webhook_news',
            default    => 'discord_webhook_alerts',
        };

        $url = Setting::where('key', $webhookKey)->value('value');

        if (empty($url)) {
            return false;
        }

        $payload = ['content' => $content];
        if (!empty($embeds)) {
            $payload['embeds'] = $embeds;
        }

        return Http::post($url, $payload);
    }
}
