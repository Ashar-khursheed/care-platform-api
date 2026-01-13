# Care Platform - Complete Payment & Payout System Documentation

## Overview
This platform uses Stripe for payment processing with a manual payout approval system. The admin controls when providers receive their earnings.

---

## Payment Flow

### 1. Client Makes Payment
When a client books a service:
- **Endpoint**: `POST /api/v1/payments/create-intent`
- Client pays through Stripe
- Platform fee (10% default) is deducted
- Provider amount is calculated: `provider_amount = total_amount - platform_fee`
- Payment is marked as `succeeded` via webhook

### 2. Payment Distribution
```
Total Amount: $100
├── Platform Fee (10%): $10
└── Provider Amount (90%): $90
```

The provider's $90 is held until they request withdrawal.

---

## Payout/Withdrawal Flow

### Step 1: Provider Checks Available Balance
**Endpoint**: `GET /api/v1/payouts/balance`

**Response**:
```json
{
  "success": true,
  "data": {
    "total_earnings": "500.00",
    "total_paid_out": "200.00",
    "pending_payouts": "100.00",
    "available_balance": "200.00",
    "currency": "USD",
    "has_stripe_account": false
  }
}
```

**Balance Calculation**:
```
Available Balance = Total Earnings - Total Paid Out - Pending Payouts
```

### Step 2: Provider Requests Payout
**Endpoint**: `POST /api/v1/payouts/request`

**Request Body**:
```json
{
  "amount": 150.00,
  "bank_account_details": {
    "bank_name": "Chase Bank",
    "account_number": "1234567890",
    "routing_number": "021000021"
  }
}
```

**Response**:
```json
{
  "success": true,
  "message": "Payout request submitted successfully. It will be processed by admin.",
  "data": {
    "payout_id": 45,
    "amount": "150.00",
    "status": "pending",
    "scheduled_at": "2025-01-11T10:30:00Z"
  }
}
```

**Validations**:
- Minimum payout: $10
- Amount cannot exceed available balance
- Only providers can request payouts

### Step 3: Provider Views Payout History
**Endpoint**: `GET /api/v1/payouts?status=pending`

**Query Parameters**:
- `status`: pending, paid, rejected, cancelled
- `per_page`: 10 (default)
- `page`: 1

**Response**:
```json
{
  "success": true,
  "data": {
    "payouts": [
      {
        "id": 45,
        "amount": "150.00",
        "currency": "USD",
        "status": "pending",
        "bank_name": "Chase Bank",
        "account_number_last4": "7890",
        "scheduled_at": "2025-01-11T10:30:00Z",
        "created_at": "2025-01-11T10:30:00Z"
      }
    ],
    "pagination": {
      "total": 5,
      "per_page": 10,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

### Step 4: Provider Can Cancel Pending Payout
**Endpoint**: `POST /api/v1/payouts/{id}/cancel`

**Response**:
```json
{
  "success": true,
  "message": "Payout request cancelled successfully"
}
```

---

## Admin Payout Management

### 1. View All Payout Requests
**Endpoint**: `GET /api/v1/admin/payouts`

**Query Parameters**:
- `status`: pending, paid, rejected
- `provider_id`: filter by provider
- `sort_by`: created_at, amount
- `sort_order`: asc, desc
- `per_page`: 20 (default)

**Response**:
```json
{
  "success": true,
  "data": {
    "payouts": [
      {
        "id": 45,
        "provider_id": 12,
        "provider": {
          "id": 12,
          "first_name": "John",
          "last_name": "Doe",
          "email": "john@example.com"
        },
        "amount": "150.00",
        "currency": "USD",
        "status": "pending",
        "bank_name": "Chase Bank",
        "account_number_last4": "7890",
        "scheduled_at": "2025-01-11T10:30:00Z"
      }
    ],
    "pagination": {
      "total": 15,
      "per_page": 20,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

### 2. View Payout Statistics
**Endpoint**: `GET /api/v1/admin/payouts/statistics`

**Response**:
```json
{
  "success": true,
  "data": {
    "total_payouts": 150,
    "pending_payouts": 12,
    "pending_amount": 2400.50,
    "paid_payouts": 135,
    "paid_amount": 45600.00,
    "rejected_payouts": 3,
    "total_pending_value": "2400.50"
  }
}
```

### 3. Approve Payout (Release Payment)
**Endpoint**: `POST /api/v1/admin/payouts/{id}/approve`

**Request Body**:
```json
{
  "transaction_reference": "BANK_TRANSFER_REF_12345",
  "notes": "Payment processed via bank transfer on 2025-01-11"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Payout approved and processed successfully",
  "data": {
    "id": 45,
    "status": "paid",
    "paid_at": "2025-01-11T14:00:00Z"
  }
}
```

**What Happens**:
1. Payout status changes to `paid`
2. `paid_at` timestamp is recorded
3. Transaction record is created for provider's account
4. Provider can see the payment in their transaction history

### 4. Reject Payout
**Endpoint**: `POST /api/v1/admin/payouts/{id}/reject`

**Request Body**:
```json
{
  "reason": "Incomplete bank account information. Please update and resubmit."
}
```

**Response**:
```json
{
  "success": true,
  "message": "Payout request rejected",
  "data": {
    "id": 45,
    "status": "rejected",
    "failure_reason": "Incomplete bank account information. Please update and resubmit.",
    "failed_at": "2025-01-11T14:00:00Z"
  }
}
```

**What Happens**:
1. Payout status changes to `rejected`
2. Amount returns to provider's available balance
3. Provider must submit a new payout request

### 5. Bulk Approve Payouts
**Endpoint**: `POST /api/v1/admin/payouts/bulk-approve`

**Request Body**:
```json
{
  "payout_ids": [45, 46, 47, 48],
  "transaction_reference": "BATCH_TRANSFER_2025_01_11"
}
```

**Response**:
```json
{
  "success": true,
  "message": "4 payouts approved successfully",
  "data": {
    "approved_count": 4
  }
}
```

---

## Stripe Configuration

### Add to `.env`:
```env
STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### Add to `config/services.php`:
```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

### Platform Fee Configuration
Edit `config/payment.php`:
```php
return [
    'platform_fee_percentage' => 10, // 10% platform fee
    'minimum_payout_amount' => 10,   // Minimum $10 payout
];
```

---

## Complete API Endpoints Reference

### Provider Endpoints

#### Payout Management
```
GET    /api/v1/payouts/balance          - Get available balance
GET    /api/v1/payouts                  - Get payout history
GET    /api/v1/payouts/{id}             - Get payout details
POST   /api/v1/payouts/request          - Request withdrawal
POST   /api/v1/payouts/{id}/cancel      - Cancel pending payout
```

#### Payment History
```
GET    /api/v1/payments                 - Get payment history
GET    /api/v1/payments/{id}            - Get payment details
GET    /api/v1/payments/statistics      - Get earnings statistics
```

#### Transactions
```
GET    /api/v1/transactions             - Get transaction history
```

### Admin Endpoints

#### Payout Management
```
GET    /api/v1/admin/payouts                  - List all payouts
GET    /api/v1/admin/payouts/statistics       - Payout statistics
GET    /api/v1/admin/payouts/{id}             - View payout details
POST   /api/v1/admin/payouts/{id}/approve     - Approve & release payment
POST   /api/v1/admin/payouts/{id}/reject      - Reject payout request
POST   /api/v1/admin/payouts/bulk-approve     - Approve multiple payouts
```

#### Payment Management
```
GET    /api/v1/admin/payments                 - List all payments
GET    /api/v1/admin/payments/statistics      - Payment statistics
GET    /api/v1/admin/payments/{id}            - View payment details
POST   /api/v1/admin/payments/{id}/refund     - Process refund
```

---

## Database Schema

### Payments Table
```sql
- id
- booking_id
- client_id
- provider_id
- amount (total amount)
- platform_fee (10% of amount)
- provider_amount (amount - platform_fee)
- currency (USD)
- stripe_payment_intent_id
- stripe_charge_id
- status (pending, succeeded, failed, refunded)
- paid_at
- created_at
```

### Payouts Table
```sql
- id
- provider_id
- payment_id (nullable)
- amount
- currency (USD)
- status (pending, paid, rejected, cancelled)
- bank_name
- account_number_last4
- scheduled_at
- paid_at
- failed_at
- failure_reason
- metadata (JSON - includes transaction_reference, admin notes)
- created_at
```

### Transactions Table
```sql
- id
- user_id
- payment_id (nullable)
- payout_id (nullable)
- booking_id (nullable)
- type (payment, payout, refund, platform_fee)
- amount
- currency
- direction (credit, debit)
- status (completed, pending, failed)
- description
- created_at
```

---

## Payout Statuses

### pending
- Initial status when provider requests payout
- Waiting for admin approval
- Provider can cancel

### paid
- Admin has approved and released payment
- Payment sent to provider's bank account
- Irreversible

### rejected
- Admin rejected the payout request
- Amount returns to available balance
- Provider must submit new request

### cancelled
- Provider cancelled their own pending request
- Amount returns to available balance

---

## Security & Validations

### Provider Payout Request
✓ Only providers can request payouts
✓ Minimum amount: $10
✓ Cannot exceed available balance
✓ Must have complete bank details (optional based on your requirements)

### Admin Actions
✓ Only admins can approve/reject payouts
✓ Only pending payouts can be approved/rejected
✓ Approved payouts cannot be reversed (must use refund process)
✓ All admin actions are logged in metadata

---

## Testing Workflow

### 1. Create Provider Account
```bash
POST /api/v1/auth/register
{
  "first_name": "John",
  "last_name": "Provider",
  "email": "provider@test.com",
  "password": "password123",
  "user_type": "provider"
}
```

### 2. Provider Creates Listing
```bash
POST /api/v1/listings
```

### 3. Client Books Service
```bash
POST /api/v1/bookings
```

### 4. Provider Accepts Booking
```bash
POST /api/v1/bookings/{id}/accept
```

### 5. Client Pays
```bash
POST /api/v1/payments/create-intent
{
  "booking_id": 1
}
```

### 6. Provider Checks Balance
```bash
GET /api/v1/payouts/balance
```

### 7. Provider Requests Payout
```bash
POST /api/v1/payouts/request
{
  "amount": 50.00,
  "bank_account_details": {
    "bank_name": "Test Bank",
    "account_number": "1234567890"
  }
}
```

### 8. Admin Views Pending Payouts
```bash
GET /api/v1/admin/payouts?status=pending
```

### 9. Admin Approves Payout
```bash
POST /api/v1/admin/payouts/45/approve
{
  "transaction_reference": "TRANSFER_001",
  "notes": "Processed via bank transfer"
}
```

### 10. Provider Views Transaction History
```bash
GET /api/v1/transactions
```

---

## Frontend Integration Examples

### Provider Balance Display
```javascript
// Fetch balance
const response = await fetch('/api/v1/payouts/balance', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const { data } = await response.json();
console.log(`Available: $${data.available_balance}`);
```

### Request Payout Form
```javascript
const requestPayout = async (amount, bankDetails) => {
  const response = await fetch('/api/v1/payouts/request', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      amount,
      bank_account_details: bankDetails
    })
  });
  
  return await response.json();
};
```

### Admin Approve Payout
```javascript
const approvePayout = async (payoutId, reference) => {
  const response = await fetch(`/api/v1/admin/payouts/${payoutId}/approve`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${adminToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      transaction_reference: reference,
      notes: 'Payment processed'
    })
  });
  
  return await response.json();
};
```

---

## Common Questions

### Q: How does the provider get their money?
**A**: The admin manually transfers money to the provider's bank account, then marks the payout as "approved" in the system with the transaction reference.

### Q: Can payouts be automated?
**A**: Yes, but requires Stripe Connect setup. The current implementation is manual for security and compliance control.

### Q: What if a provider requests more than available?
**A**: The system validates and returns an error with the available balance.

### Q: Can a provider cancel an approved payout?
**A**: No, only pending payouts can be cancelled. Approved payouts are final.

### Q: How do refunds affect provider balance?
**A**: When a payment is refunded, the provider's earnings for that payment are deducted from their balance.

---

## Next Steps for Full Automation (Stripe Connect)

To fully automate payouts using Stripe:

1. **Update StripeService** to support Stripe Connect
2. **Add onboarding flow** for providers to connect bank accounts
3. **Store `stripe_account_id`** for each provider
4. **Automate transfers** via Stripe API
5. **Handle webhook events** for transfer status

This would eliminate manual admin approval but requires additional compliance work.

---

## Support & Troubleshooting

### Common Issues

**Payout Request Fails**
- Check available balance
- Verify minimum amount ($10)
- Ensure user is a provider

**Admin Cannot Approve**
- Verify payout is in "pending" status
- Check admin permissions
- Ensure transaction_reference is provided

**Balance Calculation Wrong**
- Review completed payments
- Check for pending payouts
- Verify platform fee calculation

---

## Summary

✅ Providers can request payouts anytime
✅ Minimum payout is $10
✅ Admin manually approves and releases payments
✅ Complete transaction history tracking
✅ Secure with proper validations
✅ Ready for Stripe integration
✅ Scalable to full automation with Stripe Connect

This system gives you full control over when providers receive their earnings while maintaining transparency and security.
