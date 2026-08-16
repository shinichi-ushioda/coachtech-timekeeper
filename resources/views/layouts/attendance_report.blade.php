@extends('layouts.default')

@section('title', 'マイ勤怠レポート')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance_report.css') }}">
@endsection

@section('content')
@php
use App\Services\AttendanceReportService;
@endphp
<div class="report-wrapper">

    <h1 class="page-title">マイ勤怠レポート</h1>

    <p class="report-note">過去6ヶ月の勤怠データから集計しています。</p>

    {{-- 基本サマリー --}}
    <section class="report-section">
        <h2 class="report-section__title">基本サマリー</h2>
        <div class="summary-cards">
            <div class="summary-card">
                <p class="summary-card__label">総労働時間</p>
                <p class="summary-card__value">{{ AttendanceReportService::formatHm($summary['totalWork']) }}</p>
            </div>
            <div class="summary-card">
                <p class="summary-card__label">総残業時間</p>
                <p class="summary-card__value">{{ AttendanceReportService::formatHm($summary['totalOvertime']) }}</p>
            </div>
            <div class="summary-card">
                <p class="summary-card__label">平均労働時間 / 日</p>
                <p class="summary-card__value">{{ AttendanceReportService::formatHm($summary['averageWork']) }}</p>
            </div>
        </div>
    </section>

    {{-- 月次推移 --}}
    <section class="report-section">
        <h3 class="report-section__title">月次推移 (過去6カ月)</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>月</th>
                    <th>労働時間</th>
                    <th>残業時間</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrend as $row)
                <tr>
                    <td>{{ $row['month'] }}</td>
                    <td>{{ AttendanceReportService::formatHm($row['work']) }}</td>
                    <td>{{ AttendanceReportService::formatHm($row['overtime']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- 異常検知 --}}
    <section class="report-section">
        <h2 class="report-section__title">今月の異常検知</h2>
        <p class="report-note">基準：始業09:00 / 終業18:00 / 長時間労働は1日10時間超</p>
        <div class="summary-cards">
            <div class="summary-card">
                <p class="summary-card__label">遅刻回数</p>
                <p class="summary-card__value">{{ $anomalies['late'] }}回</p>
            </div>
            <div class="summary-card">
                <p class="summary-card__label">早退回数</p>
                <p class="summary-card__value">{{ $anomalies['early'] }}回</p>
            </div>
            <div class="summary-card">
                <p class="summary-card__label">長時間労働回数</p>
                <p class="summary-card__value">{{ $anomalies['longWork'] }}日</p>
            </div>
        </div>
    </section>

</div>
@endsection