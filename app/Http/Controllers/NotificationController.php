<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;

class NotificationController extends Controller
{
    public function readAll(): array
    {
        DataRepository::markAllNotificationsRead();
        flash('status', __('notifications.read_all_done', 'Notifications marked as read.'));

        return $this->redirect('dashboard');
    }
}
