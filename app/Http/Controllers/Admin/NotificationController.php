<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesNotifications;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    use HandlesNotifications;

    protected function indexView(): string
    {
        return 'admin.notifications';
    }
}
