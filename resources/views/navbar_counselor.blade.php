@extends('layouts.app')

@section('content')
    <style>
        .bottom-nav {
            background: blue;
            border-radius: 20px 20px 0 0;
            padding: 10px;
        }

        .bottom-nav ul {
            display: flex;
            justify-content: space-around;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .bottom-nav a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 10px;
        }

        .bottom-nav a.active {
            color: darkblue;
            font-weight: bold;
        }
    </style>

    <div class="container">
        <div class="screen">
            @if ($selectedIndex == 0)
                @include('chat_counselor') <!-- Ensure this view exists -->
            @elseif ($selectedIndex == 1)
                @include('profile_counselor') <!-- Ensure this view exists -->
            @endif
        </div>

        <nav class="bottom-nav">
            <ul>
                <li>
                    <a href="{{ route('navbar_counselor', ['selectedIndex' => 0]) }}" class="{{ $selectedIndex == 0 ? 'active' : '' }}">
                        <i class="icon-chat"></i> Chat
                    </a>
                </li>
                <li>
                    <a href="{{ route('navbar_counselor', ['selectedIndex' => 1]) }}" class="{{ $selectedIndex == 1 ? 'active' : '' }}">
                        <i class="icon-profile"></i> Profile
                    </a>
                </li>
            </ul>
        </nav>
    </div>
@endsection