<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function support()
    {
        return view('help.support');
    }

    public function submitTicket(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string',
            'message' => 'required|string',
        ]);

        return back()->with('success', 'Support ticket submitted! Our dedicated agency engineer will reply via Telegram or email within 1 hour.');
    }

    public function faq()
    {
        return view('help.faq');
    }
}
