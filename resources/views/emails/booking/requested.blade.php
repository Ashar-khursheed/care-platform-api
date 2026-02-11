@extends('emails.layout')

@section('content')
    <h2>New Booking Request</h2>
    <p>Hi {{ $booking->provider->first_name }},</p>
    <p>You have received a new booking request for your service: <strong>{{ $booking->listing->title }}</strong>.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>Booking Details:</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Client:</strong> {{ $booking->client->first_name }} {{ $booking->client->last_name }}</li>
            <li><strong>Date:</strong> {{ $booking->booking_date->format('M d, Y') }}</li>
            <li><strong>Time:</strong> {{ \Carbon\Carbon::parse($booking->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('h:i A') }}</li>
            <li><strong>Location:</strong> {{ $booking->service_location }}</li>
            <li><strong>Total Amount:</strong> {{ config('app.currency', '$') }}{{ number_format($booking->total_amount, 2) }}</li>
        </ul>
    </div>

    @if($booking->special_requirements)
        <p><strong>Special Requirements:</strong><br>
        {{ $booking->special_requirements }}</p>
    @endif

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/provider/bookings/{{ $booking->id }}" class="button">View & Respond to Booking</a>
    </p>

    <p>Please respond to this request as soon as possible to give your client the best experience.</p>
    <p>Thanks,<br>{{ config('app.name') }} Team</p>
@endsection

