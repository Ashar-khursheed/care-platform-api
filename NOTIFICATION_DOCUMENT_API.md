# Notification & Document Management API Documentation

## Overview
Complete guide for Notification and Document verification systems in the Care Platform.

---

## 📬 NOTIFICATION SYSTEM

### User Notification Endpoints

#### 1. Get All Notifications
**Endpoint**: `GET /api/v1/notifications`

**Query Parameters**:
- `is_read` - Filter by read status (true/false)
- `type` - Filter by notification type
- `priority` - Filter by priority (low, medium, high, urgent)
- `per_page` - Results per page (default: 20)
- `page` - Page number

**Request**:
```bash
GET /api/v1/notifications?is_read=false&per_page=10
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 1,
        "type": "booking_accepted",
        "title": "Booking Accepted",
        "message": "Your booking #123 has been accepted by the provider",
        "action_url": "/bookings/123",
        "is_read": false,
        "read_at": null,
        "priority": "high",
        "icon": "✅",
        "color": "green",
        "created_at": "2025-01-11 10:30:00",
        "data": {
          "booking_id": 123
        }
      }
    ],
    "pagination": {
      "total": 45,
      "per_page": 10,
      "current_page": 1,
      "last_page": 5
    }
  }
}
```

---

#### 2. Get Unread Count
**Endpoint**: `GET /api/v1/notifications/unread-count`

**Request**:
```bash
GET /api/v1/notifications/unread-count
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "unread_count": 12
  }
}
```

---

#### 3. Get Recent Notifications
**Endpoint**: `GET /api/v1/notifications/recent`

**Query Parameters**:
- `days` - Number of days to look back (default: 7)

**Request**:
```bash
GET /api/v1/notifications/recent?days=3
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "type": "payment_received",
      "title": "Payment Received",
      "message": "You received $90.00 payment",
      "is_read": false,
      "icon": "💰",
      "color": "green",
      "created_at": "2 hours ago"
    }
  ]
}
```

---

#### 4. Mark Notification as Read
**Endpoint**: `PUT /api/v1/notifications/{id}/read`

**Request**:
```bash
PUT /api/v1/notifications/15/read
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

#### 5. Mark Notification as Unread
**Endpoint**: `PUT /api/v1/notifications/{id}/unread`

**Request**:
```bash
PUT /api/v1/notifications/15/unread
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "Notification marked as unread"
}
```

---

#### 6. Mark All as Read
**Endpoint**: `PUT /api/v1/notifications/read-all`

**Request**:
```bash
PUT /api/v1/notifications/read-all
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

---

#### 7. Delete Notification
**Endpoint**: `DELETE /api/v1/notifications/{id}`

**Request**:
```bash
DELETE /api/v1/notifications/15
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "Notification deleted"
}
```

---

#### 8. Delete All Notifications
**Endpoint**: `DELETE /api/v1/notifications`

**Request**:
```bash
DELETE /api/v1/notifications
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "All notifications deleted"
}
```

---

#### 9. Clear Read Notifications
**Endpoint**: `DELETE /api/v1/notifications/clear-all`

**Request**:
```bash
DELETE /api/v1/notifications/clear-all
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "All read notifications cleared"
}
```

---

#### 10. Get Notification Preferences
**Endpoint**: `GET /api/v1/notifications/preferences`

**Request**:
```bash
GET /api/v1/notifications/preferences
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "email_notifications": true,
    "push_notifications": true,
    "sms_notifications": false,
    "notification_types": {
      "booking_updates": true,
      "payment_updates": true,
      "message_updates": true,
      "review_updates": true,
      "system_updates": true,
      "promotional": false
    }
  }
}
```

---

#### 11. Update Notification Preferences
**Endpoint**: `PUT /api/v1/notifications/preferences`

**Request**:
```bash
PUT /api/v1/notifications/preferences
Authorization: Bearer {token}
Content-Type: application/json

{
  "email_notifications": true,
  "push_notifications": false,
  "sms_notifications": false,
  "notification_types": {
    "booking_updates": true,
    "payment_updates": true,
    "message_updates": false,
    "review_updates": true,
    "system_updates": true,
    "promotional": false
  }
}
```

**Response**:
```json
{
  "success": true,
  "message": "Notification preferences updated",
  "data": {
    "email_notifications": true,
    "push_notifications": false,
    "sms_notifications": false,
    "notification_types": {...}
  }
}
```

---

#### 12. Register Device for Push Notifications
**Endpoint**: `POST /api/v1/notifications/register-device`

**Request**:
```bash
POST /api/v1/notifications/register-device
Authorization: Bearer {token}
Content-Type: application/json

{
  "device_token": "fcm_token_here",
  "device_type": "android"
}
```

**Device Types**: `ios`, `android`, `web`

**Response**:
```json
{
  "success": true,
  "message": "Device registered for push notifications"
}
```

---

#### 13. Unregister Device
**Endpoint**: `POST /api/v1/notifications/unregister-device`

**Request**:
```bash
POST /api/v1/notifications/unregister-device
Authorization: Bearer {token}
Content-Type: application/json

{
  "device_token": "fcm_token_here"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Device unregistered from push notifications"
}
```

---

### Notification Types

```
booking_created       - New booking created
booking_accepted      - Booking accepted
booking_rejected      - Booking rejected
booking_cancelled     - Booking cancelled
booking_completed     - Booking completed
payment_received      - Payment received
payment_failed        - Payment failed
payment_refunded      - Payment refunded
payout_processed      - Payout processed
message_received      - New message
review_received       - New review
review_response       - Review response
document_approved     - Document approved
document_rejected     - Document rejected
listing_approved      - Listing approved
listing_rejected      - Listing rejected
system_announcement   - System announcement
promotional           - Promotional notification
```

---

### Admin Notification Endpoints

#### 1. Get All Notifications (Admin)
**Endpoint**: `GET /api/v1/admin/notifications`

**Query Parameters**:
- `user_id` - Filter by user
- `type` - Filter by type
- `is_read` - Filter by read status
- `priority` - Filter by priority
- `per_page` - Results per page (default: 50)

**Request**:
```bash
GET /api/v1/admin/notifications?priority=urgent&per_page=20
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "data": [...],
  "current_page": 1,
  "total": 150
}
```

---

#### 2. Send Announcement
**Endpoint**: `POST /api/v1/admin/notifications/announcement`

**Request**:
```bash
POST /api/v1/admin/notifications/announcement
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "title": "Platform Maintenance",
  "message": "Scheduled maintenance on Sunday 2AM-4AM EST",
  "user_type": "provider",
  "priority": "high"
}
```

**User Types**: `client`, `provider`, `admin`, or omit for all users

**Response**:
```json
{
  "success": true,
  "message": "Announcement sent successfully."
}
```

---

#### 3. Send to Specific Users
**Endpoint**: `POST /api/v1/admin/notifications/send-to-users`

**Request**:
```bash
POST /api/v1/admin/notifications/send-to-users
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "user_ids": [1, 2, 3, 5, 8],
  "type": "system_announcement",
  "title": "Important Update",
  "message": "Your account verification is complete",
  "priority": "medium"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Notifications sent to 5 users."
}
```

---

#### 4. Get Statistics
**Endpoint**: `GET /api/v1/admin/notifications/statistics`

**Request**:
```bash
GET /api/v1/admin/notifications/statistics
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_notifications": 5420,
      "unread_notifications": 1230,
      "read_notifications": 4190,
      "read_rate": "77.31%"
    },
    "by_type": {
      "booking_updates": 2100,
      "payment_updates": 1500,
      "system_announcements": 500
    },
    "by_priority": {
      "urgent": 120,
      "high": 800,
      "medium": 3200,
      "low": 1300
    },
    "delivery": {
      "emails_sent": 4500,
      "pushes_sent": 3200,
      "sms_sent": 150
    },
    "recent_notifications": [...],
    "most_engaged_users": [...]
  }
}
```

---

#### 5. Test Notification
**Endpoint**: `POST /api/v1/admin/notifications/test`

**Request**:
```bash
POST /api/v1/admin/notifications/test
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "user_id": 5
}
```

**Response**:
```json
{
  "success": true,
  "message": "Test notification sent."
}
```

---

#### 6. Delete Notification (Admin)
**Endpoint**: `DELETE /api/v1/admin/notifications/{id}`

**Request**:
```bash
DELETE /api/v1/admin/notifications/150
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "success": true,
  "message": "Notification deleted permanently."
}
```

---

## 📄 DOCUMENT VERIFICATION SYSTEM

### User Document Endpoints
(See ProfileController documentation)

### Admin Document Endpoints

#### 1. Get Pending Documents
**Endpoint**: `GET /api/v1/admin/documents/pending`

**Query Parameters**:
- `document_type` - Filter by type
- `per_page` - Results per page (default: 15)

**Request**:
```bash
GET /api/v1/admin/documents/pending?document_type=identity_proof
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "documents": [
      {
        "id": 25,
        "user": {
          "id": 12,
          "name": "John Doe",
          "email": "john@example.com",
          "user_type": "provider"
        },
        "document_type": "identity_proof",
        "document_name": "drivers_license.pdf",
        "verification_status": "pending",
        "uploaded_at": "2025-01-11 09:30:00"
      }
    ],
    "pagination": {
      "total": 15,
      "per_page": 15,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

#### 2. Get All Documents
**Endpoint**: `GET /api/v1/admin/documents`

**Query Parameters**:
- `status` - Filter by status (pending, approved, rejected)
- `user_id` - Filter by user
- `document_type` - Filter by type
- `per_page` - Results per page (default: 15)

**Request**:
```bash
GET /api/v1/admin/documents?status=approved&per_page=20
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "documents": [
      {
        "id": 20,
        "user": {
          "id": 10,
          "name": "Jane Smith",
          "email": "jane@example.com"
        },
        "document_type": "certification",
        "document_name": "cpr_certification.pdf",
        "verification_status": "approved",
        "rejection_reason": null,
        "verified_at": "2025-01-10 14:20:00",
        "uploaded_at": "2025-01-10 10:00:00"
      }
    ],
    "pagination": {...}
  }
}
```

---

#### 3. View Document Details
**Endpoint**: `GET /api/v1/admin/documents/{id}`

**Request**:
```bash
GET /api/v1/admin/documents/25
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 25,
    "user": {
      "id": 12,
      "name": "John Doe",
      "email": "john@example.com",
      "user_type": "provider"
    },
    "document_type": "identity_proof",
    "document_name": "drivers_license.pdf",
    "verification_status": "pending",
    "rejection_reason": null,
    "verified_at": null,
    "uploaded_at": "2025-01-11 09:30:00",
    "file_info": {
      "mime_type": "application/pdf",
      "size": 524288
    }
  }
}
```

---

#### 4. Download Document
**Endpoint**: `GET /api/v1/admin/documents/{id}/download`

**Request**:
```bash
GET /api/v1/admin/documents/25/download
Authorization: Bearer {admin_token}
```

**Response**: File download (PDF, Image, etc.)

---

#### 5. Approve Document
**Endpoint**: `POST /api/v1/admin/documents/{id}/approve`

**Request**:
```bash
POST /api/v1/admin/documents/25/approve
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "success": true,
  "message": "Document approved successfully",
  "data": {
    "id": 25,
    "verification_status": "approved",
    "verified_at": "2025-01-11 15:00:00"
  }
}
```

**What Happens**:
- Document status changes to "approved"
- User's verification status is checked
- If user has all required documents approved, user becomes verified
- Notification sent to user

---

#### 6. Reject Document
**Endpoint**: `POST /api/v1/admin/documents/{id}/reject`

**Request**:
```bash
POST /api/v1/admin/documents/25/reject
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "reason": "Document is blurry and unreadable. Please upload a clearer image."
}
```

**Response**:
```json
{
  "success": true,
  "message": "Document rejected",
  "data": {
    "id": 25,
    "verification_status": "rejected",
    "rejection_reason": "Document is blurry and unreadable. Please upload a clearer image.",
    "verified_at": "2025-01-11 15:00:00"
  }
}
```

**What Happens**:
- Document status changes to "rejected"
- Rejection reason is saved
- Notification sent to user with reason
- User can upload new document

---

#### 7. Delete Document
**Endpoint**: `DELETE /api/v1/admin/documents/{id}`

**Request**:
```bash
DELETE /api/v1/admin/documents/25
Authorization: Bearer {admin_token}
```

**Response**:
```json
{
  "success": true,
  "message": "Document deleted successfully"
}
```

**What Happens**:
- Document file is deleted from storage
- Database record is deleted
- Permanent action (cannot be undone)

---

### Document Types

```
identity_proof         - Government ID, Driver's License, Passport
address_proof          - Utility Bill, Bank Statement
certification          - Professional Certifications (CPR, First Aid, etc.)
background_check       - Background Check Report
insurance              - Insurance Certificate
reference_letter       - Reference Letters
work_permit            - Work Authorization
other                  - Other Documents
```

---

### Verification Requirements

#### For Clients:
- ✅ Identity Proof (required)
- Status becomes "verified" after identity is approved

#### For Providers:
- ✅ Identity Proof (required)
- ✅ Certification OR Background Check (required)
- Status becomes "verified" after all requirements are approved

---

## 🧪 Testing Guide

### Test Notification Flow

```bash
# 1. Get unread count
GET /api/v1/notifications/unread-count

# 2. Get all notifications
GET /api/v1/notifications

# 3. Mark one as read
PUT /api/v1/notifications/1/read

# 4. Get recent notifications
GET /api/v1/notifications/recent

# 5. Mark all as read
PUT /api/v1/notifications/read-all

# 6. Update preferences
PUT /api/v1/notifications/preferences
{
  "email_notifications": false,
  "push_notifications": true
}

# 7. Clear read notifications
DELETE /api/v1/notifications/clear-all
```

### Test Document Verification Flow

```bash
# Admin View

# 1. Get pending documents
GET /api/v1/admin/documents/pending

# 2. View document details
GET /api/v1/admin/documents/25

# 3. Download document
GET /api/v1/admin/documents/25/download

# 4. Approve document
POST /api/v1/admin/documents/25/approve

# OR Reject with reason
POST /api/v1/admin/documents/25/reject
{
  "reason": "Please upload a clearer image"
}

# 5. Check all documents
GET /api/v1/admin/documents?status=approved
```

---

## 📊 Database Schema

### Notifications Table
```sql
- id
- user_id
- type
- title
- message
- related_type
- related_id
- action_url
- data (JSON)
- is_read
- read_at
- priority (low, medium, high, urgent)
- sent_in_app
- sent_email
- sent_push
- sent_sms
- created_at
```

### Profile Documents Table
```sql
- id
- user_id
- document_type
- document_name
- document_path
- verification_status (pending, approved, rejected)
- rejection_reason
- verified_at
- verified_by
- created_at
```

### Notification Preferences Table
```sql
- id
- user_id
- email_notifications
- push_notifications
- sms_notifications
- notification_types (JSON)
- created_at
```

---

## ✅ Summary

### Notification System
- ✅ 13 user endpoints
- ✅ 6 admin endpoints
- ✅ Read/Unread tracking
- ✅ Preferences management
- ✅ Push notification support
- ✅ Comprehensive filtering
- ✅ Statistics dashboard

### Document System
- ✅ 7 admin endpoints
- ✅ Approve/Reject workflow
- ✅ File download support
- ✅ Auto-verification checking
- ✅ Multiple document types
- ✅ Complete audit trail

Both systems are **production-ready** and **fully functional**! 🚀
