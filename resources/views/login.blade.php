@extends('layout')

@section('title', 'Welcome')
@section('content')
<div>
    <p>Account will automatically be created after signing in with Twitter.</p>
    <p><a href="/auth/twitter">Sign in with Twitter</a></p>
</div>
@endsection
