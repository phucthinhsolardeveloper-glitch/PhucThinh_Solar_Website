<?php

namespace App\Domain\Admin;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The bell in the Admin header: one entry per new order, transfer report, enquiry or review, for the staff whose
 * permission covers it. Sent beside the e-mail alert, not instead of it, and never allowed to break the customer's request.
 * No phone numbers or e-mails in the entry: staff open the record for those.
 */
final class AdminBell
{
    /**
     * @param  string  $area  permission area (orders, leads, reviews…); owners always receive
     * @param  string  $key  dedupe key, e.g. "order:12": the same event never rings twice
     */
    /** @param (callable(User): bool)|null $who narrows the receivers further (e.g. only publishers, not the sender) */
    public static function ring(string $area, string $key, string $title, string $body, ?string $url, string $icon = 'heroicon-o-bell', string $color = 'primary', ?callable $who = null): void
    {
        try {
            if (DB::table('notifications')->where('data', 'like', '%"key":"'.addcslashes($key, '%_').'"%')->exists()) {
                return;
            }
            $users = User::query()->where('is_active', true)->get()->filter(fn (User $user) => $user->hasPermission($area) && ($who === null || $who($user)));
            if ($users->isEmpty()) {
                return;
            }
            $notification = Notification::make()->title($title)->body($body)->icon($icon)->iconColor($color)->viewData(['key' => $key]);
            if ($url !== null) {
                $notification->actions([Action::make('open')->label('Mở')->url($url)->markAsRead()]);
            }
            // Written now, not through the queue: the bell must ring even when the mail worker is stuck.
            $message = $notification->toDatabase();
            foreach ($users as $user) {
                $user->notifyNow($message);
            }
        } catch (Throwable $e) {
            Log::warning('Admin bell could not ring', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    public static function money(float|int|string $value): string
    {
        return number_format((float) $value, 0, ',', '.').'đ';
    }
}
