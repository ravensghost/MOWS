<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class PhpMailTransport extends AbstractTransport
{
    /**
     * Dispatch the email using PHP's native mail() function.
     */
    protected function doSend(SentMessage $message): void
    {
        $originalMessage = $message->getOriginalMessage();

        try {
            $email = MessageConverter::toEmail($originalMessage);
        } catch (\Throwable $e) {
            $email = $originalMessage instanceof Email ? $originalMessage : null;
        }

        // Extract recipient(s)
        if ($email instanceof Email && count($email->getTo()) > 0) {
            $toAddresses = array_map(
                fn(Address $address) => $address->toString(),
                $email->getTo()
            );
            $to = implode(', ', $toAddresses);
        } else {
            $envelopeRecipients = array_map(
                fn(Address $address) => $address->toString(),
                $message->getEnvelope()->getRecipients()
            );
            $to = implode(', ', $envelopeRecipients);
        }

        // Extract subject
        $subject = $email instanceof Email ? (string) $email->getSubject() : '';

        // Prepare standard MIME headers
        $headers = [
            'MIME-Version: 1.0',
        ];

        if ($email instanceof Email) {
            $from = $email->getFrom();
            if (!empty($from)) {
                $headers[] = 'From: ' . $from[0]->toString();
            }

            $replyTo = $email->getReplyTo();
            if (!empty($replyTo)) {
                $headers[] = 'Reply-To: ' . $replyTo[0]->toString();
            }

            $cc = $email->getCc();
            if (!empty($cc)) {
                $headers[] = 'Cc: ' . implode(', ', array_map(fn(Address $a) => $a->toString(), $cc));
            }

            $bcc = $email->getBcc();
            if (!empty($bcc)) {
                $headers[] = 'Bcc: ' . implode(', ', array_map(fn(Address $a) => $a->toString(), $bcc));
            }

            $htmlBody = $email->getHtmlBody();
            $textBody = $email->getTextBody();

            if (!empty($htmlBody) && !empty($textBody)) {
                $boundary = '=_mows_part_' . md5((string) microtime(true));
                $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

                $body = "--{$boundary}\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . (is_resource($textBody) ? stream_get_contents($textBody) : (string) $textBody) . "\r\n\r\n"
                    . "--{$boundary}\r\n"
                    . "Content-Type: text/html; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . (is_resource($htmlBody) ? stream_get_contents($htmlBody) : (string) $htmlBody) . "\r\n\r\n"
                    . "--{$boundary}--";
            } elseif (!empty($htmlBody)) {
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $headers[] = 'Content-Transfer-Encoding: 8bit';
                $body = is_resource($htmlBody) ? stream_get_contents($htmlBody) : (string) $htmlBody;
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
                $headers[] = 'Content-Transfer-Encoding: 8bit';
                $body = is_resource($textBody) ? stream_get_contents($textBody) : (string) ($textBody ?? '');
            }
        } else {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $body = $message->toString();
        }

        $headerString = implode("\r\n", $headers);

        if (function_exists('mail') && !empty($to)) {
            @mail($to, $subject, (string) $body, $headerString);
        }
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'phpmail';
    }
}
