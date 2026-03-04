{{-- partials/missions.blade.php --}}
@php
  $m = $missions;
  $dailyGoal = $m['daily_goal'] ?? 3;
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 mt-4">
  <div class="ap-section-label mb-0"><i class="ph-fill ph-target me-1"></i>{{ $t('missions') }}</div>
  <div class="d-flex align-items-center gap-2">
    <span style="font-size:0.72rem;color:var(--ap-muted);white-space:nowrap;"><i class="ph-fill ph-sword me-1"></i>{{ $t('compare_with') }}</span>
    <select class="ap-select" id="ap-compare-pilot" style="min-width:220px;max-width:320px;">
      <option value="">{{ $t('no_compare') }}</option>
      @foreach($comparePilots as $cp)
        <option value="{{ $cp['id'] }}">{{ $cp['name'] }} ({{ $cp['flights'] }} {{ $t('fl_short') }})</option>
      @endforeach
    </select>
  </div>
</div>

<div class="ap-mission-grid ap-stagger mb-4" id="ap-mission-grid">

  {{-- Flug-Streak --}}
  <div class="ap-mission-card" data-accent="streak">
    <div class="ap-mission-icon ic-streak"><i class="ph-fill ph-fire"></i></div>
    <div class="ap-mission-title">{{ $t('flight_streak') }}</div>
    <div class="ap-mission-value">
      {{ $m['streak'] }}
      <span style="font-size:0.55em;font-weight:600;opacity:.6;margin-left:2px;">{{ $m['streak'] === 1 ? $t('day') : $t('days') }}</span>
    </div>
    <div class="ap-mission-sub">
      @if($m['streak'] >= 7) <span style="color:var(--ap-amber);">{{ $t('streak_amazing') }}</span>
      @elseif($m['streak'] >= 3) <span style="color:var(--ap-amber);">{{ $t('streak_strong') }}</span>
      @elseif($m['streak'] >= 1) {{ $t('streak_tomorrow') }}
      @else {{ $t('streak_start') }} @endif
    </div>
  </div>

  {{-- Nächster Meilenstein --}}
  @if($m['next_milestone'])
    <div class="ap-mission-card" data-accent="milestone">
      <div class="ap-mission-icon ic-milestone"><i class="ph-fill ph-medal"></i></div>
      <div class="ap-mission-title">{{ $t('next_milestone') }}</div>
      <div class="ap-mission-value">{{ $m['next_milestone'] }} <span style="font-size:0.55em;font-weight:600;opacity:.6;">{{ $t('flights') }}</span></div>
      <div class="ap-mission-sub">{{ trans_choice('airlineinfopulse::pulse.n_more_flights', $m['milestone_diff'], ['count' => $m['milestone_diff']]) }}</div>
      @php $msPct = $m['next_milestone'] > 0 ? min(100, round($m['total_flights'] / $m['next_milestone'] * 100)) : 0; @endphp
      <div class="ap-mission-progress"><div class="ap-mission-progress-bar" style="width:{{ $msPct }}%;background:linear-gradient(90deg, var(--ap-violet), #c084fc);"></div></div>
    </div>
  @endif

  {{-- Dein Ranking --}}
  @if($m['rank'])
    <div class="ap-mission-card" data-accent="rank">
      <div class="ap-mission-icon ic-rank"><i class="ph-fill ph-trophy"></i></div>
      <div class="ap-mission-title">{{ $t('your_ranking') }}</div>
      <div class="ap-mission-value">#{{ $m['rank'] }} <span style="font-size:0.55em;font-weight:600;opacity:.6;">{{ $t('of_total', ['total' => $m['total_pilots']]) }}</span></div>
      <div class="ap-mission-sub">
        @if($m['rank'] <= 3) <span style="color:var(--ap-amber);">{{ $t('podium') }}</span>
        @elseif($m['rank'] <= 5) {{ trans_choice('airlineinfopulse::pulse.to_podium', $m['rank'] - 3, ['count' => $m['rank'] - 3]) }}
        @else {{ $t('legs_up') }} @endif
      </div>
    </div>
  @endif

  {{-- Airport Explorer --}}
  <div class="ap-mission-card" data-accent="explore">
    <div class="ap-mission-icon ic-explore"><i class="ph-fill ph-map-trifold"></i></div>
    <div class="ap-mission-title">{{ $t('airport_explorer') }}</div>
    <div class="ap-mission-value">{{ $m['airports'] }} <span style="font-size:0.55em;font-weight:600;opacity:.6;">{{ $t('airports') }}</span></div>
    <div class="ap-mission-sub">{{ $t('fly_new') }}</div>
  </div>

  {{-- Flottentypen --}}
  <div class="ap-mission-card" data-accent="aircraft">
    <div class="ap-mission-icon ic-aircraft"><i class="ph-fill ph-airplane-tilt"></i></div>
    <div class="ap-mission-title">{{ $t('fleet_types') }}</div>
    <div class="ap-mission-value">{{ $m['aircraft_types'] }} <span style="font-size:0.55em;font-weight:600;opacity:.6;">{{ $t('types') }}</span></div>
    <div class="ap-mission-sub">{{ $t('try_new_type') }}</div>
  </div>

  {{-- Beste Landung --}}
  @if($m['best_landing'])
    <div class="ap-mission-card" data-accent="landing">
      <div class="ap-mission-icon ic-landing"><i class="ph-fill ph-gauge"></i></div>
      <div class="ap-mission-title">{{ $t('personal_record') }}</div>
      <div class="ap-mission-value">{{ (int)$m['best_landing'] }} <span style="font-size:0.55em;font-weight:600;opacity:.6;">fpm</span></div>
      <div class="ap-mission-sub">{{ $t('softest_landing') }}</div>
    </div>
  @endif

  {{-- Tages-Challenge --}}
  <div class="ap-mission-card" data-accent="daily">
    <div class="ap-mission-icon ic-daily"><i class="ph-fill ph-lightning"></i></div>
    <div class="ap-mission-title">{{ $t('daily_challenge') }}</div>
    <div class="ap-mission-value">{{ $m['today_flights'] }}<span style="font-size:0.55em;font-weight:600;opacity:.5;">/ {{ $dailyGoal }}</span></div>
    <div class="ap-mission-sub">
      @if($m['today_flights'] >= $dailyGoal) <span style="color:var(--ap-green);">{{ $t('done_keep_going') }}</span>
      @elseif($m['today_flights'] > 0) {{ trans_choice('airlineinfopulse::pulse.n_more_today', $dailyGoal - $m['today_flights'], ['count' => $dailyGoal - $m['today_flights']]) }}
      @else {{ $t('do_n_today', ['count' => $dailyGoal]) }} @endif
    </div>
    @php $dailyPct = min(100, round($m['today_flights'] / max(1, $dailyGoal) * 100)); @endphp
    <div class="ap-mission-progress"><div class="ap-mission-progress-bar" style="width:{{ $dailyPct }}%;background:linear-gradient(90deg, #ec4899, #f472b6);"></div></div>
  </div>

  {{-- Längster Flug --}}
  @if(($m['longest_flight'] ?? 0) > 0)
    <div class="ap-mission-card" data-accent="distance">
      <div class="ap-mission-icon ic-distance"><i class="ph-fill ph-path"></i></div>
      <div class="ap-mission-title">{{ $t('longest_flight') }}</div>
      <div class="ap-mission-value">{{ number_format($m['longest_flight'] * $units['distance_factor'], 0, '', ' ') }} <span style="font-size:0.55em;font-weight:600;opacity:.6;">{{ $units['distance_label'] }}</span></div>
      <div class="ap-mission-sub">{{ $m['longest_route'] ?? $t('distance_record') }}</div>
    </div>
  @endif

  {{-- Airlines --}}
  <div class="ap-mission-card" data-accent="airlines">
    <div class="ap-mission-icon ic-airlines"><i class="ph-fill ph-buildings"></i></div>
    <div class="ap-mission-title">{{ $t('airlines_flown') }}</div>
    <div class="ap-mission-value">{{ $m['airlines_flown'] }} <span style="font-size:0.55em;font-weight:600;opacity:.6;">{{ $t('of_total', ['total' => $m['total_airlines']]) }}</span></div>
    <div class="ap-mission-sub">
      @if($m['airlines_flown'] >= $m['total_airlines']) <span style="color:var(--ap-amber);">{{ $t('all_complete') }}</span>
      @else {{ $t('n_more_airlines', ['count' => $m['total_airlines'] - $m['airlines_flown']]) }} @endif
    </div>
    @php $airPct = $m['total_airlines'] > 0 ? min(100, round($m['airlines_flown'] / $m['total_airlines'] * 100)) : 0; @endphp
    <div class="ap-mission-progress"><div class="ap-mission-progress-bar" style="width:{{ $airPct }}%;background:linear-gradient(90deg, #f59e0b, #fbbf24);"></div></div>
  </div>

  {{-- Wochenend-Pilot --}}
  <div class="ap-mission-card" data-accent="weekend">
    <div class="ap-mission-icon ic-weekend"><i class="ph-fill ph-sun-horizon"></i></div>
    <div class="ap-mission-title">{{ $t('weekend_pilot') }}</div>
    <div class="ap-mission-value">{{ $m['weekend_flights'] }} <span style="font-size:0.55em;font-weight:600;opacity:.6;">Sa/So</span></div>
    <div class="ap-mission-sub">
      @if($m['weekend_pct'] >= 50) <span style="color:var(--ap-amber);">{{ $t('weekend_king', ['pct' => $m['weekend_pct']]) }}</span>
      @elseif($m['weekend_flights'] > 0) {{ $t('weekend_pct', ['pct' => $m['weekend_pct']]) }}
      @else {{ $t('fly_weekend') }} @endif
    </div>
  </div>

</div>

{{-- ── Duell-Vergleichstabelle ── --}}
<div id="ap-compare-table" class="ap-glass mb-4" style="display:none;">
  <div class="ap-glass-header">
    <div class="d-flex align-items-center gap-2">
      <i class="ph-fill ph-sword" style="color:var(--ap-amber);font-size:1.1rem;"></i>
      <span class="ap-card-title">{{ $t('duel_title') }}: <span id="ap-compare-me-name">{{ $t('you') }}</span> vs <span id="ap-compare-rival-name">—</span></span>
    </div>
    <span id="ap-compare-score" style="font-family:var(--ap-font-mono);font-size:0.85rem;font-weight:700;"></span>
  </div>
  <div style="padding:16px;overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
      <thead>
        <tr style="border-bottom:2px solid var(--ap-border);">
          <th style="text-align:left;padding:8px 10px;color:var(--ap-muted);font-weight:600;">{{ $t('mission') }}</th>
          <th style="text-align:right;padding:8px 10px;color:var(--ap-cyan);font-weight:700;">{{ $t('you') }}</th>
          <th style="text-align:center;padding:8px 10px;color:var(--ap-muted);width:60px;"></th>
          <th style="text-align:left;padding:8px 10px;color:var(--ap-amber);font-weight:700;" id="ap-ct-rival-head">{{ $t('rival') }}</th>
        </tr>
      </thead>
      <tbody id="ap-compare-tbody"></tbody>
    </table>
  </div>
</div>
