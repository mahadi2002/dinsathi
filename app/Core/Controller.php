<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

/** Thin base for every controller — rendering, redirecting, common lookups. */
abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($template, $data), $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }

    protected function back(Request $request, string $fallback = '/'): Response
    {
        $ref  = (string) $request->header('referer', '');
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === $host) {
            return Response::redirect($ref);
        }
        return Response::redirect($fallback);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /** IDOR and missing rows both end here — never confirm existence either way. */
    protected function notFound(): never
    {
        throw new HttpException(404);
    }

    protected function currentUserId(): ?int
    {
        return Session::userId();
    }
}
