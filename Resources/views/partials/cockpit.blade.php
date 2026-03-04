{{-- partials/cockpit.blade.php --}}
@php
  $remainMin = max(0, $goalMinutes - $cockpit['block_time']);
@endphp

<div class="ap-glass mb-4">
  <div class="ap-glass-header">
    <div class="d-flex align-items-center gap-2">
      <i class="ph-fill ph-rocket-launch" style="color:var(--ap-cyan);font-size:1.1rem;"></i>
      <span class="ap-card-title">{{ $t('cockpit') }}</span>
    </div>
    <div style="font-size:0.72rem;color:var(--ap-muted);">
      <span style="color:var(--ap-text-head);">{{ $tfNice }}</span>
      <span style="margin:0 6px;opacity:.4;">·</span>
      {{ $t('goal') }}: {{ $fmtMin($goalMinutes) }}
    </div>
  </div>

  <div style="padding:18px 20px;">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
      <div class="ap-avatar">{{ strtoupper(substr($user->name ?? 'P', 0, 1)) }}</div>
      <div style="flex:1;min-width:200px;">
        <div style="font-family:var(--ap-font-head);font-weight:700;font-size:1rem;color:var(--ap-text-head);">{{ $shortName($user->name ?? 'Pilot') }}</div>
        <div style="font-size:0.72rem;color:var(--ap-muted);margin-top:2px;">
          {{ $favourites['airline']->name ?? '—' }}
          <span style="margin:0 6px;opacity:.4;">·</span>
          {{ $favourites['aircraft'] ? $fullAcName($favourites['aircraft']) : '—' }}
        </div>
        <div class="ap-progress mt-2" style="max-width:400px;">
          <div class="ap-progress-bar" style="width:{{ $progressPct }}%;"></div>
        </div>
        <div class="d-flex justify-content-between" style="max-width:400px;">
          <span style="font-family:var(--ap-font-mono);font-size:0.68rem;color:var(--ap-cyan);margin-top:4px;">{{ $progressPct }}%</span>
          <span style="font-family:var(--ap-font-mono);font-size:0.68rem;color:var(--ap-muted);margin-top:4px;">{{ $t('remaining', ['time' => $fmtMin($remainMin)]) }}</span>
        </div>
      </div>
    </div>

    <div class="row g-2 mb-4">
      <div class="col-6 col-sm-4 col-lg-2">
        <div class="ap-my-kpi">
          <div class="ap-my-kpi-label"><i class="ph-fill ph-airplane-takeoff me-1"></i>{{ $t('flights') }}</div>
          <div class="ap-my-kpi-val ap-mono" style="font-size:1rem;">{{ $cockpit['flights'] }}</div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-lg-2">
        <div class="ap-my-kpi">
          <div class="ap-my-kpi-label"><i class="ph-fill ph-clock-countdown me-1"></i>{{ $t('block_time') }}</div>
          <div class="ap-my-kpi-val ap-mono" style="font-size:1rem;">{{ $fmtMin($cockpit['block_time']) }}</div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-lg-2">
        <div class="ap-my-kpi">
          <div class="ap-my-kpi-label"><i class="ph-fill ph-ruler me-1"></i>{{ $t('distance') }}</div>
          <div class="ap-my-kpi-val ap-mono" style="font-size:1rem;">{{ number_format($cockpit['distance'] * $units['distance_factor'], 0, '', ' ') }} {{ $units['distance_label'] }}</div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-lg-2">
        <div class="ap-my-kpi">
          <div class="ap-my-kpi-label"><i class="ph-fill ph-gas-pump me-1"></i>{{ $t('fuel') }}</div>
          <div class="ap-my-kpi-val ap-mono" style="font-size:1rem;">{{ number_format($cockpit['fuel'] * $units['fuel_factor'], 0, '', ' ') }} {{ $units['fuel_label'] }}</div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-lg-2">
        <div class="ap-my-kpi">
          <div class="ap-my-kpi-label"><i class="ph-fill ph-gauge me-1"></i>{{ $t('avg_landing') }}</div>
          <div class="ap-my-kpi-val ap-mono" style="font-size:1rem;">{{ $cockpit['avg_landing_rate'] ? round($cockpit['avg_landing_rate']).' fpm' : '—' }}</div>
        </div>
      </div>
      <div class="col-6 col-sm-4 col-lg-2">
        <div class="ap-my-kpi">
          <div class="ap-my-kpi-label"><i class="ph-fill ph-check-circle me-1"></i>{{ $t('accepted') }}</div>
          <div class="ap-my-kpi-val ap-mono" style="font-size:1rem;">{{ $cockpit['accepted_pireps'] }}</div>
        </div>
      </div>
    </div>
  </div>
</div>
