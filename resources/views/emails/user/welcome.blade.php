@extends('emails.layout')

@section('content')
    <h2>Welcome to {{ config('app.name') }}!</h2>
    <p>Hi {{ $user->first_name }},</p>
    <p>We're thrilled to have you join our community! {{ config('app.name') }} is here to connect you with the best services and providers.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p>Whether you're looking for professional help or looking to provide your services, we've got you covered.</p>
    </div>

    @if($user->isProvider())
        <p>As a provider, you can start by creating your first service listing to reach thousands of potential clients.</p>
        <p style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/provider/listings/create" class="button">Create Your First Listing</a>
        </p>
    @else
        <p>As a client, you can start by exploring our categories to find the perfect service for your needs.</p>
        <p style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/services" class="button">Explore Services</a>
        </p>
    @endif

    <p>If you have any questions, feel free to reply to this email or visit our Help Center.</p>
    <p>Best regards,<br>{{ config('app.name') }} Team</p>
@endsection

