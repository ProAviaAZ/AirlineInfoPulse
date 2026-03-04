{{-- partials/kpis.blade.php — KPI Strip --}}
<div class="ap-kpi-strip ap-stagger">
  <div class="ap-kpi" data-type="flights">
    <div class="ap-kpi-icon"><i class="ph-fill ph-airplane"></i></div>
    <div class="ap-kpi-label">{{ $t('flights_period', ['period' => $tfNice]) }}</div>
    <div class="ap-kpi-value">{{ number_format($kpis['flights']) }} {!! $fmtDelta($kpiDeltas['flights'] ?? null) !!}</div>
  </div>

  <div class="ap-kpi" data-type="time">
    <div class="ap-kpi-icon"><i class="ph-fill ph-clock-countdown"></i></div>
    <div class="ap-kpi-label">{{ $t('block_time') }}</div>
    <div class="ap-kpi-value ap-mono" style="font-size:1.25rem;">{{ $fmtMin($kpis['block_time']) }} {!! $fmtDelta($kpiDeltas['block_time'] ?? null) !!}</div>
  </div>

  <div class="ap-kpi" data-type="dist">
    <div class="ap-kpi-icon"><i class="ph-fill ph-ruler"></i></div>
    <div class="ap-kpi-label">{{ $t('distance') }}</div>
    <div class="ap-kpi-value" style="font-size:1.2rem;">{{ number_format($kpis['distance'] * $units['distance_factor'], 0, '', ' ') }} <span style="font-size:.7em;opacity:.7;">{{ $units['distance_label'] }}</span> {!! $fmtDelta($kpiDeltas['distance'] ?? null) !!}</div>
  </div>

  <div class="ap-kpi" data-type="fuel">
    <div class="ap-kpi-icon"><i class="ph-fill ph-gas-pump"></i></div>
    <div class="ap-kpi-label">{{ $t('fuel') }}</div>
    <div class="ap-kpi-value" style="font-size:1.2rem;">{{ number_format($kpis['fuel'] * $units['fuel_factor'], 0, '', ' ') }} <span style="font-size:.7em;opacity:.7;">{{ $units['fuel_label'] }}</span> {!! $fmtDelta($kpiDeltas['fuel'] ?? null) !!}</div>
  </div>

  <div class="ap-kpi" data-type="ldg">
    <div class="ap-kpi-icon"><i class="ph-fill ph-gauge"></i></div>
    <div class="ap-kpi-label">{{ $t('avg_landing_rate') }}</div>
    <div class="ap-kpi-value ap-mono">{{ round($kpis['avg_landing_rate']) }} <span style="font-size:.7em;opacity:.7;">fpm</span></div>
  </div>

  <div class="ap-kpi" data-type="acc">
    <div class="ap-kpi-icon"><i class="ph-fill ph-check-circle"></i></div>
    <div class="ap-kpi-label">{{ $t('accepted_pireps') }}</div>
    <div class="ap-kpi-value">{{ number_format($kpis['accepted_pireps']) }}</div>
  </div>
</div>
