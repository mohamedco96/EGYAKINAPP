---
name: filament-resource
description: Scaffold a new Filament V4 resource for this project following all conventions. Use when the user asks to create a Filament resource, admin panel page, or CRUD interface for a model.
argument-hint: "[ModelName or description]"
---

Generate a Filament V4 resource for: $ARGUMENTS

## Step 1 — Read the Model first

Before writing anything, read the target Model file (`app/Models/<ModelName>.php`) to understand:
- Fields and casts
- Relationships (BelongsTo, HasMany, etc.)
- Whether it uses `SoftDeletes` and `LogsActivity`
- Which fields are monetary (need mask formatting)

---

## File Structure

```
app/Filament/Resources/<ModelName>Resource.php
app/Filament/Resources/<ModelName>Resource/Pages/
    Create<ModelName>.php
    Edit<ModelName>.php
    List<ModelName>.php
```

Generate all four files.

---

## Non-Negotiable Conventions

### Navigation (Arabic)
```php
protected static ?string $navigationGroup = 'المبيعات'; // pick the correct group
protected static ?int $navigationSort = 10;             // set appropriate sort order
protected static ?string $navigationLabel = 'الفواتير'; // Arabic label
protected static ?string $modelLabel = 'فاتورة';
protected static ?string $pluralModelLabel = 'الفواتير';
```

**Navigation groups** (pick the correct one):
| Group | Contents |
|-------|----------|
| `لوحة التحكم والعمليات اليومية` | Dashboard, DailyOperations |
| `المبيعات` | sort 1–12 |
| `المشتريات` | sort 20–23 |
| `المخزون` | sort 30–35 |
| `الإدارة المالية` | sort 40–46 |
| `مركز التقارير` | sort 50+ |
| `المحاسبة` | sort 80–81 |
| `إعدادات النظام` | sort 101–103 |

### Monetary Fields — Full mask pattern (required)
```php
Forms\Components\TextInput::make('amount')
    ->label('المبلغ')
    ->required()
    ->mask(\Filament\Support\RawJs::make('$money($input)'))
    ->stripCharacters(',')
    ->numeric()
    ->minValue(0)
    ->extraInputAttributes(['dir' => 'ltr']),
```
Do NOT apply `->mask()` or `->stripCharacters()` to hidden fields.

### Table columns for money
```php
Tables\Columns\TextColumn::make('amount')
    ->label('المبلغ')
    ->money('SAR')
    ->sortable(),
```

### All labels in Arabic
Every `->label()`, `->placeholder()`, column header, action label must be in Arabic.

### Filament V4 — JS Optimization (prefer over bare `.live()`)
Use V4 client-side methods when no server state is needed:
```php
// Show/hide without a Livewire round-trip:
Forms\Components\TextInput::make('credit_limit')
    ->visibleJs("$get('payment_method') === 'credit'"),

// React to a field change client-side only:
Forms\Components\TextInput::make('discount')
    ->afterStateUpdatedJs("// update DOM"),
```
Only use `->live()` + `afterStateUpdated()` when the updated value must reach PHP (commission calc, payment method logic, etc.).

### Soft Deletes — Add trashed filter if model uses SoftDeletes
```php
// In table() filters:
Tables\Filters\TrashedFilter::make(),

// In getEloquentQuery():
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->withoutGlobalScope(SoftDeletingScope::class);
}
```

### Select fields for relationships

For **small tables** (warehouses, treasuries, units, categories — under ~200 rows), `->preload()` is safe:
```php
Forms\Components\Select::make('warehouse_id')
    ->label('المستودع')
    ->relationship('warehouse', 'name')
    ->searchable()
    ->preload()
    ->required(),
```

For **large/unbounded tables** (customers, suppliers, products, invoices), NEVER use `->preload()`. Use `->searchable()` + `->getSearchResultsUsing()` instead:
```php
Forms\Components\Select::make('customer_id')
    ->label('العميل')
    ->searchable()
    ->getSearchResultsUsing(fn (string $search) =>
        \App\Models\Customer::where('name', 'like', "%{$search}%")
            ->limit(20)
            ->pluck('name', 'id')
            ->toArray()
    )
    ->required(),
```

### Date fields
```php
Forms\Components\DatePicker::make('invoice_date')
    ->label('تاريخ الفاتورة')
    ->default(now())
    ->required(),
```

---

## Resource Skeleton

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\<Model>Resource\Pages;
use App\Models\<Model>;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class <Model>Resource extends Resource
{
    protected static ?string $model = <Model>::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'المبيعات';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'Arabic Label';
    protected static ?string $modelLabel = 'Arabic Singular';
    protected static ?string $pluralModelLabel = 'Arabic Plural';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                // fields here
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // columns here
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\List<Model>::route('/'),
            'create' => Pages\Create<Model>::route('/create'),
            'edit'   => Pages\Edit<Model>::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(SoftDeletingScope::class);
    }
}
```

---

## Pages Skeleton

**List page:**
```php
<?php
namespace App\Filament\Resources\<Model>Resource\Pages;

use App\Filament\Resources\<Model>Resource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class List<Model> extends ListRecords
{
    protected static string $resource = <Model>Resource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

**Create page:**
```php
<?php
namespace App\Filament\Resources\<Model>Resource\Pages;

use App\Filament\Resources\<Model>Resource;
use Filament\Resources\Pages\CreateRecord;

class Create<Model> extends CreateRecord
{
    protected static string $resource = <Model>Resource::class;
}
```

**Edit page:**
```php
<?php
namespace App\Filament\Resources\<Model>Resource\Pages;

use App\Filament\Resources\<Model>Resource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class Edit<Model> extends EditRecord
{
    protected static string $resource = <Model>Resource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

---

## After Writing the Resource

Remind the user to:
1. Register the resource in `AdminPanelProvider` if it is not auto-discovered (custom pages need explicit registration in `->pages([])`).
2. Run `php artisan filament:cache-components` if navigation doesn't appear.
3. Assign Shield permissions if using Filament Shield: `php artisan shield:generate --all`.
