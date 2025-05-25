<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatCounselorController extends Controller
{
     public function showChatCounselor()
    {
        return view('chat_counselor');
    }
}
