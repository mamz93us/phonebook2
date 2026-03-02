<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries   = 3;

    public function __construct(private Notification $notification) {}

    public function handle(SmtpConfigService $smtp): void
    {
        $user = $this->notification->user;

        if (!$user || empty($user->email)) {
            return;
        }

        $smtp->loadFromSettings();

        $notification = $this->notification;

        Mail::html($this->buildHtml($notification), function ($message) use ($user, $notification) {
            $message->to($user->email, $user->name)
                    ->subject("[SG NOC] {$notification->title}");
        });
    }

    private function buildHtml(Notification $n): string
    {
        $appName = config('app.name', 'SG NOC');
        $link    = $n->link ? "<p><a href=\"{$n->link}\" style=\"color:#0d6efd\">View Details →</a></p>" : '';
        $color   = match ($n->severity) {
            'critical' => '#dc3545',
            'warning'  => '#ffc107',
            default    => '#0dcaf0',
        };

        return "
        <div style='font-family:sans-serif;max-width:600px;margin:0 auto'>
            <div style='background:{$color};padding:16px;border-radius:4px 4px 0 0'>
                <h2 style='color:#fff;margin:0'>{$appName}</h2>
            </div>
            <div style='padding:24px;background:#fff;border:1px solid #dee2e6'>
                <h3 style='margin-top:0'>{$n->title}</h3>
                <p style='color:#495057'>{$n->message}</p>
                {$link}
                <hr style='border-color:#dee2e6'>
                <p style='color:#6c757d;font-size:12px'>
                    This notification was sent by {$appName}.
                    You can manage your notification preferences in your account settings.
                </p>
            </div>
        </div>";
    }
}
