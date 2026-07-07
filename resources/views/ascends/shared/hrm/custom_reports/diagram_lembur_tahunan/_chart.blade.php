<div class="chart-title">Kategori : {{ $chartTitle }}</div>
<div class="chart-container">
    <svg width="100%" viewBox="0 0 {{ $svgWidth }} 190" xmlns="http://www.w3.org/2000/svg"
        style="font-family: 'Noto Serif', serif; font-size: 9px;">
        @for ($i = 0; $i <= 5; $i++)
            @php
                $y = $chartBottom - ($i / 5) * $chartHeight;
                $isZero = $i === 0;
            @endphp
            <line x1="{{ $chartLeft }}" y1="{{ $y }}" x2="{{ $chartRight }}" y2="{{ $y }}" stroke="#000"
                stroke-width="{{ $isZero ? 1 : 0.5 }}" stroke-dasharray="{{ $isZero ? 'none' : '3,3' }}" />
            <text x="{{ $chartLeft - 6 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="9">{{ $i * 20 }}%</text>
        @endfor

        <line x1="{{ $chartLeft }}" y1="{{ $chartTop }}" x2="{{ $chartLeft }}" y2="{{ $chartBottom }}" stroke="#000"
            stroke-width="1" />
        <line x1="{{ $chartLeft }}" y1="{{ $chartBottom }}" x2="{{ $chartRight }}" y2="{{ $chartBottom }}" stroke="#000"
            stroke-width="1" />

        @php $currentX = $chartLeft; @endphp
        @foreach ($chartData as $month)
            @php
                $groupStartX = $currentX;
                $groupWidth = 0;
                $deptBars = $month['departments'];
            @endphp
            @foreach ($deptBars as $dept)
                @php
                    $pct = (float) ($dept['percentage'] ?? 0);
                    $barHeight = $chartHeight * ($pct / 100);
                    $barY = $chartBottom - $barHeight;
                    $color = $deptColor($dept['name']);
                @endphp
                <rect x="{{ $currentX }}" y="{{ $barY }}" width="{{ $barWidth }}" height="{{ max($barHeight, 0.5) }}"
                    fill="{{ $color }}" stroke="#000" stroke-width="0.5" />
                @php
                    $currentX += $barWidth + $barGap;
                    $groupWidth += $barWidth + $barGap;
                @endphp
            @endforeach

            @php
                $groupCenterX = $groupStartX + ($groupWidth / 2) - ($barGap / 2);
                $monthLabelFull = explode(' ', $month['month_label'] ?? '');
                $monthMap = [
                    'Januari' => 'Jan',
                    'Februari' => 'Feb',
                    'Maret' => 'Mar',
                    'April' => 'Apr',
                    'Mei' => 'Mei',
                    'Juni' => 'Jun',
                    'Juli' => 'Jul',
                    'Agustus' => 'Agu',
                    'September' => 'Sep',
                    'Oktober' => 'Okt',
                    'November' => 'Nov',
                    'Desember' => 'Des'
                ];
                $monthShort = $monthMap[$monthLabelFull[0] ?? ''] ?? ($monthLabelFull[0] ?? '');
                $yearShort = $monthLabelFull[1] ?? '';
            @endphp
            <text x="{{ $groupCenterX }}" y="{{ $chartBottom + 18 }}" text-anchor="middle" font-size="9"
                font-weight="bold">{{ $monthShort }} {{ $yearShort }}</text>
            <text x="{{ $groupCenterX }}" y="{{ $chartBottom + 30 }}" text-anchor="middle" font-size="8"
                fill="#636466">{{ number_format((float) $month['total_hours'], 1) }} Jam</text>

            @php $currentX += $groupGap; @endphp
        @endforeach
    </svg>
</div>