<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\HandlesNotifications;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    use HandlesNotifications;

    protected function indexView(): string
    {
        return 'dashboard.notifications';
    }
}
