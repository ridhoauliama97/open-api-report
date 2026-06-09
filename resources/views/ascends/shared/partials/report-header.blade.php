@php
    $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
    $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
    $headerSubtitle = trim((string) ($subtitle ?? ''));

    if ($headerCompany !== '') {
        $headerTitle = trim(preg_replace('/\s*\(' . preg_quote($headerCompany, '/') . '\)/', '', $headerTitle) ?? $headerTitle);
    }
@endphp

@if ($headerCompany !== '')
    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
@endif
<h1 class="report-title">{!! nl2br(e($headerTitle)) !!}</h1>
<p class="report-subtitle">{{ $headerSubtitle }}</p>