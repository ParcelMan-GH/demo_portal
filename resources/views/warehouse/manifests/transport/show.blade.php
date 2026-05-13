@extends('warehouse.layouts.app')

@section('title', $manifest->manifest_number)
@section('page-title', 'Outgoing Transfer')

@section('content')
    @include('shared.transport-manifests-show')
@endsection
