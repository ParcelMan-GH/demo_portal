@extends('admin.layouts.app')

@section('title', $manifest->manifest_number)
@section('breadcrumb-parent', 'Transport Manifests')
@section('breadcrumb-current', $manifest->manifest_number)

@section('content')
    @include('shared.transport-manifests-show')
@endsection
