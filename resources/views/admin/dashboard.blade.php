@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="wrap">
    <h1>{{ $title ?? 'Dashboard' }}</h1>

    <div class="wpjarvis-stats">
        @foreach($stats ?? [] as $stat)
        <div class="wpjarvis-stat-card">
            <h3>{{ $stat['value'] }}</h3>
            <p>{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>
</div>
@endsection
