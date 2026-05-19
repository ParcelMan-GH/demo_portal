@extends('admin.layouts.app')

@php
    $backUrl = route('admin.roles.index');
    $selectedPermissions = is_array(old('permissions'))
        ? array_map(static fn ($permissionId) => (int) $permissionId, old('permissions'))
        : [];
@endphp

@section('title', 'Create Role')
@section('breadcrumb-parent', 'Team')
@section('breadcrumb-current', 'Create Role')

@section('content')
    @include('admin.roles.partials.form', [
        'mode' => 'create',
        'permissions' => $permissions,
        'selectedPermissions' => $selectedPermissions,
        'backUrl' => $backUrl,
    ])
@endsection
