<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\TaskListRepo;

final class TaskListController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $this->currentUserId();
        return $this->view('app/lists-index', [
            'title'      => 'আমার Lists',
            'lists'      => (new TaskListRepo())->forUser($userId),
        ]);
    }

    public function store(Request $request): Response
    {
        $userId = (int) $this->currentUserId();

        $v = Validator::make($request->body(), [
            'name'      => 'required|max:100',
            'color_hex' => 'hex_color',
        ], ['name' => 'নাম']);

        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/app/lists');
        }

        (new TaskListRepo())->create($userId, $v->get('name'), $v->get('color_hex', '#2E3A87'));
        Session::notify('success', 'নতুন List তৈরি হয়েছে।');
        return $this->redirect('/app/lists');
    }

    public function update(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        $v = Validator::make($request->body(), [
            'name'      => 'required|max:100',
            'color_hex' => 'hex_color',
        ], ['name' => 'নাম']);

        if ($v->fails()) {
            $v->flash();
            return $this->redirect('/app/lists');
        }

        (new TaskListRepo())->update((int) $id, $userId, $v->get('name'), $v->get('color_hex', '#2E3A87'));
        Session::notify('success', 'List Update হয়েছে।');
        return $this->redirect('/app/lists');
    }

    public function destroy(Request $request, string $id): Response
    {
        $userId = (int) $this->currentUserId();
        (new TaskListRepo())->delete((int) $id, $userId);
        Session::notify('success', 'List মুছে ফেলা হয়েছে।');
        return $this->redirect('/app/lists');
    }
}
