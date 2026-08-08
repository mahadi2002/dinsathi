<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class AdminContactController extends Controller
{
    /**
     * Contact messages have no dedicated table (out of 02-SCHEMA.sql's scope
     * for this build) — they are appended to storage/logs/contact-*.log.
     * Shown here as a raw tail so support staff have one place to look;
     * promote to a real `contact_messages` table + states the moment volume
     * makes a log file impractical.
     */
    public function index(Request $request): Response
    {
        $file = APP_ROOT . '/storage/logs/contact-' . date('Y-m-d') . '.log';
        $tail = is_file($file) ? implode('', array_slice(file($file) ?: [], -100)) : '';

        return $this->view('admin/contact', ['title' => 'Contact Inbox', 'tail' => $tail]);
    }
}
