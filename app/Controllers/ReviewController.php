<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\DailyReviewRepo;
use App\Support\DateBD;
use DateTimeImmutable;

final class ReviewController extends Controller
{
    public function show(Request $request, string $date): Response
    {
        $userId = (int) $this->currentUserId();
        $date   = DateTimeImmutable::createFromFormat('Y-m-d', $date) ? $date : DateBD::today();

        return $this->view('app/review', [
            'title'  => 'Daily Review',
            'date'   => $date,
            'review' => (new DailyReviewRepo())->find($userId, $date),
            'recent' => (new DailyReviewRepo())->recent($userId, 14),
            'isToday' => $date === DateBD::today(),
        ]);
    }

    public function store(Request $request, string $date): Response
    {
        $userId = (int) $this->currentUserId();
        $mood   = $request->str('mood');
        $mood   = in_array($mood, ['great', 'good', 'okay', 'tough'], true) ? $mood : null;

        (new DailyReviewRepo())->upsert($userId, $date, $request->str('note') ?: null, $mood);
        Session::notify('success', 'Review সংরক্ষণ হয়েছে।');

        return $this->redirect('/app/review/' . $date);
    }
}
