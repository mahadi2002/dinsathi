<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\ContactMessageRepo;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home/index', [
            'title'       => null,
            'description' => 'দৈনন্দিন কাজ, রিমাইন্ডার আর অভ্যাস — সব একসাথে, একটাই জায়গায়। দিনসাথী Push Notification দিয়ে সময়মতো মনে করিয়ে দেয়।',
        ]);
    }

    public function privacy(Request $request): Response
    {
        return $this->view('home/privacy', ['title' => 'Privacy Policy']);
    }

    public function terms(Request $request): Response
    {
        return $this->view('home/terms', ['title' => 'Terms & Conditions']);
    }

    public function contact(Request $request): Response
    {
        return $this->view('home/contact', ['title' => 'Contact Us', 'formStartedAt' => time()]);
    }

    public function submitContact(Request $request): Response
    {
        // Honeypot + minimum fill time instead of a CAPTCHA — a bot fills
        // every field instantly; a human takes at least ~2 seconds.
        if ($request->str('website') !== '' || (time() - $request->int('started_at')) < 2) {
            return $this->redirect('/contact');
        }

        $v = Validator::make($request->body(), [
            'name'    => 'required|min:2|max:100',
            'contact' => 'required|min:5|max:100',
            'message' => 'required|min:5|max:1000',
        ], ['name' => 'নাম', 'contact' => 'যোগাযোগের তথ্য', 'message' => 'বার্তা']);

        if ($v->fails()) {
            $v->flash();
            return $this->back($request, '/contact');
        }

        (new ContactMessageRepo())->create($v->get('name'), $v->get('contact'), $v->get('message'));
        Session::notify('success', 'বার্তা পাঠানো হয়েছে। ধন্যবাদ!');

        return $this->redirect('/contact');
    }
}
