<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;

final class HealthController extends Controller
{
    public function check(Request $request): Response
    {
        try {
            Db::value('SELECT 1');
            return $this->json(['status' => 'ok'], 200);
        } catch (\Throwable) {
            return $this->json(['status' => 'db_unreachable'], 503);
        }
    }
}
