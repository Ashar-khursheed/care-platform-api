@extends('emails.layout')

@section('content')
    <h2>Congratulations! Your Bid Was Accepted</h2>
    <p>Hi {{ $bid->provider->first_name }},</p>
    <p>Your bid for <strong>{{ $bid->listing->title }}</strong> has been accepted by the client.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>Bid Details:</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Amount:</strong> {{ config('app.currency', '$') }}{{ number_format($bid->amount, 2) }}</li>
            <li><strong>Status:</strong> Accepted</li>
        </ul>
    </div>

    <p>A booking has been automatically created for you. You can now start communicating with the client to finalize the details.</p>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/provider/bookings" class="button">View My Bookings</a>
    </p>

    <p>Thanks,<br>{{ config('app.name') }} Team</p>
@endsection

