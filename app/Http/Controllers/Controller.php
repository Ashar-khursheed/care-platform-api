<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Care Platform API",
 *     description="Complete API documentation for Care Platform",
 *     @OA\Contact(name="API Support", email="support@careplatform.com")
 * )
 * 
 * @OA\Server(url=L5_SWAGGER_CONST_HOST, description="API Server")
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(name="Authentication", description="Authentication endpoints")
 * @OA\Tag(name="Health", description="Health check")
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="user_type", type="string", example="provider")
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
 * 
 * @OA\Get(
 *     path="/health",
 *     operationId="healthCheck",
 *     tags={"Health"},
 *     summary="Health check endpoint",
 *     description="Check if API is running",
 *     @OA\Response(
 *         response=200,
 *         description="API is healthy",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="healthy"),
 *             @OA\Property(property="timestamp", type="string", example="2024-01-18T10:00:00.000000Z")
 *         )
 *     )
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}