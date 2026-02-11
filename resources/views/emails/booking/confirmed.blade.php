@extends('emails.layout')

@section('content')
    <h2>Booking Confirmed!</h2>
    <p>Hi {{ $booking->client->first_name }},</p>
    <p>Great news! Your booking for <strong>{{ $booking->listing->title }}</strong> has been confirmed by the provider.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>Confirmed Details:</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Provider:</strong> {{ $booking->provider->first_name }} {{ $booking->provider->last_name }}</li>
            <li><strong>Date:</strong> {{ $booking->booking_date->format('M d, Y') }}</li>
            <li><strong>Time:</strong> {{ \Carbon\Carbon::parse($booking->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('h:i A') }}</li>
            <li><strong>Location:</strong> {{ $booking->service_location }}</li>
        </ul>
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/bookings/{{ $booking->id }}" class="button">View Booking Details</a>
    </p>

    <p>If you need to contact your provider, you can do so through the message center on our platform.</p>
    <p>Thanks,<br>{{ config('app.name') }} Team</p>
@endsection

