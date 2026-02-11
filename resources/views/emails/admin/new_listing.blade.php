@extends('emails.layout')

@section('content')
    <h2>New Service Listing Created</h2>
    <p>A new service listing has been created and requires review.</p>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>Listing Details:</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Title:</strong> {{ $listing->title }}</li>
            <li><strong>Provider:</strong> {{ $listing->provider->first_name }} {{ $listing->provider->last_name }}</li>
            <li><strong>Category:</strong> {{ $listing->category->name }}</li>
            <li><strong>Hourly Rate:</strong> {{ config('app.currency', '$') }}{{ number_format($listing->hourly_rate, 2) }}</li>
        </ul>
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/admin/listings/{{ $listing->id }}" class="button">Review Listing</a>
    </p>

    <p>Thanks,<br>{{ config('app.name') }} System</p>
@endsection

