@extends('emails.layout')

@section('content')
    <h2>Service Completed!</h2>
    <p>Hi {{ $booking->client->first_name }},</p>
    <p>Your service <strong>{{ $booking->listing->title }}</strong> has been marked as completed by {{ $booking->provider->first_name }}.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p>We hope you were satisfied with the service! Please take a moment to leave a review for your provider.</p>
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/bookings/{{ $booking->id }}/review" class="button">Leave a Review</a>
    </p>

    <p>Thanks for using {{ config('app.name') }}!</p>
    <p>Thanks,<br>{{ config('app.name') }} Team</p>
@endsection

