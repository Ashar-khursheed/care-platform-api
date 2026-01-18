<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Care Platform API",
 *     description="Complete API documentation for Care Platform - A comprehensive marketplace connecting clients with care providers. Features include user authentication, service listings, booking management, payments, reviews, messaging, and admin dashboard.",
 *     @OA\Contact(
 *         name="Care Platform API Support",
 *         email="support@careplatform.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT Bearer token"
 * )
 * 
 * @OA\Tag(name="Authentication", description="User authentication endpoints")
 * @OA\Tag(name="Profile", description="User profile management")
 * @OA\Tag(name="Categories", description="Service categories")
 * @OA\Tag(name="Listings", description="Service listings")
 * @OA\Tag(name="Bookings", description="Booking management")
 * @OA\Tag(name="Reviews", description="Review system")
 * @OA\Tag(name="Payments", description="Payment processing")
 * @OA\Tag(name="Payouts", description="Payout management")
 * @OA\Tag(name="Messages", description="Messaging system")
 * @OA\Tag(name="Notifications", description="Notifications")
 * @OA\Tag(name="Subscriptions", description="Subscription plans")
 * @OA\Tag(name="Analytics", description="Analytics")
 * @OA\Tag(name="CMS", description="Content management")
 * @OA\Tag(name="Job Applications", description="Job applications")
 * @OA\Tag(name="Mobile", description="Mobile endpoints")
 * @OA\Tag(name="Admin - Users", description="Admin user management")
 * @OA\Tag(name="Admin - Documents", description="Admin documents")
 * @OA\Tag(name="Admin - Listings", description="Admin listings")
 * @OA\Tag(name="Admin - Bookings", description="Admin bookings")
 * @OA\Tag(name="Admin - Reviews", description="Admin reviews")
 * @OA\Tag(name="Admin - Payments", description="Admin payments")
 * @OA\Tag(name="Admin - Payouts", description="Admin payouts")
 * @OA\Tag(name="Admin - Messages", description="Admin messages")
 * @OA\Tag(name="Admin - Notifications", description="Admin notifications")
 * @OA\Tag(name="Admin - Subscriptions", description="Admin subscriptions")
 * @OA\Tag(name="Admin - Analytics", description="Admin analytics")
 * @OA\Tag(name="Admin - CMS", description="Admin CMS")
 * @OA\Tag(name="Admin - Job Applications", description="Admin job applications")
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="user_type", type="string", enum={"client", "provider"}, example="provider"),
 *     @OA\Property(property="phone", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error message")
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(property="errors", type="object")
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}