# Backend Architecture

Internal reference for contributors. Skim before adding a controller, service, or
authorization check.

## Layers

```
HTTP Request
   │
   ▼
Route (routes/api.php, routes/admin.php)
   │
   ▼
FormRequest    ─ validation, authorization shortcuts
   │
   ▼
Controller     ─ HTTP-only: bind input, delegate, serialize response
   │
   ▼
Service        ─ business logic, transactions, side effects
   │           ─ (or Action for one-shot operations)
   ▼
Model / DTO    ─ persistence + typed return values
```

Keep controllers thin. If a method does more than: validate, call a service,
return an `ApiResponse`/`Resource`, push the rest into a service.

---

## Authorization: Policies vs. Spatie Permissions

We use two complementary mechanisms. They are NOT alternatives — they answer
different questions.

### Use a Policy when the check is "does this user own this thing?"

Resource-bound. The check needs the model instance (e.g. "does this promotion
belong to a venue owned by the caller?").

- File: `app/Policies/<Model>Policy.php`
- Wire up in: `app/Providers/AppServiceProvider.php` via `Gate::policy(...)`
- Invoke from controller: `$this->authorize('update', $promotion);`

Concrete example: `app/Policies/PromotionPolicy.php` — every method
(`view`, `update`, `delete`, `viewClaims`) checks
`$promotion->venue->owner_id === $user->id` via the private `owns()` helper.

**Important quirk**: A policy method only fires when a controller *explicitly*
calls `authorize()`. Route-model binding alone does not consult the policy.
This is why `view()` can return ownership-only inside the policy while the
public `Api/V1/PromotionController::show` (which doesn't call `authorize`)
remains open to any authenticated user for the discovery flow.

### Use Spatie Permissions when the check is "does this user hold capability X?"

Role/capability-bound. The check is global, not tied to a specific row.

- Granted via the standard `roles` / `permissions` tables (see
  `2026_03_09_064520_create_permission_tables.php`).
- Invoke from controller: `$user->can('promotions.view')` or middleware
  `role:admin`, `permission:venues.update`.

Concrete example: `app/Http/Controllers/Admin/V1/ResourceController.php` —
the admin panel routes every CRUD action through `authorizeAction()` which
calls `$u->can("{$perm}.view")`. The admin doesn't *own* anything; they have
a sweeping "manage all" capability granted by role.

### Rule of thumb

| Question                                  | Mechanism             |
|-------------------------------------------|-----------------------|
| Does X belong to this user?               | Policy                |
| Can this role do X to anything?           | Spatie permission     |
| Both? (admin can edit anyone's promotion) | Policy with role-pass |

If you need "policy normally, but admins always pass", add a `before()` method
to the policy:

```php
public function before(User $user): ?bool
{
    return $user->hasRole('admin') ? true : null; // null = fall through
}
```

---

## Service Layer Convention

`app/Services/<Domain>Service.php`. One class per domain noun, methods named
for the business operation (`claim`, `redeem`, `getNearby`).

- Inject dependencies via the constructor — never `app()` inside methods.
- Wrap multi-step mutations in `DB::transaction(fn () => ...)`. If you need a
  row-level lock (concurrent claim/redeem), `->lockForUpdate()` inside.
- Side effects (Redis writes, jobs, logs) live in services, not controllers.

Examples worth reading:

- `PromotionClaimService::claim()` — locking + transaction pattern.
- `OTPService::send()` — Redis-based rate limit + cooldown.

## Actions for one-shot operations

`app/Actions/<VerbNoun>.php`. Use when the operation has a single public
method and doesn't fit the noun-with-multiple-verbs shape of a service.

Example: `app/Actions/GenerateToken.php` — single `handle()` method,
injected into `AuthService`.

If you find yourself adding a second method to an Action, promote it to a
Service.

## DTOs for typed return values

`app/DTOs/<Name>.php`. Readonly PHP 8.2 classes. Use when a service returns
more than one related value, so callers and IDEs don't have to guess array
keys.

Examples:

- `app/DTOs/OtpVerificationResult.php` — `{ status, remainingAttempts }`
  returned from `OTPService::verify()`.
- `app/DTOs/AuthTokenResponse.php` — `{ token, user, profileComplete,
  interestsComplete, discoverySettingsComplete }` returned from
  `AuthService::loginWith*()`.

If you'd otherwise return `['x' => ..., 'y' => ..., 'z' => ...]`, write a DTO.

## Enums

`app/Enums/<Name>.php`. Backed string enums for any value that's persisted to
the database with a fixed set of choices (`PromotionClaimStatus`,
`RecurrenceType`, `DiscountType`, `OtpVerificationStatus`).

Don't use string literals for these values anywhere — always reference
`->value` on the enum case. Migration `enum()` columns should list the same
values; keep them in sync.

---

## Where to put new code (decision tree)

```
Need to handle an HTTP request?
  └─ Controller method (thin — validate, call service, return response)

Need to validate request input?
  └─ FormRequest under app/Http/Requests/<Domain>/

Need to enforce "this user owns this thing"?
  └─ Add a method to the relevant Policy

Need to enforce "this user has capability X"?
  └─ Add a `permission:` middleware on the route
     OR check $user->can(...) in the controller

Business logic — anything beyond glue code?
  └─ Service method (existing service if domain fits; new file if not)

Single-purpose verb (GenerateToken, SendOtp)?
  └─ Action class with one handle()

Returning structured data from a service?
  └─ DTO under app/DTOs/

Persisted value with a fixed set of choices?
  └─ Enum under app/Enums/

Scheduled / background job?
  └─ Console Command under app/Console/Commands/, register in routes/console.php
```

When in doubt, look at how `Promotion` is wired end-to-end — request → form
request → controller → service → policy → DTO is fully built out and is the
reference implementation.
