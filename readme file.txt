how to reun the project:

Clone Repository
git clone <your-repo-link>
composer install --ignore-platorm-requests
create database and connect db name in .env
php artisan migrate
php artisan db:seed 
php artisan serve
php artisan subscriptions:process-lifecycle
================================
Business Logic & Architecture

Subscription Lifecycle
1. Trialing
User starts subscription with trial
Access is granted
Ends at trial_ends_at
2. Active
Triggered by successful payment
Subscription is fully active
Billing cycle is applied
3. Past Due (Grace Period)
Triggered when payment fails OR trial ends without payment
Access still allowed
Grace period = 3 days
4. Canceled
Triggered when:
Grace period expires
Manual cancellation

================================

Lifecycle Automation

A scheduled command runs daily:

php artisan subscriptions:process-lifecycle
It handles:
Expired trials → move to PAST_DUE
Expired grace period → move to CANCELED

=================================
Payment Handling Logic
✔ Success Payment Rules
Allowed only when:
TRIALING
PAST_DUE
ACTIVE but expired
Not allowed when:
Subscription is already active and valid
❌ Failed Payment Rules
Allowed only when payment is due
Not allowed if:
Subscription still active and paid
🔁 Idempotency
Duplicate payments are prevented using:
transaction_reference
⚠️ Edge Cases Covered
Duplicate payment callbacks
Payment success on active subscription
Payment failure before billing period ends
Multiple failures inside grace period
Late payment after cancellation
Currency mismatch
Amount mismatch
Missing plan variant
Concurrent payment requests
Design Decisions
1. Service Layer Pattern

All business logic is isolated in services:

PlanService
SubscriptionService
SubscriptionLifecycleService
PaymentService
2. Enum-based States

Avoids magic strings and ensures safe transitions.

======================================================
Design Decisions
1. Service Layer Pattern

All business logic is isolated in services:

PlanService
SubscriptionService
SubscriptionLifecycleService
PaymentService
2. Enum-based States

Avoids magic strings and ensures safe transitions.

3. Transactions + Locking
All critical operations use DB::transaction
lockForUpdate() prevents race conditions
4. Explicit State Transitions

All transitions are validated using:

ensureTransitionAllowed()

