<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class NavbarCounselorController extends Controller
{
public function index(Request $request)
{
    $selectedIndex = $request->input('selectedIndex', 0);
    return view('navbar_counselor', compact('selectedIndex'));
}
}