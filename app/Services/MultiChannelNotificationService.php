<?php

namespace App\Services;

use App\Mail\MowsNotificationMail;
use App\Models\Configuration;
use App\Models\CorrespondenceThread;
use App\Models\Folio;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class MultiChannelNotificationService
{
    /**
     * Check whether a specific notification channel is enabled in Global Configurations.
     * Defaults to false ('0') so all notifications remain off until explicitly enabled.
     */
    public static function isChannelEnabled(string $channel): bool
    {
        $key = match (strtolower(trim($channel))) {
            'email'   => 'notifications_email_enabled',
            'discord' => 'notifications_discord_enabled',
            'pm'      => 'notifications_pm_enabled',
            'pbem'    => 'notifications_pbem_group_enabled',
            default   => '',
        };

        if ($key === '') {
            return false;
        }

        $val = Configuration::where('configuration_token', $key)->value('configuration_payload');

        return in_array(strtolower((string) $val), ['1', 'true', 'yes', 'enabled'], true);
    }

    /**
     * Dynamically configure Laravel's mailer using database configurations and return the active transport driver.
     */
    protected static function applyMailConfig(): string
    {
        $settings = Configuration::whereIn('configuration_token', [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_encryption',
            'mail_username',
            'mail_password',
            'mail_from_address',
            'mail_from_name',
        ])->pluck('configuration_payload', 'configuration_token');

        $mailer = strtolower(trim((string) ($settings['mail_mailer'] ?? 'smtp')));
        if (! in_array($mailer, ['smtp', 'phpmail', 'log'], true)) {
            $mailer = 'smtp';
        }

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $settings['mail_from_address'] ?? 'no-reply@mowsengine.local');
        Config::set('mail.from.name', $settings['mail_from_name'] ?? 'MOWS Engine');

        if ($mailer === 'smtp') {
            $encryption = strtolower(trim((string) ($settings['mail_encryption'] ?? 'tls')));
            Config::set('mail.mailers.smtp.host', $settings['mail_host'] ?? 'smtp-relay.brevo.com');
            Config::set('mail.mailers.smtp.port', (int) ($settings['mail_port'] ?? 587));
            Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
            Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?? '');
            Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?? '');
        }

        return $mailer;
    }

    /**
     * Send an email with the custom subject prefix applied (if Email channel is enabled).
     */
    public static function sendEmail(string $to, string $subject, string $view = 'emails.notification', array $data = []): bool
    {
        if (! self::isChannelEnabled('email') || empty($to)) {
            return false;
        }

        $mailer = self::applyMailConfig();
        $prefix = Configuration::where('configuration_token', 'email_subject_prefix')
            ->value('configuration_payload') ?? '[MOWS Engine]';
        $fullSubject = trim($prefix . ' ' . $subject);

        Mail::mailer($mailer)->to($to)->send(new MowsNotificationMail($fullSubject, $view, $data));

        return true;
    }

    /**
     * Send an automated on-site Correspondence Thread alert (if PM channel is enabled).
     */
    public static function sendPm(
        ?User $sender,
        iterable $recipients,
        string $body,
        string $subject = 'System Notification'
    ): ?CorrespondenceThread {
        if (! self::isChannelEnabled('pm')) {
            return null;
        }

        return CorrespondenceThread::createSystemMessage($sender, $recipients, $body, $subject);
    }

    /**
     * Send a webhook notification to Discord (if Discord channel is enabled).
     */
    public static function sendDiscord(string $type, string $content, array $embeds = []): mixed
    {
        if (! self::isChannelEnabled('discord')) {
            return false;
        }

        $webhookKey = match (strtolower(trim($type))) {
            'folios', 'chapters'  => 'discord_webhook_chapters',
            'bulletins', 'news'   => 'discord_webhook_news',
            default               => 'discord_webhook_alerts',
        };

        $url = Configuration::where('configuration_token', $webhookKey)->value('configuration_payload');

        if (empty($url)) {
            return false;
        }

        $payload = ['content' => $content];
        if (! empty($embeds)) {
            $payload['embeds'] = $embeds;
        }

        return Http::post((string) $url, $payload);
    }

    /**
     * Dispatch a published Folio or Bulletin to the configured Google Groups / PBEM mailing list.
     */
    public static function sendPbemDispatch(string $subject, string $htmlContent, array $metadata = []): bool
    {
        if (! self::isChannelEnabled('pbem')) {
            return false;
        }

        $mailingListAddress = Configuration::where('configuration_token', 'pbem_mailing_list_address')
            ->value('configuration_payload');

        if (empty($mailingListAddress)) {
            return false;
        }

        $mailer = self::applyMailConfig();
        $prefix = Configuration::where('configuration_token', 'email_subject_prefix')
            ->value('configuration_payload') ?? '[MOWS Engine]';
        $fullSubject = trim($prefix . ' [PBEM] ' . $subject);

        $plainAsciiBody = wordwrap(trim(html_entity_decode(strip_tags($htmlContent))), 76, "\r\n");

        Mail::mailer($mailer)->to((string) $mailingListAddress)->send(
            new MowsNotificationMail($fullSubject, 'emails.notification', array_merge($metadata, [
                'title'      => $subject,
                'body'       => $htmlContent,
                'plain_text' => $plainAsciiBody,
            ]))
        );

        return true;
    }

    /**
     * Notify the next tagged co-writer when a Folio turn advances or is locked.
     */
    public static function notifyCoWriterTurn(Folio $folio, ?User $sender, User $nextWriter): void
    {
        $title = $folio->folio_title ?: ('Folio #' . $folio->id);
        $message = "You are next in the writing rotation for '{$title}'.";

        self::sendPm($sender, [$nextWriter], $message, "Co-Writing Turn: {$title}");
        self::sendEmail($nextWriter->email, "Your Co-Writing Turn: {$title}", 'emails.notification', [
            'title' => "Co-Writing Turn: {$title}",
            'body'  => $message,
            'folio' => $folio,
        ]);
    }
}