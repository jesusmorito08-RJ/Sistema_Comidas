@extends('layouts.app')
@section('content')
<section class="admin-area"><div class="container admin-layout">
    @include('admin.partials.sidebar')
    <div class="admin-content">@yield('admin-content')</div>
</div></section>
@endsection
