@extends('emails.layout')

@section('content')
    <h2>New Bid Received!</h2>
    <p>Hi {{ $bid->listing->provider->first_name }},</p>
    <p>You have received a new bid for your job: <strong>{{ $bid->listing->title }}</strong>.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>Bid Details:</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Bidder:</strong> {{ $bid->provider->first_name }} {{ $bid->provider->last_name }}</li>
            <li><strong>Amount:</strong> {{ config('app.currency', '$') }}{{ number_format($bid->amount, 2) }}</li>
        </ul>
        @if($bid->message)
            <p><strong>Message from Bidder:</strong><br>
            {{ $bid->message }}</p>
        @endif
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/jobs/{{ $bid->listing->id }}/bids" class="button">View All Bids</a>
    </p>

    <p>Thanks,<br>{{ config('app.name') }} Team</p>
@endsection

