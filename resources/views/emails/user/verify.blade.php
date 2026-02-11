@extends('emails.layout')

@section('content')
    <h2>Verify Your Email Address</h2>
    <p>Hi {{ $user->first_name }},</p>
    <p>Thank you for signing up for {{ config('app.name') }}! Please click the button below to verify your email address and activate your account.</p>

    <p style="text-align: center;">
        <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
    </p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p style="font-size: 13px; color: #6c757d;">If you're having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your web browser:</p>
        <p style="font-size: 13px; word-break: break-all;"><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>
    </div>

    <p>If you did not create an account, no further action is required.</p>
    <p>Thanks,<br>{{ config('app.name') }} Team</p>
@endsection

