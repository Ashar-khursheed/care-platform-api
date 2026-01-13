# Payment & Payout API Testing Guide

## Prerequisites
1. Have provider and admin accounts created
2. Have at least one booking with payment completed
3. Set environment variables in Postman

## Postman Environment Variables
```
base_url: http://localhost:8000/api/v1
provider_token: {your_provider_token}
admin_token: {your_admin_token}
client_token: {your_client_token}
```

---

## Test Sequence

### 1. Provider Checks Available Balance
```
Method: GET
URL: {{base_url}}/payouts/balance
Headers:
  Authorization: Bearer {{provider_token}}
  Accept: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "total_earnings": "500.00",
    "total_paid_out": "0.00",
    "pending_payouts": "0.00",
    "available_balance": "500.00",
    "currency": "USD",
    "has_stripe_account": false
  }
}
```

---

### 2. Provider Requests Payout
```
Method: POST
URL: {{base_url}}/payouts/request
Headers:
  Authorization: Bearer {{provider_token}}
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "amount": 150.00,
  "bank_account_details": {
    "bank_name": "Chase Bank",
    "account_number": "1234567890",
    "routing_number": "021000021"
  }
}
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Payout request submitted successfully. It will be processed by admin.",
  "data": {
    "payout_id": 1,
    "amount": "150.00",
    "status": "pending",
    "scheduled_at": "2025-01-11T10:30:00.000000Z"
  }
}
```

**Test Cases**:
- ✓ Request payout with valid amount
- ✗ Request payout exceeding available balance (should fail)
- ✗ Request payout less than $10 (should fail)
- ✗ Client tries to request payout (should fail with 403)

---

### 3. Provider Views Payout History
```
Method: GET
URL: {{base_url}}/payouts?status=pending&per_page=10
Headers:
  Authorization: Bearer {{provider_token}}
  Accept: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "payouts": [
      {
        "id": 1,
        "provider_id": 2,
        "amount": "150.00",
        "currency": "USD",
        "status": "pending",
        "bank_name": "Chase Bank",
        "account_number_last4": "7890",
        "scheduled_at": "2025-01-11T10:30:00.000000Z",
        "created_at": "2025-01-11T10:30:00.000000Z"
      }
    ],
    "pagination": {
      "total": 1,
      "per_page": 10,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

### 4. Provider Views Specific Payout
```
Method: GET
URL: {{base_url}}/payouts/1
Headers:
  Authorization: Bearer {{provider_token}}
  Accept: application/json
```

---

### 5. Provider Cancels Pending Payout
```
Method: POST
URL: {{base_url}}/payouts/1/cancel
Headers:
  Authorization: Bearer {{provider_token}}
  Accept: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Payout request cancelled successfully"
}
```

**Note**: Only works for pending payouts

---

### 6. Admin Views All Payouts
```
Method: GET
URL: {{base_url}}/admin/payouts?status=pending&per_page=20
Headers:
  Authorization: Bearer {{admin_token}}
  Accept: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "payouts": [
      {
        "id": 1,
        "provider_id": 2,
        "provider": {
          "id": 2,
          "first_name": "John",
          "last_name": "Provider",
          "email": "provider@test.com"
        },
        "amount": "150.00",
        "currency": "USD",
        "status": "pending",
        "bank_name": "Chase Bank",
        "account_number_last4": "7890",
        "scheduled_at": "2025-01-11T10:30:00.000000Z"
      }
    ],
    "pagination": {
      "total": 1,
      "per_page": 20,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

### 7. Admin Views Payout Statistics
```
Method: GET
URL: {{base_url}}/admin/payouts/statistics
Headers:
  Authorization: Bearer {{admin_token}}
  Accept: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "total_payouts": 5,
    "pending_payouts": 2,
    "pending_amount": 300,
    "paid_payouts": 3,
    "paid_amount": 450,
    "rejected_payouts": 0,
    "total_pending_value": "300.00"
  }
}
```

---

### 8. Admin Approves Payout
```
Method: POST
URL: {{base_url}}/admin/payouts/1/approve
Headers:
  Authorization: Bearer {{admin_token}}
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "transaction_reference": "BANK_TRANSFER_20250111_001",
  "notes": "Payment processed via bank transfer on 2025-01-11"
}
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Payout approved and processed successfully",
  "data": {
    "id": 1,
    "provider_id": 2,
    "amount": "150.00",
    "status": "paid",
    "paid_at": "2025-01-11T14:00:00.000000Z"
  }
}
```

**Test Cases**:
- ✓ Approve pending payout with transaction reference
- ✗ Approve already paid payout (should fail)
- ✗ Approve as provider (should fail with 403)

---

### 9. Admin Rejects Payout
```
Method: POST
URL: {{base_url}}/admin/payouts/2/reject
Headers:
  Authorization: Bearer {{admin_token}}
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "reason": "Bank account information is incomplete. Please update routing number."
}
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Payout request rejected",
  "data": {
    "id": 2,
    "status": "rejected",
    "failure_reason": "Bank account information is incomplete. Please update routing number.",
    "failed_at": "2025-01-11T14:00:00.000000Z"
  }
}
```

---

### 10. Admin Bulk Approves Payouts
```
Method: POST
URL: {{base_url}}/admin/payouts/bulk-approve
Headers:
  Authorization: Bearer {{admin_token}}
  Content-Type: application/json
  Accept: application/json

Body (raw JSON):
{
  "payout_ids": [3, 4, 5],
  "transaction_reference": "BATCH_TRANSFER_20250111"
}
```

**Expected Response**:
```json
{
  "success": true,
  "message": "3 payouts approved successfully",
  "data": {
    "approved_count": 3
  }
}
```

---

### 11. Provider Views Transaction History
```
Method: GET
URL: {{base_url}}/transactions?per_page=10
Headers:
  Authorization: Bearer {{provider_token}}
  Accept: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 1,
        "type": "payout",
        "amount": "150.00",
        "direction": "credit",
        "status": "completed",
        "description": "Payout for services rendered",
        "created_at": "2025-01-11T14:00:00.000000Z"
      }
    ],
    "pagination": {
      "total": 1,
      "per_page": 10,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

## Complete Payment Flow Test

### Step 1: Create Booking & Payment
```
1. POST /api/v1/bookings (as client)
2. POST /api/v1/bookings/{id}/accept (as provider)
3. POST /api/v1/payments/create-intent (as client)
4. Wait for Stripe webhook to mark payment as succeeded
```

### Step 2: Provider Withdrawal Flow
```
5. GET /api/v1/payouts/balance (verify available balance)
6. POST /api/v1/payouts/request (request withdrawal)
7. GET /api/v1/payouts (check payout status)
```

### Step 3: Admin Approval Flow
```
8. GET /api/v1/admin/payouts?status=pending (view pending requests)
9. GET /api/v1/admin/payouts/statistics (check statistics)
10. POST /api/v1/admin/payouts/{id}/approve (release payment)
```

### Step 4: Verify Completion
```
11. GET /api/v1/payouts/{id} (provider checks payout status)
12. GET /api/v1/transactions (provider sees transaction)
13. GET /api/v1/payouts/balance (verify new balance)
```

---

## Error Scenarios to Test

### 1. Insufficient Balance
```
POST /api/v1/payouts/request
{
  "amount": 99999.00
}

Expected: 400 Bad Request
{
  "success": false,
  "message": "Insufficient balance. Available: $500.00"
}
```

### 2. Below Minimum Amount
```
POST /api/v1/payouts/request
{
  "amount": 5.00
}

Expected: 422 Validation Error
```

### 3. Client Tries to Request Payout
```
POST /api/v1/payouts/request (with client_token)

Expected: 403 Forbidden
{
  "success": false,
  "message": "Only providers can request payouts"
}
```

### 4. Approve Already Paid Payout
```
POST /api/v1/admin/payouts/1/approve (on paid payout)

Expected: 400 Bad Request
{
  "success": false,
  "message": "Only pending payouts can be approved"
}
```

### 5. Provider Approves Own Payout
```
POST /api/v1/admin/payouts/1/approve (with provider_token)

Expected: 403 Forbidden
```

### 6. Cancel Non-Pending Payout
```
POST /api/v1/payouts/1/cancel (on paid payout)

Expected: 400 Bad Request
{
  "success": false,
  "message": "Can only cancel pending payouts"
}
```

---

## Postman Collection JSON

Save this as a Postman collection:

```json
{
  "info": {
    "name": "Care Platform - Payouts",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Provider - Get Balance",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{provider_token}}"
          }
        ],
        "url": "{{base_url}}/payouts/balance"
      }
    },
    {
      "name": "Provider - Request Payout",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{provider_token}}"
          },
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"amount\": 150.00,\n  \"bank_account_details\": {\n    \"bank_name\": \"Chase Bank\",\n    \"account_number\": \"1234567890\"\n  }\n}"
        },
        "url": "{{base_url}}/payouts/request"
      }
    },
    {
      "name": "Provider - View Payout History",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{provider_token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/payouts?status=pending",
          "query": [
            {
              "key": "status",
              "value": "pending"
            }
          ]
        }
      }
    },
    {
      "name": "Admin - View All Payouts",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{admin_token}}"
          }
        ],
        "url": "{{base_url}}/admin/payouts"
      }
    },
    {
      "name": "Admin - Approve Payout",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{admin_token}}"
          },
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"transaction_reference\": \"TRANSFER_001\",\n  \"notes\": \"Processed\"\n}"
        },
        "url": "{{base_url}}/admin/payouts/1/approve"
      }
    },
    {
      "name": "Admin - Reject Payout",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{admin_token}}"
          },
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"reason\": \"Invalid bank details\"\n}"
        },
        "url": "{{base_url}}/admin/payouts/1/reject"
      }
    }
  ]
}
```

---

## Database Verification Queries

After testing, verify in database:

```sql
-- Check payout status
SELECT id, provider_id, amount, status, paid_at 
FROM payouts 
WHERE status = 'paid';

-- Check transaction creation
SELECT id, user_id, type, amount, direction, status 
FROM transactions 
WHERE type = 'payout';

-- Verify provider balance calculation
SELECT 
  p.provider_id,
  SUM(p.provider_amount) as total_earnings,
  COALESCE(SUM(po.amount), 0) as paid_out
FROM payments p
LEFT JOIN payouts po ON po.provider_id = p.provider_id AND po.status = 'paid'
WHERE p.status = 'succeeded'
GROUP BY p.provider_id;
```

---

## Summary Checklist

✅ Provider can check balance
✅ Provider can request payout
✅ Provider can view payout history
✅ Provider can cancel pending payout
✅ Admin can view all payouts
✅ Admin can view statistics
✅ Admin can approve payouts
✅ Admin can reject payouts
✅ Admin can bulk approve
✅ Transaction history is created
✅ Balance updates correctly
✅ Proper authorization checks
✅ Validation errors work correctly

---

## Next Steps

1. Import Postman collection
2. Set environment variables
3. Run through complete flow
4. Test error scenarios
5. Verify database changes
6. Check transaction history

Happy Testing! 🚀
