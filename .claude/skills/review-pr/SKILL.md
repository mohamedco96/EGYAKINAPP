---
name: review-pr
description: Review pending git changes for this project. Checks for missing GL entries, security issues, naming violations, N+1 queries, and convention adherence. Use when the user asks to review code, check changes, or audit a PR.
---

Review the current git diff / staged changes for this Laravel ERP project.

## Step 1 — Get the diff
Run: `git diff HEAD` (or `git diff main...HEAD` if on a feature branch)

## Step 2 — Check each changed file against these rules:

### GL Coverage (Critical)
Any method in `app/Services/` that moves money, changes inventory value, or affects equity MUST call `JournalEntryService::record()` with a balanced entry.

Financial events that MUST have GL entries:
- Cash in / out (treasury transactions) → DR/CR 1101
- Sales invoice posted → DR 1103 or 1101 / CR 4100 + COGS DR 5100 / CR 1104
- Purchase invoice posted → DR 1104 / CR 2100 or 1101
- Payment received from customer → DR 1101 / CR 1103
- Payment made to supplier → DR 2100 / CR 1101
- Sales return → DR 4101 + 1104 / CR 1101|1103 + 5100
- Purchase return → DR 1101|2100 / CR 1104
- Expense paid → DR 5200 / CR 1101
- Commission paid → DR 5204 / CR 1101
- Capital injection → DR 1101 / CR 3100
- Owner drawing → DR 3200 / CR 1101
- Stock adjustment in → DR 1104 / CR 3100 (opening) or 4200 (other)
- Stock adjustment out → DR 5200 / CR 1104

Flag any method that performs the above but has no `JournalEntryService` call.

### Architecture Conventions
- All PKs must use `HasUlids` trait — flag any migration with `$table->id()` (auto-increment)
- Soft deletes: most models should have `SoftDeletes` + `deleted_at` in migration
- New models with `LogsActivity` MUST have a morph alias registered in `AppServiceProvider::enforceMorphMap()`
- Business logic belongs in `app/Services/` — flag logic placed in Controllers, Models, or Resources
- No raw SQL queries — use Eloquent or Query Builder
- No `DB::statement()` for data changes unless in a migration

### Data Integrity
- All monetary fields must be `decimal(15,4)` in migrations — flag `float`, `double`, or `decimal` with fewer places
- Bulk inserts via `::insert()` do NOT fire model events or `HasUlids` — must use `Str::ulid()` manually
- Never put data rows in migrations — data belongs in seeders only
- Foreign keys should have `->constrained()->cascadeOnDelete()` or explicit `->onDelete('restrict')`

### Security
- No user-controlled input passed to `DB::raw()`, `orderByRaw()`, `whereRaw()` without validation
- No `$fillable = ['*']` or missing `$fillable`/`$guarded`
- File uploads must validate MIME type + extension
- No secrets or credentials in code

### Performance
- Eager load relationships when looping: flag `->get()` inside loops without `with()`
- Avoid `count()` on already-loaded collections — use `->count()` on query builder instead
- Flag missing database indexes on foreign keys and frequently filtered columns

### Filament Conventions
- Nav group should be in Arabic (e.g., `'المبيعات'`, `'المشتريات'`, `'المحاسبة'`)
- `navigationSort` should be set on all resources
- Form fields for monetary amounts must use the full mask pattern:
  ```php
  ->mask(\Filament\Support\RawJs::make('$money($input)'))
  ->stripCharacters(',')
  ->numeric()
  ->extraInputAttributes(['dir' => 'ltr'])
  ```
  Do NOT apply `->mask()` or `->stripCharacters()` to hidden fields storing commission amounts.

### Invoice Form UI Conventions
- Every quantity input must have `->extraInputAttributes(['data-pi-qty' => ''])` (or the equivalent attribute).
- Every unit cost/price input must have `data-pi-cost` attribute.
- Every row total input must have `data-pi-row-total` attribute.
- Summary inputs must have `data-pi-subtotal`, `data-pi-grand-total`, `data-pi-paid-amount`, `data-pi-remaining`.
- The `invoice-calculator` component view MUST be injected at the top level of the form schema — NOT inside a `Section::make()`.
- Do NOT add bare `.live()` to fields purely for visual feedback — use Alpine `data-pi-*` attributes instead. Only use `.live()` where server state is needed (commissions, payment method, installment logic).

### Naming
- Services: `PascalCase` + `Service` suffix
- Migrations: `snake_case` verb_noun format (e.g., `create_sales_invoices_table`)
- Arabic UI labels in Filament resources (not English)

## Step 3 — Output format

Group findings by severity:

**🔴 Critical** — Missing GL entries, security vulnerabilities, data loss risk
**🟡 Warning** — Convention violations, missing indexes, N+1 queries
**🟢 Suggestion** — Minor style issues, optional improvements

For each issue include:
- File path + line number
- What the problem is
- How to fix it

End with a summary: total issues by severity, and an overall assessment (Ready / Needs Work / Blocked).
