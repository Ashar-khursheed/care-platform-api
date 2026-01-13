# Swagger/OpenAPI Documentation Setup Guide

## 📚 What is Swagger/OpenAPI?

Swagger/OpenAPI is an interactive API documentation that allows you to:
- ✅ View all API endpoints in one place
- ✅ Test APIs directly from the browser
- ✅ See request/response examples
- ✅ Understand required parameters
- ✅ Get automatic code generation
- ✅ Share with your team

**Much better than Postman for API documentation!**

---

## 🚀 Quick Start - 3 Ways to Use

### Option 1: Swagger UI Online (Fastest)

1. Go to: https://editor.swagger.io/
2. Click **File → Import File**
3. Upload `openapi.yaml` from your project
4. View interactive documentation instantly!

### Option 2: Local Swagger UI (Recommended)

**Step 1: Download Swagger UI**
```bash
cd your-project-directory
git clone https://github.com/swagger-api/swagger-ui.git swagger-ui-dist
```

**Step 2: Copy your OpenAPI file**
```bash
cp openapi.yaml swagger-ui-dist/dist/
```

**Step 3: Update the Swagger UI index**
Edit `swagger-ui-dist/dist/index.html`, find this line:
```javascript
url: "https://petstore.swagger.io/v2/swagger.json",
```

Replace with:
```javascript
url: "./openapi.yaml",
```

**Step 4: Serve it**
```bash
cd swagger-ui-dist/dist
python3 -m http.server 8080
```

**Step 5: Open browser**
Go to: http://localhost:8080

🎉 Your API documentation is now live!

### Option 3: Using Docker (Easiest)

**Step 1: Run Swagger UI with Docker**
```bash
docker run -p 80:8080 -e SWAGGER_JSON=/openapi.yaml -v $(pwd)/openapi.yaml:/openapi.yaml swaggerapi/swagger-ui
```

**Step 2: Open browser**
Go to: http://localhost

---

## 🎯 How to Use Swagger UI

### 1. View All Endpoints
- Organized by tags (Authentication, Profile, Listings, etc.)
- Click any endpoint to expand details

### 2. Try It Out
1. Click on any endpoint
2. Click **"Try it out"** button
3. Fill in parameters
4. Click **"Execute"**
5. See the response

### 3. Authenticate
1. Click **"Authorize"** button at the top
2. Enter your Bearer token: `Bearer {your_token}`
3. Click **"Authorize"**
4. Now all requests will include your token

### 4. Get Your Token
```bash
# Login first
POST /api/v1/auth/login
{
  "email": "your@email.com",
  "password": "yourpassword"
}

# Copy the token from response
{
  "data": {
    "token": "1|abc123def456..."  ← Copy this
  }
}

# Use in Swagger: Bearer 1|abc123def456...
```

---

## 📖 OpenAPI File Overview

Your `openapi.yaml` file contains:

### All Endpoints Documented (100+)
```
✅ Authentication (6 endpoints)
   - Register, Login, Logout, Password Reset

✅ Profile Management (5 endpoints)
   - View, Update, Upload Photo, Documents

✅ Listings (6 endpoints)
   - Browse, Create, Update, Delete, My Listings

✅ Bookings (10+ endpoints)
   - Create, Accept, Reject, Cancel, Complete

✅ Payments (7 endpoints)
   - Create Intent, Confirm, Refund, History

✅ Payouts (5 endpoints)
   - Check Balance, Request, History, Cancel

✅ Notifications (13 endpoints)
   - View, Mark Read, Preferences, Push Notifications

✅ Reviews (8 endpoints)
   - Create, Update, Delete, Respond

✅ Messages (10 endpoints)
   - Send, View, Conversations

✅ Admin Endpoints (40+ endpoints)
   - Payouts: Approve, Reject, Bulk Approve
   - Documents: Approve, Reject, Download
   - Notifications: Send Announcements
   - Users, Bookings, Analytics, etc.
```

### Complete Schema Definitions
- User
- Listing
- Booking
- Payment
- Payout
- Notification
- Document
- And more...

### Request/Response Examples
Every endpoint includes:
- ✅ Required parameters
- ✅ Optional parameters
- ✅ Request body examples
- ✅ Response examples
- ✅ Error responses
- ✅ Data types
- ✅ Validation rules

---

## 💡 Testing Examples Using Swagger

### Example 1: Provider Requests Payout

**Step 1: Login**
```
POST /auth/login
Body:
{
  "email": "provider@example.com",
  "password": "password"
}
```

**Step 2: Authorize in Swagger**
- Click "Authorize" button
- Enter: `Bearer {token_from_login}`

**Step 3: Check Balance**
```
GET /payouts/balance
(No parameters needed - uses your token)
```

**Step 4: Request Payout**
```
POST /payouts/request
Body:
{
  "amount": 150.00,
  "bank_account_details": {
    "bank_name": "Chase Bank",
    "account_number": "1234567890"
  }
}
```

**Step 5: View in Swagger Response**
```json
{
  "success": true,
  "message": "Payout request submitted successfully",
  "data": {
    "payout_id": 1,
    "amount": "150.00",
    "status": "pending"
  }
}
```

### Example 2: Admin Approves Payout

**Step 1: Login as Admin**
```
POST /auth/login
Body:
{
  "email": "admin@example.com",
  "password": "adminpass"
}
```

**Step 2: View Pending Payouts**
```
GET /admin/payouts?status=pending
```

**Step 3: Approve Payout**
```
POST /admin/payouts/1/approve
Body:
{
  "transaction_reference": "BANK_TRANSFER_001",
  "notes": "Processed"
}
```

**Step 4: See Success Response**
```json
{
  "success": true,
  "message": "Payout approved and processed successfully"
}
```

---

## 🔍 Advanced Features

### 1. Filter Endpoints by Tag
Click on tag names to show/hide sections:
- Authentication
- Profile
- Listings
- Payouts
- Admin - Payouts
- etc.

### 2. Search Functionality
- Use browser's search (Ctrl+F / Cmd+F)
- Search by endpoint path, description, or parameter name

### 3. Download OpenAPI Spec
- Click "Download" button in Swagger UI
- Get JSON or YAML format
- Share with your team

### 4. Code Generation
Some Swagger UI versions offer:
- Generate API client code
- Multiple languages (JavaScript, Python, PHP, etc.)
- Copy-paste ready

### 5. Schema Explorer
- Click on schema names in responses
- See full object structure
- Understand data relationships

---

## 📱 Mobile Testing

Swagger UI works great on mobile browsers:
1. Deploy Swagger UI to your server
2. Access from phone browser
3. Test APIs on the go
4. Perfect for demos!

---

## 🌐 Deploying Swagger UI

### Option 1: GitHub Pages (Free)
```bash
# In your project
mkdir docs
cp -r swagger-ui-dist/dist/* docs/
cp openapi.yaml docs/
git add docs/
git commit -m "Add API documentation"
git push

# Enable GitHub Pages in repo settings
# Point to /docs folder
# Access at: https://yourusername.github.io/yourrepo/
```

### Option 2: Netlify (Free)
```bash
# Create netlify.toml
[build]
  publish = "swagger-ui-dist/dist"

# Deploy
netlify deploy
```

### Option 3: Your Laravel App
```bash
# Copy to Laravel public folder
cp -r swagger-ui-dist/dist public/api-docs
cp openapi.yaml public/api-docs/

# Access at: http://yourapp.com/api-docs
```

---

## 🔒 Security Best Practices

### 1. Protect Production Docs
```php
// routes/web.php
Route::get('/api-docs', function() {
    // Add authentication
    if (!auth()->check() || !auth()->user()->isAdmin()) {
        abort(403);
    }
    return view('api-docs');
});
```

### 2. Different Docs for Different Environments
```yaml
servers:
  - url: http://localhost:8000/api/v1
    description: Development
  - url: https://staging.api.com/api/v1
    description: Staging
  - url: https://api.careplatform.com/api/v1
    description: Production
```

### 3. Hide Sensitive Info
Don't include in OpenAPI:
- ❌ Real API keys
- ❌ Production credentials
- ❌ Internal endpoints
- ❌ Debug information

---

## 🎨 Customization

### Change Swagger UI Theme
Edit `swagger-ui-dist/dist/index.html`:
```javascript
const ui = SwaggerUIBundle({
  url: "./openapi.yaml",
  dom_id: '#swagger-ui',
  deepLinking: true,
  presets: [
    SwaggerUIBundle.presets.apis,
    SwaggerUIStandalonePreset
  ],
  plugins: [
    SwaggerUIBundle.plugins.DownloadUrl
  ],
  // Add custom theme
  syntaxHighlight: {
    activate: true,
    theme: "monokai" // or "agate"
  }
})
```

### Add Company Logo
```javascript
const ui = SwaggerUIBundle({
  // ... other options
  customCss: '.swagger-ui .topbar { display: none }',
  customSiteTitle: "Care Platform API"
})
```

---

## 📊 Compare: Postman vs Swagger

| Feature | Postman | Swagger UI |
|---------|---------|------------|
| **Cost** | Free tier limited | Completely free |
| **Expiration** | Can expire | Never expires |
| **Sharing** | Team features $$ | Share YAML file |
| **Documentation** | Manual | Auto-generated |
| **Interactive** | Yes | Yes |
| **Code Gen** | Limited | Built-in |
| **Browser Only** | No (desktop app) | Yes |
| **Open Source** | No | Yes |

**Winner: Swagger UI** for public API documentation!

---

## 🔄 Keeping Docs Updated

### Option 1: Manual Updates
Edit `openapi.yaml` when you add/change endpoints

### Option 2: Auto-Generate (Laravel Package)
```bash
composer require darkaonline/l5-swagger

# Generate from PHPDoc annotations
php artisan l5-swagger:generate
```

### Option 3: API Design First
1. Design API in Swagger Editor
2. Export OpenAPI spec
3. Build API based on spec
4. Always in sync!

---

## 🐛 Troubleshooting

### Swagger UI Not Loading
```bash
# Check if file exists
ls openapi.yaml

# Check file permissions
chmod 644 openapi.yaml

# Check for YAML errors
python3 -c "import yaml; yaml.safe_load(open('openapi.yaml'))"
```

### CORS Issues
Add to Laravel `public/index.php`:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### Authentication Not Working
1. Make sure token has "Bearer " prefix
2. Check token is still valid
3. Verify endpoint requires authentication

---

## ✅ Checklist

Before sharing your API docs:

- [ ] OpenAPI file has no errors
- [ ] All endpoints documented
- [ ] Request examples provided
- [ ] Response examples provided
- [ ] Authentication explained
- [ ] Error responses documented
- [ ] Server URLs correct
- [ ] Contact info updated
- [ ] Tested in Swagger UI
- [ ] Deployed and accessible

---

## 📚 Additional Resources

### Official Documentation
- Swagger Editor: https://editor.swagger.io/
- OpenAPI Spec: https://swagger.io/specification/
- Swagger UI: https://swagger.io/tools/swagger-ui/

### Learning Resources
- OpenAPI Tutorial: https://swagger.io/docs/specification/basic-structure/
- Swagger UI Customization: https://swagger.io/docs/open-source-tools/swagger-ui/customization/overview/

### Tools
- Swagger Validator: https://validator.swagger.io/
- OpenAPI Generator: https://openapi-generator.tech/
- Swagger Inspector: https://inspector.swagger.io/

---

## 🎉 Summary

You now have:
- ✅ Complete OpenAPI 3.0 specification
- ✅ 100+ endpoints documented
- ✅ Interactive API testing
- ✅ No expiration (unlike Postman)
- ✅ Free forever
- ✅ Easy to share
- ✅ Professional documentation

**Your `openapi.yaml` file is ready to use!**

Just open https://editor.swagger.io/ and import it to start testing! 🚀

---

## 🔥 Quick Start Commands

```bash
# 1. Use Online Editor (Easiest)
Open: https://editor.swagger.io/
File → Import File → Select openapi.yaml

# 2. Use Docker (Fastest)
docker run -p 80:8080 -v $(pwd)/openapi.yaml:/openapi.yaml -e SWAGGER_JSON=/openapi.yaml swaggerapi/swagger-ui

# 3. Use Python Server (Simple)
cd swagger-ui-dist/dist
python3 -m http.server 8080
# Open: http://localhost:8080
```

Choose any option and you're ready to test your APIs! 🎯
