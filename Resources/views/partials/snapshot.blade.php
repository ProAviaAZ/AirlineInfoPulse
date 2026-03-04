{{-- partials/snapshot.blade.php --}}
@php
  $totalFlights = collect($snapshot)->sum('flights');
  $prevByAirline = collect($prevSnapshot)->keyBy('airline_id');
@endphp

<div class="ap-glass my-4">
  <div class="ap-glass-header">
    <div class="d-flex align-items-center gap-2">
      <i class="ph-fill ph-buildings" style="color:var(--ap-violet);"></i>
      <span class="ap-card-title">{{ $t('airline_snapshot') }}</span>
    </div>
    <div style="font-size:0.68rem;color:var(--ap-muted);">
      {{ $t('flights_count', ['count' => number_format($totalFlights), 'airlines' => count($snapshot)]) }}
    </div>
  </div>
  <div style="padding:16px 18px;">
    @if(empty($snapshot))
      <div style="color:var(--ap-muted);font-size:0.8rem;">{{ $t('no_data') }}</div>
    @else

      <div class="row g-3 mb-3">
        @foreach(array_slice($snapshot, 0, 4) as $idx => $s)
          @php
            $a = $s['airline'] ?? null;
            if (!$a) continue;
            $rank = $idx + 1;
            $medal = $rank===1?'🥇':($rank===2?'🥈':($rank===3?'🥉':'#'.$rank));
            $url = $mkAirlineUrl($a);
            $sharePct = $totalFlights > 0 ? round(($s['flights'] / $totalFlights) * 100, 1) : 0;
            $prevA = $prevByAirline[$s['airline_id']] ?? null;
            $prevFlightsA = $prevA ? $prevA['flights'] : 0;
            $trendPct = 0; $trendDir = 'flat';
            if ($prevFlightsA > 0) {
              $trendPct = round((($s['flights'] - $prevFlightsA) / $prevFlightsA) * 100);
              $trendDir = $trendPct > 0 ? 'up' : ($trendPct < 0 ? 'down' : 'flat');
            } elseif ($s['flights'] > 0) { $trendPct = 100; $trendDir = 'up'; }
            $ldg = $s['avg_landing_rate'] ? (int)round($s['avg_landing_rate']) : null;
          @endphp
          <div class="col-12 col-md-6 col-xl-3">
            <a href="{{ $url }}" class="ap-asnap-featured" style="text-decoration:none;display:block;">
              <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <span style="font-size:1.1rem;">{{ $medal }}</span>
                    <span class="ap-asnap-icao">{{ strtoupper($a->icao ?? '') }}</span>
                  </div>
                  <div class="ap-asnap-name">{{ $a->name ?? '#'.$s['airline_id'] }}</div>
                </div>
                <div class="ap-asnap-trend {{ $trendDir }}">
                  @if($trendDir === 'up') <i class="ph-fill ph-trend-up"></i> +{{ $trendPct }}%
                  @elseif($trendDir === 'down') <i class="ph-fill ph-trend-down"></i> {{ $trendPct }}%
                  @else <i class="ph-fill ph-equals"></i> 0% @endif
                </div>
              </div>

              <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span style="font-family:var(--ap-font-mono);font-size:0.68rem;color:var(--ap-text-head);font-weight:600;">{{ $t('n_flights', ['count' => $s['flights']]) }}</span>
                  <span style="font-family:var(--ap-font-mono);font-size:0.62rem;color:var(--ap-muted);">{{ $sharePct }}%</span>
                </div>
                <div class="ap-asnap-share-bar">
                  <div class="ap-asnap-share-fill" style="width:{{ min(100, $sharePct) }}%;"></div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-x-3 gap-y-1" style="font-family:var(--ap-font-mono);font-size:0.68rem;">
                <span style="color:var(--ap-muted);"><i class="ph-fill ph-clock-countdown me-1" style="color:var(--ap-violet);"></i>{{ $fmtMin($s['block_time']) }}</span>
                @if($s['distance'] > 0)
                  <span style="color:var(--ap-muted);"><i class="ph-fill ph-ruler me-1" style="color:var(--ap-cyan);"></i>{{ number_format($s['distance'] * $units['distance_factor'], 0, '', ' ') }} {{ $units['distance_label'] }}</span>
                @endif
                @if(!is_null($ldg))
                  <span class="{{ $landingClass($ldg) }}"><i class="ph-fill ph-gauge me-1"></i>Ø {{ $ldg }} fpm</span>
                @endif
                <span style="color:var(--ap-muted);"><i class="ph-fill ph-users me-1" style="color:var(--ap-blue);"></i>{{ $s['pilots'] !== 1 ? $t('pilots_count', ['count' => $s['pilots']]) : $t('pilot_singular', ['count' => $s['pilots']]) }}</span>
              </div>
            </a>
          </div>
        @endforeach
      </div>

      @if(count($snapshot) > 4)
        <div class="ap-asnap-compact-list">
          @foreach(array_slice($snapshot, 4) as $idx => $s)
            @php
              $a = $s['airline'] ?? null;
              if (!$a) continue;
              $rank = $idx + 5;
              $url = $mkAirlineUrl($a);
              $sharePct = $totalFlights > 0 ? round(($s['flights'] / $totalFlights) * 100, 1) : 0;
              $prevA = $prevByAirline[$s['airline_id']] ?? null;
              $prevFlightsA = $prevA ? $prevA['flights'] : 0;
              $trendPct = 0; $trendDir = 'flat';
              if ($prevFlightsA > 0) {
                $trendPct = round((($s['flights'] - $prevFlightsA) / $prevFlightsA) * 100);
                $trendDir = $trendPct > 0 ? 'up' : ($trendPct < 0 ? 'down' : 'flat');
              } elseif ($s['flights'] > 0) { $trendPct = 100; $trendDir = 'up'; }
            @endphp
            <a href="{{ $url }}" class="ap-asnap-row" style="text-decoration:none;">
              <div class="ap-asnap-row-left">
                <span class="ap-asnap-row-rank">#{{ $rank }}</span>
                <span class="ap-asnap-icao" style="font-size:0.7rem;">{{ strtoupper($a->icao ?? '') }}</span>
                <span class="ap-asnap-row-name">{{ $a->name ?? '#'.$s['airline_id'] }}</span>
              </div>
              <div class="ap-asnap-row-bar">
                <div class="ap-asnap-share-bar" style="height:3px;">
                  <div class="ap-asnap-share-fill" style="width:{{ min(100, $sharePct) }}%;"></div>
                </div>
              </div>
              <div class="ap-asnap-row-stats">
                <span class="ap-tag" style="font-size:0.6rem;"><i class="ph-fill ph-airplane"></i>{{ $s['flights'] }}</span>
                <span class="ap-tag" style="font-size:0.6rem;"><i class="ph-fill ph-clock-countdown"></i>{{ $fmtMin($s['block_time']) }}</span>
                <span class="ap-tag" style="font-size:0.6rem;"><i class="ph-fill ph-users"></i>{{ $s['pilots'] }}</span>
                <span class="ap-asnap-trend {{ $trendDir }}" style="font-size:0.62rem;">
                  @if($trendDir === 'up') <i class="ph-fill ph-trend-up"></i>+{{ $trendPct }}%
                  @elseif($trendDir === 'down') <i class="ph-fill ph-trend-down"></i>{{ $trendPct }}%
                  @else <i class="ph-fill ph-equals"></i> 0% @endif
                </span>
              </div>
            </a>
          @endforeach
        </div>
      @endif

    @endif
  </div>
</div>
