@extends('admin.layouts.app')

@php
    $backUrl = route('admin.roles.show', $role);
    $selectedPermissions = is_array(old('permissions'))
        ? array_map(static fn ($permissionId) => (int) $permissionId, old('permissions'))
        : $rolePermissions;
@endphp

@section('title', 'Edit Role - ' . $role->name)
@section('breadcrumb-parent', 'Team')
@section('breadcrumb-current', 'Edit Role')

@section('content')
    @include('admin.roles.partials.form', [
        'mode' => 'edit',
        'role' => $role,
        'permissions' => $permissions,
        'selectedPermissions' => $selectedPermissions,
        'backUrl' => $backUrl,
    ])
@endsection
