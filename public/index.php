<?php
declare(strict_types=1);

/**
 * Front controller — the only PHP file the web server ever executes.
 *
 * On cPanel, if public/ cannot be symlinked as the docroot, copy the contents
 * of public/ into public_html/ and point APP_ROOT at the application folder:
 *   define('APP_ROOT', '/home/USER/dinsathi');
 */

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\HttpException;
use App\Middleware\SecurityHeaders;

$request = Request::capture();

try {
    Session::start($request);

    View::share('currentPath', $request->path);
    View::share('notice', Session::notice());
    View::share('theme', ($_COOKIE['dinsathi_theme'] ?? '') === 'dark' ? 'dark' : 'light');

    $router   = new Router(require APP_ROOT . '/app/routes.php');
    $response = $router->dispatch($request);
} catch (HttpException $e) {
    $response = renderError($e->status, $e->getMessage(), $e->context);
} catch (PDOException $e) {
    App\Core\Logger::exception($e);
    $response = Response::html(View::render('errors/500'), 500);
} catch (Throwable $e) {
    App\Core\Logger::exception($e);

    if (config('app.debug')) {
        throw $e;   // the bootstrap exception handler prints the trace
    }
    $response = Response::html(View::render('errors/500'), 500);
}

// SecurityHeaders is global — applied to every response, including errors.
(new SecurityHeaders())->handle($request, static fn(): Response => $response)->send();

/** Map an HTTP status onto its error view. */
function renderError(int $status, string $message, array $context = []): Response
{
    $view = match ($status) {
        403     => 'errors/403',
        404     => 'errors/404',
        419     => 'errors/419',
        429     => 'errors/429',
        405     => 'errors/404',
        default => 'errors/500',
    };

    return Response::html(View::render($view, [
        'message'    => $message,
        'retryAfter' => $context['retry_after'] ?? null,
    ]), $status === 405 ? 404 : $status);
}
