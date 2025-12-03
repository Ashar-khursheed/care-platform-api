# Care Platform API - Quick Testing Guide

## Authentication

### 1. Register as Client
```bash
POST /api/v1/auth/register
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "client@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890",
  "user_type": "client"
}
```

### 2. Login
```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "client@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "1|xxxxxxxxxxxxxx"
  }
}
```

**Save this token** - you'll need it for authenticated requests!

---

## Testing Booking Creation

### Prerequisites:
1. You must be logged in as a **client**
2. You need a valid **listing_id** (create one or use existing)

### Create Booking
```bash
POST /api/v1/bookings
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json

{
  "listing_id": 1,
  "booking_date": "2024-12-15",
  "start_time": "10:00",
  "end_time": "14:00",
  "service_location": "123 Main Street, City, State 12345",
  "special_requirements": "Please bring cleaning supplies"
}
```

### Expected Success Response (201):
```json
{
  "success": true,
  "message": "Booking request sent successfully",
  "data": {
    "id": 1,
    "booking_date": "2024-12-15",
    "start_time": "10:00",
    "end_time": "14:00",
    "hours": 4,
    "hourly_rate": "25.00",
    "total_amount": "100.00",
    "status": "pending",
    "service_location": "123 Main Street, City, State 12345",
    "special_requirements": "Please bring cleaning supplies",
    "client": {...},
    "provider": {...},
    "listing": {...}
  }
}
```

### Common Errors:

**401 Unauthorized:**
- Token missing or invalid
- Token expired

**403 Forbidden:**
- User is not a client (providers cannot create bookings)

**400 Bad Request:**
- Service listing is not available
- Listing does not exist

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "booking_date": ["Booking date must be today or in the future."],
    "end_time": ["Booking must be at least 1 hour."]
  }
}
```

---

## Testing Review Creation

### Prerequisites:
1. You must be logged in as a **client**
2. You need a **completed booking** (booking status must be "completed")
3. You can only review each booking once

### Create Review
```bash
POST /api/v1/reviews
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json

{
  "booking_id": 1,
  "rating": 5,
  "comment": "Excellent service! Very professional and thorough."
}
```

### Expected Success Response (201):
```json
{
  "data": {
    "id": 1,
    "booking_id": 1,
    "client_id": 2,
    "provider_id": 3,
    "listing_id": 1,
    "rating": 5,
    "comment": "Excellent service! Very professional and thorough.",
    "status": "approved",
    "is_flagged": false,
    "helpful_count": 0,
    "created_at": "2024-12-03T10:30:00.000000Z",
    "client": {...},
    "provider": {...},
    "listing": {...},
    "booking": {...}
  }
}
```

### Common Errors:

**401 Unauthorized:**
- Token missing or invalid

**403 Forbidden:**
- User is not a client

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "booking_id": ["This booking does not belong to you."],
    "booking_id": ["You can only review completed bookings."],
    "booking_id": ["You have already reviewed this booking."]
  }
}
```

---

## Get Your Bookings

```bash
GET /api/v1/bookings
Authorization: Bearer YOUR_TOKEN_HERE
```

**Query Parameters:**
- `status` - Filter by status (pending, accepted, in_progress, completed, cancelled, rejected)
- `type` - Filter by type (upcoming, past)
- `per_page` - Number per page (default: 10)
- `page` - Page number

---

## Get Your Reviews

```bash
GET /api/v1/reviews/my-reviews
Authorization: Bearer YOUR_TOKEN_HERE
```

**Query Parameters:**
- `status` - Filter by status (pending, approved, rejected)
- `per_page` - Number per page (default: 10)
- `page` - Page number

---

## Postman Collection Tips

1. **Set up Environment Variables:**
   - `base_url`: http://your-domain.com/api/v1
   - `token`: (will be set after login)

2. **Authorization:**
   - Type: Bearer Token
   - Token: {{token}}

3. **Create a Login Request First:**
   - Save the token from response to environment variable
   - Use this token in all subsequent requests

---

## Troubleshooting

### "Unauthenticated" Error
- Check if token is included in Authorization header
- Format: `Authorization: Bearer YOUR_TOKEN_HERE`
- Make sure token hasn't expired

### "Unauthorized" Error
- Check user type (must be client for creating bookings/reviews)
- Verify you're logged in with correct account

### "Service listing is not available"
- Listing status must be 'active'
- Listing is_available must be true
- Check if listing_id exists

### "Booking cannot be reviewed"
- Booking status must be 'completed'
- Can't review bookings that aren't finished
- Provider must have completed the booking first

---

## Testing Workflow

1. **Register as Provider** → Create Service Listings
2. **Register as Client** → Login with client account
3. **Create Booking** → Use client token
4. **Provider Accepts** → Login as provider, accept booking
5. **Provider Completes** → Mark booking as completed
6. **Client Reviews** → Use client token to create review

---

## Status Flow

### Booking Status Flow:
```
pending → accepted → in_progress → completed
   ↓         ↓           ↓
rejected  cancelled  cancelled
```

### Review Status:
- `pending` - Awaiting admin approval (if moderation enabled)
- `approved` - Visible to public (auto-approved by default)
- `rejected` - Admin rejected (not visible)

---

## Additional Resources

- Full API Documentation: Check `routes/api.php` for all endpoints
- Postman Collection: Import and test all endpoints
- Error Codes: Standard HTTP status codes (200, 201, 400, 401, 403, 422, 500)
