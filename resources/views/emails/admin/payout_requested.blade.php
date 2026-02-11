@extends('emails.layout')

@section('content')
    <h2>New Payout Request</h2>
    <p>A provider has requested a payout for their earnings.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>Payout Details:</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Provider:</strong> {{ $payout->provider->first_name }} {{ $payout->provider->last_name }}</li>
            <li><strong>Amount:</strong> {{ config('app.currency', '$') }}{{ number_format($payout->amount, 2) }}</li>
            <li><strong>Bank Info:</strong> {{ $payout->bank_name }} (Ending in {{ $payout->account_number_last4 }})</li>
        </ul>
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/admin/payouts/{{ $payout->id }}" class="button">Review Payout Request</a>
    </p>

    <p>Thanks,<br>{{ config('app.name') }} System</p>
@endsection

