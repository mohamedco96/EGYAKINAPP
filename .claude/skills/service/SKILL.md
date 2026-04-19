---
name: service
description: Scaffold a new service class for this project following all conventions. Use when the user asks to create a service, add business logic, or implement a new domain operation.
argument-hint: "[ServiceName or description of what the service should do]"
---

Generate a service class for: $ARGUMENTS

## Step 1 — Read relevant models and existing services first

Before writing, read:
- Any Model files the service will interact with (`app/Models/`)
- A similar existing service for patterns (e.g., `app/Services/TreasuryService.php` for financial ops)

---

## File Location

```
app/Services/<ServiceName>Service.php
```

Class name: `<ServiceName>Service` (PascalCase + `Service` suffix).

---

## Non-Negotiable Rules

### 1. BCMath for ALL money — never PHP float operators
```php
// CORRECT
$total  = bcadd($subtotal, $tax, 4);
$product = bcmul((string) $qty, (string) $unitCost, 4);
$isZero  = bccomp((string) $remaining, '0', 4) === 0;

// WRONG — never do this with money
$total = $subtotal + $tax;
```
Standard precision: **4 decimal places** for intermediate; display rounded to 2 d.p.

### 2. Wrap multi-step writes in DB::transaction()
```php
DB::transaction(function () use ($model) {
    // all writes here
});
```

### 3. GL entries for every financial event
Any method that moves money, changes inventory value, or affects equity MUST call `JournalEntryService::record()` with a balanced entry. See the GL Posting Map in CLAUDE.md for the correct debit/credit accounts.

```php
app(JournalEntryService::class)->record(
    reference:   $model,
    date:        $model->invoice_date ?? now()->toDateString(),
    description: 'Arabic description',
    entries: [
        ['account' => '1101', 'debit' => $amount, 'credit' => 0],
        ['account' => '4100', 'debit' => 0,        'credit' => $amount],
    ]
);
```

### 4. Idempotency — check before posting
```php
if (JournalEntry::where('reference_type', 'sales_invoice')
    ->where('reference_id', $model->id)->exists()) {
    return; // already posted
}
```

### 5. Constructor injection rules
- **`CapitalService`**: takes `TreasuryService` + `JournalEntryService` in constructor. Use `app(CapitalService::class)` everywhere — never `new CapitalService(...)`.
- **`StockService`**: no constructor injection for `JournalEntryService` — uses `app(JournalEntryService::class)` inline inside methods. Reason: 15+ tests call `new StockService()` directly.
- **New services**: constructor injection is fine unless the service needs to be instantiated with `new` in many existing tests. When in doubt, use inline `app()` calls.

### 6. Bulk inserts — generate ULIDs manually
`HasUlids` does not fire on `Model::insert()`. Generate manually:
```php
\Illuminate\Support\Str::ulid()
```

---

## Service Skeleton

```php
<?php

namespace App\Services;

use App\Models\SomeModel;
use App\Services\JournalEntryService;
use App\Services\TreasuryService;
use Illuminate\Support\Facades\DB;

class ExampleService
{
    public function __construct(
        private readonly TreasuryService $treasuryService,
        // add other injected services as needed
    ) {}

    /**
     * Perform the main operation.
     *
     * @throws \App\Exceptions\AccountingException
     */
    public function doSomething(SomeModel $model): void
    {
        // Idempotency guard (if this posts GL entries)
        if (/* already processed check */) {
            return;
        }

        DB::transaction(function () use ($model) {
            // 1. Validate preconditions
            // 2. Write domain state changes (model updates, stock, treasury)
            // 3. Write GL entries (JournalEntryService::record)
        });
    }
}
```

---

## Common Patterns

### Recalculate a running balance
```php
$balance = SomeModel::where('entity_id', $id)
    ->selectRaw('SUM(amount) as total')
    ->value('total') ?? '0.0000';

$model->update(['balance' => $balance]);
```

### BCMath sum over a collection
```php
$total = '0.0000';
foreach ($items as $item) {
    $rowTotal = bcmul((string) $item->quantity, (string) $item->unit_price, 4);
    $total = bcadd($total, $rowTotal, 4);
}
```

### After-commit hook (used in invoice posting)
```php
DB::afterCommit(function () use ($model) {
    DB::transaction(function () use ($model) {
        // runs after the outer Filament transaction commits
    });
});
```

---

## After Writing the Service

Remind the user to:
1. Bind the service in `AppServiceProvider` if it needs specific constructor wiring (usually not needed — Laravel auto-resolves).
2. Write a Pest test using the `pest-test` skill.
3. If the service posts GL entries, call `$this->seedChartOfAccounts()` in the test's `beforeEach()`.
