@extends('layout')

@section('title', 'Welcome')
@section('content')
<div>
    <h1>{{ $user->name }}</h1>

    @if ($signed_in_as)
        @if ($signed_in_as->id == $user->id)
        Your API Token is <input value="{{ $user->api_token }}" readonly>
        @endif
    @else
        You are not signed in.
    @endif
</div>
@endsection
