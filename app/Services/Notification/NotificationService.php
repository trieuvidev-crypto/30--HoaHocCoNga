<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use App\Services\Realtime\RealtimeBroadcastService;

/**
 * Central place every module calls to notify a user — Course, Order,
 * Forum, etc. should never call MailerService or write to the
 * `notifications` table directly; they call this service, which
 * respects the user's channel preferences (see NOTIFICATION_SYSTEM.md
 * §User Preferences) and records delivery status for the admin
 * Notification Center to audit.
 *
 * Templates are simple {{variable}} substitution — deliberately not a
 * templating engine, since email bodies here are short and the project
 * has no dependency on a third-party templating library.
 */
final class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly UserRepository $users,
        private readonly MailerService $mailer,
        private readonly RealtimeBroadcastService $realtime
    ) {
    }

    /**
     * @param array<string, string> $variables substituted into {{key}} placeholders
     */
    public function notify(
        int $userId,
        string $category,
        string $keyName,
        string $title,
        string $body,
        ?string $actionUrl = null,
        array $variables = [],
        string $priority = 'normal'
    ): void {
        $resolvedTitle = $this->interpolate($title, $variables);
        $resolvedBody = $this->interpolate($body, $variables);

        $preference = $this->notifications->getPreference($userId, $category);
        $inAppEnabled = $preference === null || (int) $preference['in_app_enabled'] === 1;
        $emailEnabled = $preference === null || (int) $preference['email_enabled'] === 1;

        if ($inAppEnabled) {
            $notification = $this->notifications->create($userId, $category, $priority, $resolvedTitle, $resolvedBody, $actionUrl);
            $this->notifications->logDelivery($userId, 'in_app', $keyName, 'sent');

            $user = $this->users->findById($userId);

            if ($user !== null) {
                $this->realtime->toUser($user['uuid'], 'notification.created', [
                    'uuid' => $notification['uuid'],
                    'title' => $resolvedTitle,
                    'body' => $resolvedBody,
                    'category' => $category,
                    'priority' => $priority,
                    'action_url' => $actionUrl,
                ]);
            }
        }

        if ($emailEnabled) {
            $user = $this->users->findById($userId);

            if ($user !== null) {
                $sent = $this->mailer->send($user['email'], $resolvedTitle, $this->wrapEmailHtml($resolvedBody, $actionUrl));
                $this->notifications->logDelivery($userId, 'email', $keyName, $sent ? 'sent' : 'failed');
            }
        }
    }

    /**
     * Convenience wrapper for the one email-only case that predates a
     * user account existing in a fully "logged in" sense (password
     * reset — the recipient is known by email, not by an authenticated
     * session), so it bypasses the in-app/preference logic entirely.
     */
    public function sendTransactionalEmail(string $toEmail, string $subject, string $htmlBody): bool
    {
        return $this->mailer->send($toEmail, $subject, $htmlBody);
    }

    /** @param array<string, string> $variables */
    private function interpolate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    private function wrapEmailHtml(string $body, ?string $actionUrl): string
    {
        $safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        $button = $actionUrl !== null
            ? '<p><a href="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:10px 20px;background:#2563EB;color:#fff;border-radius:8px;text-decoration:none;">Xem chi tiết</a></p>'
            : '';

        return <<<HTML
            <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;">
                <p style="color:#2563EB;font-weight:bold;font-size:18px;">HoaHocCoNga.Com</p>
                <p>{$safeBody}</p>
                {$button}
                <p style="color:#6b7280;font-size:12px;">Đây là email tự động, vui lòng không phản hồi trực tiếp email này.</p>
            </div>
            HTML;
    }
}
