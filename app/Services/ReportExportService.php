<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class ReportExportService
{
    /**
     * Export bookings to CSV
     */
    public function exportBookingsCSV($startDate = null, $endDate = null, $status = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        $query = Booking::with(['client', 'provider', 'listing'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Booking ID',
            'Client Name',
            'Client Email',
            'Provider Name',
            'Provider Email',
            'Service',
            'Booking Date',
            'Start Time',
            'End Time',
            'Hours',
            'Total Amount',
            'Status',
            'Created At',
        ];

        foreach ($bookings as $booking) {
            $csvData[] = [
                $booking->id,
                $booking->client->first_name . ' ' . $booking->client->last_name,
                $booking->client->email,
                $booking->provider->first_name . ' ' . $booking->provider->last_name,
                $booking->provider->email,
                $booking->listing->title ?? 'N/A',
                $booking->booking_date->format('Y-m-d'),
                $booking->start_time,
                $booking->end_time,
                $booking->total_hours,
                $booking->total_amount,
                $booking->status,
                $booking->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $this->generateCSV($csvData, 'bookings_report_' . now()->format('Y-m-d'));
    }

    /**
     * Export payments to CSV
     */
    public function exportPaymentsCSV($startDate = null, $endDate = null, $status = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        $query = Payment::with(['booking.client', 'booking.provider'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Payment ID',
            'Booking ID',
            'Client Name',
            'Provider Name',
            'Amount',
            'Platform Fee',
            'Provider Amount',
            'Payment Method',
            'Transaction ID',
            'Status',
            'Payment Date',
            'Created At',
        ];

        foreach ($payments as $payment) {
            $csvData[] = [
                $payment->id,
                $payment->booking_id,
                $payment->booking->client->first_name . ' ' . $payment->booking->client->last_name,
                $payment->booking->provider->first_name . ' ' . $payment->booking->provider->last_name,
                $payment->amount,
                $payment->platform_fee,
                $payment->provider_amount,
                $payment->payment_method,
                $payment->transaction_id,
                $payment->status,
                $payment->payment_date ? $payment->payment_date->format('Y-m-d H:i:s') : 'N/A',
                $payment->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $this->generateCSV($csvData, 'payments_report_' . now()->format('Y-m-d'));
    }

    /**
     * Export reviews to CSV
     */
    public function exportReviewsCSV($startDate = null, $endDate = null, $status = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        $query = Review::with(['client', 'provider', 'booking', 'listing'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        $reviews = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Review ID',
            'Booking ID',
            'Client Name',
            'Provider Name',
            'Service',
            'Rating',
            'Comment',
            'Provider Response',
            'Status',
            'Created At',
        ];

        foreach ($reviews as $review) {
            $csvData[] = [
                $review->id,
                $review->booking_id,
                $review->client->first_name . ' ' . $review->client->last_name,
                $review->provider->first_name . ' ' . $review->provider->last_name,
                $review->listing->title ?? 'N/A',
                $review->rating,
                $review->comment ?? '',
                $review->provider_response ?? '',
                $review->status,
                $review->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $this->generateCSV($csvData, 'reviews_report_' . now()->format('Y-m-d'));
    }

    /**
     * Export users to CSV
     */
    public function exportUsersCSV($startDate = null, $endDate = null, $userType = null)
    {
        $query = User::query();

        if ($startDate && $endDate) {
            $startDate = Carbon::parse($startDate);
            $endDate = Carbon::parse($endDate);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($userType) {
            $query->where('user_type', $userType);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'User ID',
            'Name',
            'Email',
            'User Type',
            'Phone',
            'Is Verified',
            'Average Rating',
            'Reviews Count',
            'Subscription Status',
            'Created At',
        ];

        foreach ($users as $user) {
            $csvData[] = [
                $user->id,
                $user->first_name . ' ' . $user->last_name,
                $user->email,
                $user->user_type,
                $user->phone ?? 'N/A',
                $user->is_verified ? 'Yes' : 'No',
                $user->average_rating ?? 0,
                $user->reviews_count ?? 0,
                $user->subscription_status ?? 'none',
                $user->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $this->generateCSV($csvData, 'users_report_' . now()->format('Y-m-d'));
    }

    /**
     * Export revenue summary to CSV
     */
    public function exportRevenueSummaryCSV($startDate, $endDate, $groupBy = 'day')
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $dateFormat = match($groupBy) {
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d',
        };

        $payments = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, 
                         COUNT(*) as transaction_count,
                         SUM(amount) as total_amount,
                         SUM(platform_fee) as total_platform_fee,
                         SUM(provider_amount) as total_provider_amount")
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        $csvData = [];
        $csvData[] = [
            'Period',
            'Transaction Count',
            'Total Amount',
            'Platform Fee',
            'Provider Amount',
        ];

        foreach ($payments as $payment) {
            $csvData[] = [
                $payment->period,
                $payment->transaction_count,
                number_format($payment->total_amount, 2),
                number_format($payment->total_platform_fee, 2),
                number_format($payment->total_provider_amount, 2),
            ];
        }

        return $this->generateCSV($csvData, 'revenue_summary_' . now()->format('Y-m-d'));
    }

    /**
     * Generate CSV file and return download response
     */
    protected function generateCSV($data, $filename)
    {
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Generate custom report CSV
     */
    public function generateCustomReport($reportType, $filters = [])
    {
        switch ($reportType) {
            case 'bookings':
                return $this->exportBookingsCSV(
                    $filters['start_date'] ?? null,
                    $filters['end_date'] ?? null,
                    $filters['status'] ?? null
                );
            
            case 'payments':
                return $this->exportPaymentsCSV(
                    $filters['start_date'] ?? null,
                    $filters['end_date'] ?? null,
                    $filters['status'] ?? null
                );
            
            case 'reviews':
                return $this->exportReviewsCSV(
                    $filters['start_date'] ?? null,
                    $filters['end_date'] ?? null,
                    $filters['status'] ?? null
                );
            
            case 'users':
                return $this->exportUsersCSV(
                    $filters['start_date'] ?? null,
                    $filters['end_date'] ?? null,
                    $filters['user_type'] ?? null
                );
            
            case 'revenue_summary':
                return $this->exportRevenueSummaryCSV(
                    $filters['start_date'] ?? now()->subDays(30),
                    $filters['end_date'] ?? now(),
                    $filters['group_by'] ?? 'day'
                );
            
            default:
                throw new \Exception('Invalid report type');
        }
    }
}