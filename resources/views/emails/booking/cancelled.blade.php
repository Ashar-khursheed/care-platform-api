@extends('emails.layout')

@section('content')
    <h2>Booking Cancelled</h2>
    <p>Hi,</p>
    <p>The booking for <strong>{{ $booking->listing->title }}</strong> scheduled for <strong>{{ $booking->booking_date->format('M d, Y') }}</strong> has been cancelled.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>Cancellation Details:</h3>
        <p><strong>Cancelled By:</strong> {{ $booking->cancelledByUser->first_name ?? 'System' }}</p>
        @if($booking->cancellation_reason)
            <p><strong>Reason:</strong> {{ $booking->cancellation_reason }}</p>
        @endif
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/bookings/{{ $booking->id }}" class="button">View Booking Details</a>
    </p>

    <p>If this was unexpected, please contact our support team.</p>
    <p>Thanks,<br>{{ config('app.name') }} Team</p>
@endsection

