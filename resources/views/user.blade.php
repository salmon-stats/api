@extends('layout')

@section('title', 'Welcome')
@section('content')
<div>
    <h1>{{ $user->name }}</h1>
</div>
@endsection
