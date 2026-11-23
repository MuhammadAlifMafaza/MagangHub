<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendWorkflowNotification
{
    public function handle(
        User $recipient,
        string $event,
        string $title,
        string $message,
        string $actionUrl,
        string $actionLabel,
        bool $sendEmail = true,
    ): void {
        if (! $recipient->is_active) {
            return;
        }

        DB::afterCommit(fn () => Notification::sendNow($recipient, new WorkflowNotification(
            event: $event,
            title: $title,
            message: $message,
            actionUrl: $actionUrl,
            actionLabel: $actionLabel,
            channels: ['database'],
        ), ['database']));

        if ($sendEmail) {
            DB::afterCommit(function () use ($recipient, $event, $title, $message, $actionUrl, $actionLabel): void {
                try {
                    Notification::send($recipient, new WorkflowNotification(
                        event: $event,
                        title: $title,
                        message: $message,
                        actionUrl: $actionUrl,
                        actionLabel: $actionLabel,
                        channels: ['mail'],
                    ));
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
        }
    }
}
