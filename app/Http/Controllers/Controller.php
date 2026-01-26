<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Care Platform API",
    version: "1.0.0",
    description: "Complete API documentation for Care Platform - A comprehensive marketplace connecting service providers with customers"
)]
#[OA\Server(url: "http://localhost:8000/api", description: "Local Development Server")]
#[OA\Server(url: "https://careapi.in-sourceit.com/api", description: "Production Server")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Tag(name: "Authentication", description: "User authentication and authorization endpoints")]
#[OA\Tag(name: "Profile", description: "User profile management")]
#[OA\Tag(name: "Categories", description: "Service category management")]
#[OA\Tag(name: "Listings", description: "Service listing operations")]
#[OA\Tag(name: "Bookings", description: "Booking management")]
#[OA\Tag(name: "Reviews", description: "Review and rating system")]
#[OA\Tag(name: "Payments", description: "Payment processing")]
#[OA\Tag(name: "Payouts", description: "Provider payout management")]
#[OA\Tag(name: "Messages", description: "Messaging system")]
#[OA\Tag(name: "Notifications", description: "Notification management")]
#[OA\Tag(name: "Subscriptions", description: "Subscription plan management")]
#[OA\Tag(name: "Analytics", description: "Analytics and reporting")]
#[OA\Tag(name: "CMS", description: "Content Management System")]
#[OA\Tag(name: "Admin - Users", description: "Admin user management")]
#[OA\Tag(name: "Admin - Documents", description: "Admin document verification")]
#[OA\Tag(name: "Admin - Listings", description: "Admin listing management")]
#[OA\Tag(name: "Admin - Bookings", description: "Admin booking management")]
#[OA\Tag(name: "Admin - Reviews", description: "Admin review moderation")]
#[OA\Tag(name: "Admin - Payments", description: "Admin payment management")]
#[OA\Tag(name: "Admin - Payouts", description: "Admin payout management")]
#[OA\Tag(name: "Admin - Messages", description: "Admin message moderation")]
#[OA\Tag(name: "Admin - Notifications", description: "Admin notification management")]
#[OA\Tag(name: "Admin - Subscriptions", description: "Admin subscription management")]
#[OA\Tag(name: "Admin - Analytics", description: "Admin analytics and reports")]
#[OA\Tag(name: "Admin - CMS", description: "Admin CMS management")]
#[OA\Tag(name: "Admin - Job Applications", description: "Admin job application management")]
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
