# How to Distinguish Provider Listings vs Client View

## 📍 API Endpoint

```
GET /api/v1/listings
```

---

## 🎯 Understanding the Filter

The `user_type` parameter controls **whose listings you see**:

### 🔵 **Provider View** (See My Own Listings)
```bash
GET /api/v1/listings?user_type=provider
Authorization: Bearer {provider_token}
```

**What you get**:
- ✅ Only YOUR own listings
- ✅ All statuses (pending, active, rejected)
- ✅ Same as `/api/v1/listings/my` endpoint

**Use case**: Provider wants to manage their own listings

---

### 🟢 **Client View** (Marketplace - See All Listings)
```bash
GET /api/v1/listings?user_type=client
# OR
GET /api/v1/listings
```

**What you get**:
- ✅ All active listings from ALL providers
- ✅ Only approved/active listings
- ✅ Marketplace view for booking

**Use case**: Client browsing for services to book

---

### 🟡 **Guest View** (No Auth)
```bash
GET /api/v1/listings
# No Authorization header
```

**What you get**:
- ✅ All active listings from ALL providers
- ✅ Public marketplace view

---

## 📋 Complete Examples

### Example 1: Provider Wants to See Their Own Listings

```bash
GET /api/v1/listings?user_type=provider
Authorization: Bearer 5|abc123def456...
```

**Response**:
```json
{
  "success": true,
  "data": {
    "listings": [
      {
        "id": 1,
        "provider_id": 5,  // YOUR ID
        "title": "Professional Childcare",
        "status": "active",
        "is_my_listing": true  // This is YOUR listing
      },
      {
        "id": 2,
        "provider_id": 5,  // YOUR ID
        "title": "Pet Care Services",
        "status": "pending",
        "is_my_listing": true
      }
    ]
  }
}
```

**Note**: Shows ALL your listings (pending, active, rejected)

---

### Example 2: Client Browsing Marketplace

```bash
GET /api/v1/listings?user_type=client
Authorization: Bearer 3|xyz789abc123...
```

**Response**:
```json
{
  "success": true,
  "data": {
    "listings": [
      {
        "id": 10,
        "provider_id": 5,  // Provider 5's listing
        "title": "Professional Childcare",
        "status": "active"
      },
      {
        "id": 11,
        "provider_id": 7,  // Provider 7's listing
        "title": "Senior Care Services",
        "status": "active"
      },
      {
        "id": 12,
        "provider_id": 9,  // Provider 9's listing
        "title": "Pet Sitting",
        "status": "active"
      }
    ]
  }
}
```

**Note**: Shows ALL active listings from ALL providers

---

### Example 3: Filter + User Type

```bash
# Provider sees only THEIR childcare listings
GET /api/v1/listings?user_type=provider&category_id=1&search=childcare
Authorization: Bearer {provider_token}

# Client sees ALL childcare listings
GET /api/v1/listings?user_type=client&category_id=1&search=childcare
Authorization: Bearer {client_token}
```

---

## 🔧 How It Works in Code

### In `routes/api.php`:

```php
Route::prefix('listings')->group(function () {
    // Public endpoint - anyone can access
    Route::get('/', [ListingController::class, 'index']);
    
    // Other routes...
});

// Authenticated routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::prefix('listings')->group(function () {
        // Provider's own listings
        Route::get('/my', [ListingController::class, 'myListings']);
        
        Route::post('/', [ListingController::class, 'store']);
        Route::put('/{id}', [ListingController::class, 'update']);
        Route::delete('/{id}', [ListingController::class, 'destroy']);
    });
    
});
```

### In `ListingController.php`:

```php
public function index(Request $request)
{
    $query = ServiceListing::with(['category', 'provider'])
        ->active();  // Only active listings by default

    // ⭐ THIS IS THE KEY PART ⭐
    // If user_type=provider, show only provider's own listings
    if ($request->has('user_type') && 
        $request->user_type === 'provider' && 
        $request->user()) {
        
        $query->where('provider_id', $request->user()->id);
    }
    
    // Otherwise, show all active listings (client/guest view)
    
    // ... rest of filters (category, search, price, etc.)
    
    return response()->json([
        'success' => true,
        'data' => [
            'listings' => ListingResource::collection($listings)
        ]
    ]);
}
```

---

## 🎨 Frontend Implementation

### React Example:

```javascript
// Provider Dashboard - See My Listings
const MyListings = () => {
  const fetchMyListings = async () => {
    const response = await fetch(
      '/api/v1/listings?user_type=provider',
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );
    const data = await response.json();
    setListings(data.data.listings);
  };
  
  return (
    <div>
      <h2>My Listings</h2>
      {listings.map(listing => (
        <ListingCard key={listing.id} listing={listing} />
      ))}
    </div>
  );
};

// Client Marketplace - Browse All
const Marketplace = () => {
  const fetchAllListings = async () => {
    const response = await fetch(
      '/api/v1/listings?user_type=client',
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );
    const data = await response.json();
    setListings(data.data.listings);
  };
  
  return (
    <div>
      <h2>Browse Services</h2>
      {listings.map(listing => (
        <ServiceCard key={listing.id} listing={listing} />
      ))}
    </div>
  );
};
```

---

## 📊 All Available Filters

You can combine `user_type` with other filters:

```bash
GET /api/v1/listings?user_type=provider&status=active&category_id=1&search=childcare&min_price=20&max_price=50&sort_by=rating&per_page=10
```

**Available Parameters**:
- `user_type` - `provider` or `client` (determines whose listings)
- `category_id` - Filter by category
- `search` - Search in title/description
- `min_price` - Minimum hourly rate
- `max_price` - Maximum hourly rate
- `min_rating` - Minimum provider rating
- `sort_by` - `created_at`, `rating`, `price_low`, `price_high`
- `sort_order` - `asc` or `desc`
- `per_page` - Results per page (default: 12)
- `page` - Page number

---

## 🔐 Alternative Endpoint for Providers

Instead of using `?user_type=provider`, providers can use:

```bash
# Dedicated endpoint for provider's own listings
GET /api/v1/listings/my
Authorization: Bearer {provider_token}
```

**This is equivalent to**:
```bash
GET /api/v1/listings?user_type=provider
Authorization: Bearer {provider_token}
```

Both return the same results!

---

## 💡 Quick Reference

| Who? | What to Use | What You Get |
|------|-------------|--------------|
| **Provider** viewing own | `?user_type=provider` OR `/listings/my` | Only your listings |
| **Client** browsing | `?user_type=client` OR just `/listings` | All active listings |
| **Guest** browsing | `/listings` | All active listings |

---

## 🧪 Testing in Swagger/Postman

### Test 1: Provider View
```
GET /api/v1/listings?user_type=provider
Headers:
  Authorization: Bearer {provider_token}
  
Expected: Only provider's own listings
```

### Test 2: Client View
```
GET /api/v1/listings?user_type=client
Headers:
  Authorization: Bearer {client_token}
  
Expected: All active listings from all providers
```

### Test 3: Guest View
```
GET /api/v1/listings

Expected: All active listings (no auth needed)
```

---

## ✅ Summary

### Single Endpoint with Smart Filtering:
```
GET /api/v1/listings
```

### Control View with `user_type`:
- **`?user_type=provider`** → See only YOUR listings
- **`?user_type=client`** → See ALL listings (marketplace)
- **No parameter** → Default marketplace view (all listings)

### Authentication:
- **Provider** must be authenticated to see own listings
- **Client/Guest** can browse without auth (public marketplace)

---

## 🎯 The Logic

```
IF user_type === 'provider' AND user is authenticated
  THEN show only listings WHERE provider_id = current_user.id
ELSE
  THEN show all active listings (marketplace)
```

Simple and clean! ✨
