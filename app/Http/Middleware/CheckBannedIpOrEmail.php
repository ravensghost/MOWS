<?php

namespace App\Http\Middleware;

use App\Models\Configuration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckBannedIpOrEmail
{
    /**
     * Validate incoming registration and candidate petition requests against banned_emails_and_ips.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawList = $this->resolveBannedList();

        if ($rawList === '') {
            return $next($request);
        }

        $entries = $this->parseEntries($rawList);

        if ($entries === []) {
            return $next($request);
        }

        $clientIp = (string) ($request->ip() ?? '');
        $submittedEmail = strtolower(trim((string) (
            $request->input('email')
            ?? $request->input('applicant_email')
            ?? $request->user()?->email
            ?? ''
        )));

        if ($this->isMatched($clientIp, $submittedEmail, $entries)) {
            $message = function_exists('lexicon')
                ? lexicon(
                    'error_Security_BannedIpOrEmail',
                    'Access denied. Your IP address or email domain has been restricted by the administrator.'
                )
                : 'Access denied. Your IP address or email domain has been restricted by the administrator.';

            if ($request->expectsJson()) {
                return response()->json([
                    'status'   => 'error',
                    'message'  => $message,
                    'data'     => null,
                    'redirect' => null,
                ], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }

    /**
     * Dynamically fetch banned_emails_and_ips from configurations.
     */
    protected function resolveBannedList(): string
    {
        try {
            return (string) (Configuration::query()
                ->where('configuration_token', 'banned_emails_and_ips')
                ->value('configuration_payload') ?? '');
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Normalize a newline, comma, or semicolon-delimited string into lowercase tokens.
     *
     * @return array<int, string>
     */
    protected function parseEntries(string $rawList): array
    {
        $decoded = json_decode($rawList, true);

        if (is_array($decoded)) {
            $tokens = array_map(static fn ($item): string => strtolower(trim((string) $item)), $decoded);

            return array_values(array_filter($tokens, static fn (string $item): bool => $item !== ''));
        }

        $tokens = preg_split('/[\r\n,;]+/', $rawList) ?: [];
        $tokens = array_map(static fn (string $item): string => strtolower(trim($item)), $tokens);

        return array_values(array_filter($tokens, static fn (string $item): bool => $item !== ''));
    }

    /**
     * Determine whether the client IP or email matches any blacklist rule.
     *
     * @param  array<int, string>  $entries
     */
    protected function isMatched(string $clientIp, string $email, array $entries): bool
    {
        $normalizedIp = strtolower(trim($clientIp));

        foreach ($entries as $rule) {
            // 1. Exact IP or wildcard IP prefix match (e.g., 203.0.113.*)
            if ($normalizedIp !== '') {
                if ($rule === $normalizedIp) {
                    return true;
                }

                if (str_contains($rule, '*') && ! str_contains($rule, '@') && fnmatch($rule, $normalizedIp)) {
                    return true;
                }
            }

            // 2. Exact email, @domain.tld, or wildcard email match
            if ($email !== '') {
                if ($rule === $email) {
                    return true;
                }

                if (str_starts_with($rule, '@') && str_ends_with($email, $rule)) {
                    return true;
                }

                if (str_contains($rule, '*') && fnmatch($rule, $email)) {
                    return true;
                }
            }
        }

        return false;
    }
}