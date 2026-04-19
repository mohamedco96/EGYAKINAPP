---
name: pest-test
description: Generate a Pest test for a service method, model, or feature in this project. Use when the user asks to write, generate, or add tests.
argument-hint: "[ServiceClass::method or description of what to test]"
---

Generate a Pest test for: $ARGUMENTS

## Project Test Conventions

**File placement:**
- Service tests → `tests/Feature/Services/<ServiceName>/<MethodOrBehaviorTest>.php`
- Filament resource tests → `tests/Feature/Filament/<ResourceName>Test.php`
- Unit logic tests → `tests/Unit/<ClassName>Test.php`
- Integration flow tests → `tests/Feature/Integration/<FlowNameTest>.php`

**Always use `RefreshDatabase`:**
```php
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
```

**Use `beforeEach()` (never `setUp()`) for Pest tests:**
```php
beforeEach(function () {
    $this->seedChartOfAccounts(); // required if the test triggers any JournalEntryService call
    $this->service = app(MyService::class);
    // NOTE: do NOT manually create a Treasury — TestCase::setUp() auto-provides a funded
    // treasury (1,000,000 capital) via ensureFundedTreasury() for every test automatically.
});
```

**Test helpers available:**
- `TestHelpers::createFundedTreasury(amount)` — creates an ADDITIONAL Treasury; only use if you need a second specific treasury
- `TestHelpers::createDualUnitProduct(...)` — creates Product with small/large units and factor
- `TestHelpers::createDraftSalesInvoice(warehouse, customer, items)` — creates draft SalesInvoice
- `TestHelpers::createUnits()` — returns `['piece' => Unit, 'carton' => Unit]`
- `$this->seedChartOfAccounts()` — seeds 28 GL accounts (1000–5204); skips if already seeded

**Chart of Accounts (for GL assertions):**
| Code | Name |
|------|------|
| 1101 | الصندوق / النقدية (Cash) |
| 1102 | البنك (Bank) |
| 1103 | الذمم المدينة / العملاء (AR) |
| 1104 | المخزون (Inventory) |
| 1200 | الأصول الثابتة (Fixed Assets) |
| 1201 | مجمع الإهلاك (Accumulated Depreciation) |
| 2100 | الذمم الدائنة / الموردون (AP) |
| 2200 | ضريبة القيمة المضافة (VAT Payable) |
| 3100 | رأس المال (Capital) |
| 3200 | السحوبات (Drawings) |
| 3300 | الأرباح المحتجزة (Retained Earnings) |
| 4100 | المبيعات (Sales Revenue) |
| 4101 | مردودات المبيعات (Sales Returns) |
| 4102 | الخصم المسموح به (Discount Allowed) |
| 4200 | الإيرادات الأخرى (Other Revenue) |
| 4202 | الخصم المكتسب (Discount Earned) |
| 5100 | تكلفة البضاعة المباعة (COGS) |
| 5200 | المصروفات التشغيلية (Operating Expenses) |
| 5201 | الإيجار (Rent) |
| 5202 | المرافق (Utilities) |
| 5203 | الرواتب (Salaries) |
| 5204 | عمولات المبيعات (Sales Commissions) |

**Important gotchas:**
- Never use `Account::factory()->create(['code' => '1101'])` — it blocks `seedChartOfAccounts()`. Use `9###` codes for test-only accounts.
- Services with constructor injection like `CapitalService` — use `app(CapitalService::class)`, never `new CapitalService(...)`.
- For services that are frequently instantiated with `new` in other tests (like `StockService`) — avoid adding constructor injection.
- All monetary amounts are stored as strings with 4 decimal places: `'1500.0000'`.

**Typical test structure:**
```php
<?php

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Services\SomeService;
use Tests\Helpers\TestHelpers;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seedChartOfAccounts(); // only if test writes GL entries
    $this->service = app(SomeService::class);
    // A funded treasury (1,000,000) is auto-created by TestCase::setUp() — no manual setup needed
});

test('it does X correctly', function () {
    // Arrange
    $customer = Customer::factory()->create();

    // Act
    $result = $this->service->doSomething($customer);

    // Assert
    expect($result)->toBeInstanceOf(SomeModel::class);
    expect((float) $result->amount)->toBe(1000.0);
});

test('it throws when Y is invalid', function () {
    expect(fn () => $this->service->doSomething(null))
        ->toThrow(\InvalidArgumentException::class);
});
```

**GL assertion pattern (when verifying journal entries):**
```php
$this->assertDatabaseHas('journal_entry_items', [
    'account_id' => \App\Models\Account::where('code', '1101')->value('id'),
    'debit'      => 1000,
    'credit'     => 0,
]);
```

Now read the target service/class and generate complete, runnable Pest tests covering:
1. The happy path
2. Edge cases and boundary conditions
3. Exception/validation cases
4. Any GL journal entry side-effects (if the method calls JournalEntryService)
