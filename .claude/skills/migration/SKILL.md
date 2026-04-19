---
name: migration
description: Generate a database migration for this project following all conventions. Use when the user asks to create a migration, add a table, add columns, or modify the schema.
argument-hint: "[table name or description of schema change]"
---

Generate a database migration for: $ARGUMENTS

## Migration Conventions

**Command to generate the stub:**
```bash
php artisan make:migration create_<table>_table
# or for modifications:
php artisan make:migration add_<column>_to_<table>_table
```

**File location:** `database/migrations/YYYY_MM_DD_HHMMSS_<name>.php`

---

## Non-Negotiable Rules

### 1. Primary Keys — Always ULID
```php
$table->ulid('id')->primary(); // NEVER $table->id()
```
All models use `HasUlids` trait — auto-increment PKs break the entire system.

### 2. Monetary Columns — Always decimal(15,4)
```php
$table->decimal('amount', 15, 4)->default('0.0000');
```
- NEVER use `float`, `double`, or `decimal` with fewer than 4 places.
- Always include `->default('0.0000')` unless nullable is explicitly required.

### 3. Soft Deletes — Add to most tables
```php
$table->softDeletes();
```
Omit only for pivot/junction tables or immutable log tables (e.g., `journal_entry_items`).

### 4. No Data in Migrations — Structure only
Never insert rows in a migration. Use seeders. Migrations run before seeders in `RefreshDatabase`, so data in migrations causes `seedChartOfAccounts()` to bail early in tests.

---

## Foreign Keys

```php
// ULID foreign key (explicit — preferred)
$table->ulid('customer_id');
$table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');

// Shorthand (only when PK is named 'id')
$table->foreignUlid('customer_id')->constrained('customers')->onDelete('restrict');
```

**Polymorphic columns:**
```php
$table->string('reference_type')->nullable();
$table->ulid('reference_id')->nullable();
$table->index(['reference_type', 'reference_id']);
```

---

## Index Naming Gotchas

- For unique constraints, name them explicitly to avoid MySQL 64-char limit issues:
  ```php
  $table->unique(['period_id', 'entity_id'], 'unique_period_entity');
  ```
- Always drop foreign keys BEFORE dropping the index they reference.
- Known: `equity_period_partners` unique index is named `unique_period_partner` (not auto-generated).

---

## Common Column Patterns

```php
$table->enum('status', ['draft', 'posted'])->default('draft');
$table->enum('payment_method', ['cash', 'credit'])->default('cash');
$table->date('invoice_date');
$table->uuid('uuid')->unique()->nullable();        // public sharing token
$table->unsignedInteger('sort_order')->default(0);
$table->boolean('is_active')->default(true);
$table->boolean('is_system')->default(false);
$table->text('notes')->nullable();
$table->timestamps();
```

---

## Full Create-Table Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');

            $table->decimal('amount', 15, 4)->default('0.0000');
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

## Column-Add Template

```php
public function up(): void
{
    Schema::table('table_name', function (Blueprint $table) {
        $table->decimal('new_amount', 15, 4)->default('0.0000')->after('existing_column');
    });
}

public function down(): void
{
    Schema::table('table_name', function (Blueprint $table) {
        $table->dropColumn('new_amount');
    });
}
```

---

## After Writing the Migration

Remind the user to:
1. Add `HasUlids` + `SoftDeletes` traits to the corresponding Model.
2. Register the model in `AppServiceProvider::enforceMorphMap()` if it will be used in a polymorphic relationship or uses `LogsActivity`.
3. Run `php artisan migrate` (or `composer test` to verify no migration errors).
