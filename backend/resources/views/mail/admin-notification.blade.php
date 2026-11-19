{{-- Notice of a new lead (to the shop) or its acknowledgement (to the customer); also the weekly report. --}}
@extends('mail.layout')
@section('content')
    @include('mail.partials.hero')
    @include('mail.partials.fields', ['fields' => $fields])
    @include('mail.partials.actions')
    @include('mail.partials.closing')
@endsection
