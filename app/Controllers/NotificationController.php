<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NotificationRepo;

final class NotificationController extends Controller
{
    public function markRead(Request $request): Response
    {
        (new NotificationRepo())->markAllRead((int) $this->currentUserId());
        return $this->json(['ok' => true]);
    }
}
