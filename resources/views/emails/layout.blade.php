<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #007bff;
            padding: 30px;
            text-align: center;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .content {
            padding: 40px;
            color: #333333;
            line-height: 1.6;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
        }
        .social-links {
            margin-top: 15px;
        }
        .social-links a {
            margin: 0 10px;
            color: #6c757d;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                width: 100%;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @php
                $logo = \App\Models\SiteSetting::get('site_logo');
            @endphp
            @if($logo)
                <img src="{{ $logo }}" alt="{{ config('app.name') }}">
            @else
                <h1 style="color: #ffffff; margin: 0;">{{ config('app.name') }}</h1>
            @endif
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>{{ \App\Models\SiteSetting::get('contact_address', 'Your Address Here') }}</p>
            <div class="social-links">
                @if($fb = \App\Models\SiteSetting::get('social_facebook'))
                    <a href="{{ $fb }}">Facebook</a>
                @endif
                @if($tw = \App\Models\SiteSetting::get('social_twitter'))
                    <a href="{{ $tw }}">Twitter</a>
                @endif
                @if($ig = \App\Models\SiteSetting::get('social_instagram'))
                    <a href="{{ $ig }}">Instagram</a>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
