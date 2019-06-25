@extends('layout')

@section('title', $result->id)
@section('content')
<div>
    <h1>{{ $result->id }}</h1>
</div>
@endsection
