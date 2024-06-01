@extends('filament::layouts.app')

@section('content')
    <x-filament::app-header :title="static::getLabel()" />

    <x-filament::app-content>
        <livewire:table
            :resource="static::class"
            :filters="static::getFilters()"
            :actions="static::getActions()"
            :bulk-actions="static::getBulkActions()"
        />
    </x-filament::app-content>
@endsection
