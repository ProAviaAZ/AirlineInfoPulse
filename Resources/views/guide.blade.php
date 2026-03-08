{{-- AirlineInfoPulse :: guide.blade.php --}}
@extends('app')
@section('title', __('airlineinfopulse::pulse.pilot_guide'))

@php
$t = fn($key, $replace = []) => __('airlineinfopulse::pulse.' . $key, $replace);
$distLabel = $units['distance_label'] ?? 'NM';
$fuelLabel = $units['fuel_label'] ?? 'kg';
$effLabel  = $units['efficiency_label'] ?? 'kg/NM';
$fphLabel  = $fuelLabel . '/h';

// Dynamic config values
$gc = $guideConfig ?? [];
$ldgGreen     = $gc['ldg_green'] ?? 299;
$ldgOrange    = $gc['ldg_orange'] ?? 499;
$ldgRed       = $ldgOrange + 1;
$ldgAmberMin  = $ldgGreen + 1;
$dailyGoalH   = $gc['daily_goal_h'] ?? 4;
$dailyGoalMin = $gc['daily_goal_min'] ?? 240;
$co2Factor    = $gc['co2_factor'] ?? 3.16;
$minLdg       = $gc['min_landing_rate'] ?? 5;
$dailyFlights = $gc['daily_goal_flights'] ?? 3;
@endphp

@section('content')
<link href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2/src/fill/style.css" rel="stylesheet">
<style>
:root {
  --apg-bg:       #f8fafc;
  --apg-card:     #ffffff;
  --apg-border:   rgba(0,0,0,.08);
  --apg-text:     #1e293b;
  --apg-text-sub: #64748b;
  --apg-head:     #0f172a;
  --apg-cyan:     #22d3ee;
  --apg-green:    #4ade80;
  --apg-amber:    #fbbf24;
  --apg-red:      #f87171;
  --apg-violet:   #a78bfa;
  --apg-font:     'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
[data-bs-theme="dark"], .dark, [data-theme="dark"] {
  --apg-bg:       #0f172a;
  --apg-card:     #1e293b;
  --apg-border:   rgba(255,255,255,.08);
  --apg-text:     #e2e8f0;
  --apg-text-sub: #94a3b8;
  --apg-head:     #f1f5f9;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-bs-theme="light"]):not([data-theme="light"]):not(.light) {
    --apg-bg:       #0f172a;
    --apg-card:     #1e293b;
    --apg-border:   rgba(255,255,255,.08);
    --apg-text:     #e2e8f0;
    --apg-text-sub: #94a3b8;
    --apg-head:     #f1f5f9;
  }
}

.apg-wrap { max-width: 860px; margin: 0 auto; padding: 24px 16px; font-family: var(--apg-font); color: var(--apg-text); }
.apg-back { display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; color: var(--apg-text-sub); text-decoration: none; transition: color .2s; margin-bottom: 20px; }
.apg-back:hover { color: var(--apg-cyan); }
.apg-title { font-size: 1.6rem; font-weight: 800; color: var(--apg-head); letter-spacing: -0.03em; margin-bottom: 6px; }
.apg-subtitle { font-size: 0.85rem; color: var(--apg-text-sub); margin-bottom: 32px; }

.apg-section { margin-bottom: 32px; }
.apg-section-title { font-size: 1.05rem; font-weight: 700; color: var(--apg-head); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.apg-section-icon { font-size: 1.1rem; }

.apg-card { background: var(--apg-card); border: 1px solid var(--apg-border); border-radius: 12px; padding: 18px 20px; margin-bottom: 14px; }
.apg-card p { margin: 0 0 10px 0; line-height: 1.6; font-size: 0.88rem; }
.apg-card p:last-child { margin-bottom: 0; }

.apg-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; margin: 10px 0; }
.apg-table th { text-align: left; font-weight: 700; color: var(--apg-head); padding: 8px 12px; border-bottom: 2px solid var(--apg-border); }
.apg-table td { padding: 8px 12px; border-bottom: 1px solid var(--apg-border); vertical-align: top; }
.apg-table tr:last-child td { border-bottom: none; }

.apg-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
.apg-badge-green  { background: rgba(74,222,128,.15); color: #22c55e; }
.apg-badge-amber  { background: rgba(251,191,36,.15); color: #f59e0b; }
.apg-badge-red    { background: rgba(248,113,113,.15); color: #ef4444; }
.apg-badge-cyan   { background: rgba(34,211,238,.15); color: #06b6d4; }
.apg-badge-violet { background: rgba(167,139,250,.15); color: #8b5cf6; }

.apg-note { font-size: 0.82rem; color: var(--apg-text-sub); background: rgba(34,211,238,.06); border-left: 3px solid var(--apg-cyan); padding: 10px 14px; border-radius: 0 8px 8px 0; margin: 10px 0; }

.apg-toc { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px; margin-bottom: 32px; }
.apg-toc a { display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: var(--apg-card); border: 1px solid var(--apg-border); border-radius: 10px; text-decoration: none; color: var(--apg-text); font-size: 0.84rem; font-weight: 600; transition: border-color .2s, transform .15s; }
.apg-toc a:hover { border-color: var(--apg-cyan); transform: translateY(-1px); }
.apg-toc i { color: var(--apg-cyan); font-size: 1rem; }

.apg-new { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 700; background: rgba(34,211,238,.15); color: #06b6d4; text-transform: uppercase; letter-spacing: 0.05em; vertical-align: middle; margin-left: 4px; }

@media (max-width: 575px) {
  .apg-title { font-size: 1.3rem; }
  .apg-toc { grid-template-columns: 1fr; }
  .apg-table { font-size: 0.78rem; }
  .apg-table th, .apg-table td { padding: 6px 8px; }
}

/* Solid mode — colors from Config/config.php */
.apg-wrap:not(.apg-glass-mode) .apg-card,
.apg-wrap:not(.apg-glass-mode) .apg-toc a,
.apg-wrap:not(.apg-glass-mode) .apg-note { background: {{ $solidColors['card_light'] }} !important; border-color: {{ $solidColors['border_light'] }} !important; }
[data-bs-theme="dark"] .apg-wrap:not(.apg-glass-mode) .apg-card,
[data-bs-theme="dark"] .apg-wrap:not(.apg-glass-mode) .apg-toc a,
[data-bs-theme="dark"] .apg-wrap:not(.apg-glass-mode) .apg-note,
.dark .apg-wrap:not(.apg-glass-mode) .apg-card,
.dark .apg-wrap:not(.apg-glass-mode) .apg-toc a,
.dark .apg-wrap:not(.apg-glass-mode) .apg-note,
[data-theme="dark"] .apg-wrap:not(.apg-glass-mode) .apg-card,
[data-theme="dark"] .apg-wrap:not(.apg-glass-mode) .apg-toc a,
[data-theme="dark"] .apg-wrap:not(.apg-glass-mode) .apg-note { background: {{ $solidColors['card'] }} !important; border-color: {{ $solidColors['border'] }} !important; }
@media (prefers-color-scheme: dark) {
  .apg-wrap:not(.apg-glass-mode) .apg-card,
  .apg-wrap:not(.apg-glass-mode) .apg-toc a,
  .apg-wrap:not(.apg-glass-mode) .apg-note { background: {{ $solidColors['card'] }} !important; border-color: {{ $solidColors['border'] }} !important; }
}
</style>

<div class="apg-wrap{{ $glassMode ? ' apg-glass-mode' : '' }}">

  {{-- Back link --}}
  <a href="{{ url('/airline-info-pulse') }}" class="apg-back">
    <i class="ph-fill ph-arrow-left"></i> {{ $t('back_to_dashboard') }}
  </a>

  {{-- Header --}}
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
    <i class="ph-fill ph-book-open-text" style="color:var(--apg-cyan);font-size:1.4rem;"></i>
    <h1 class="apg-title mb-0">{{ $t('pilot_guide') }}</h1>
  </div>
  <div class="apg-subtitle">{{ $t('guide_subtitle') }}</div>

  {{-- Table of Contents --}}
  <div class="apg-toc">
    <a href="#apg-access"><i class="ph-fill ph-key"></i> {{ $t('guide_toc_access') }}</a>
    <a href="#apg-filter"><i class="ph-fill ph-clock"></i> {{ $t('guide_toc_filter') }}</a>
    <a href="#apg-kpis"><i class="ph-fill ph-chart-bar"></i> {{ $t('guide_toc_kpis') }}</a>
    <a href="#apg-cockpit"><i class="ph-fill ph-gauge"></i> {{ $t('guide_toc_cockpit') }}</a>
    <a href="#apg-missions"><i class="ph-fill ph-target"></i> {{ $t('guide_toc_missions') }}</a>
    <a href="#apg-duel"><i class="ph-fill ph-sword"></i> {{ $t('guide_toc_duel') }}</a>
    <a href="#apg-quickstart"><i class="ph-fill ph-shuffle"></i> {{ $t('guide_toc_quickstart') }}</a>
    <a href="#apg-toppilots"><i class="ph-fill ph-trophy"></i> {{ $t('guide_toc_top') }}</a>
    <a href="#apg-feed"><i class="ph-fill ph-rss"></i> {{ $t('guide_toc_feed') }}</a>
    <a href="#apg-snapshot"><i class="ph-fill ph-buildings"></i> {{ $t('guide_toc_snapshot') }}</a>
    <a href="#apg-landing"><i class="ph-fill ph-airplane-landing"></i> {{ $t('guide_toc_landing') }}</a>
    <a href="#apg-units"><i class="ph-fill ph-gear"></i> {{ $t('guide_toc_units') }} <span class="apg-new">NEW</span></a>
    <a href="#apg-faq"><i class="ph-fill ph-question"></i> {{ $t('guide_toc_faq') }}</a>
  </div>

  {{-- ══ ACCESS ══ --}}
  <div class="apg-section" id="apg-access">
    <div class="apg-section-title"><i class="ph-fill ph-key apg-section-icon" style="color:var(--apg-amber);"></i> {{ $t('guide_access_title') }}</div>
    <div class="apg-card">
      <p>{!! $t('guide_access_text') !!}</p>
      <div class="apg-note">{{ $t('guide_access_note') }}</div>
      <p>{!! $t('guide_access_lang') !!}</p>
    </div>
  </div>

  {{-- ══ TIME FILTER ══ --}}
  <div class="apg-section" id="apg-filter">
    <div class="apg-section-title"><i class="ph-fill ph-clock apg-section-icon" style="color:var(--apg-cyan);"></i> {{ $t('guide_filter_title') }}</div>
    <div class="apg-card">
      <p>{{ $t('guide_filter_intro') }}</p>
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_filter_btn') }}</th><th>{{ $t('guide_filter_period') }}</th></tr></thead>
        <tbody>
          <tr><td><strong>{{ $t('today') }}</strong></td><td>{{ $t('guide_filter_today') }}</td></tr>
          <tr><td><strong>{{ $t('yesterday') }}</strong></td><td>{{ $t('guide_filter_yest') }}</td></tr>
          <tr><td><strong>{{ $t('week') }}</strong></td><td>{{ $t('guide_filter_week') }}</td></tr>
          <tr><td><strong>{{ $t('month') }}</strong></td><td>{{ $t('guide_filter_month') }}</td></tr>
          <tr><td><strong>{{ $t('quarter') }}</strong></td><td>{{ $t('guide_filter_quarter') }}</td></tr>
          <tr><td><strong>{{ $t('year') }}</strong></td><td>{{ $t('guide_filter_year') }}</td></tr>
        </tbody>
      </table>
      <div class="apg-note">{{ $t('guide_filter_note') }}</div>
    </div>
  </div>

  {{-- ══ KPI STRIP ══ --}}
  <div class="apg-section" id="apg-kpis">
    <div class="apg-section-title"><i class="ph-fill ph-chart-bar apg-section-icon" style="color:var(--apg-green);"></i> {{ $t('guide_kpi_title') }}</div>
    <div class="apg-card">
      <p>{!! $t('guide_kpi_intro') !!}</p>
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_kpi_card') }}</th><th>{{ $t('guide_kpi_shows') }}</th></tr></thead>
        <tbody>
          <tr><td>✈️ <strong>{{ $t('flights') }}</strong></td><td>{{ $t('guide_kpi_flights') }}</td></tr>
          <tr><td>⏱️ <strong>{{ $t('block_time') }}</strong></td><td>{{ $t('guide_kpi_blocktime') }}</td></tr>
          <tr><td>📏 <strong>{{ $t('distance') }}</strong></td><td>{{ $t('guide_kpi_distance', ['unit' => $distLabel]) }}</td></tr>
          <tr><td>⛽ <strong>{{ $t('fuel') }}</strong></td><td>{{ $t('guide_kpi_fuel', ['unit' => $fuelLabel]) }}</td></tr>
          <tr><td>📉 <strong>{{ $t('avg_landing') }}</strong></td><td>{{ $t('guide_kpi_landing') }}</td></tr>
          <tr><td>✅ <strong>{{ $t('accepted_pireps') }}</strong></td><td>{{ $t('guide_kpi_pireps') }}</td></tr>
        </tbody>
      </table>
      <p>{!! $t('guide_kpi_arrows') !!}</p>
    </div>
  </div>

  {{-- ══ COCKPIT ══ --}}
  <div class="apg-section" id="apg-cockpit">
    <div class="apg-section-title"><i class="ph-fill ph-gauge apg-section-icon" style="color:var(--apg-violet);"></i> {{ $t('guide_cockpit_title') }}</div>
    <div class="apg-card">
      <p>{!! $t('guide_cockpit_text') !!}</p>
      <div class="apg-note">{{ $t('guide_cockpit_note') }}</div>
    </div>
  </div>

  {{-- ══ MISSIONS ══ --}}
  <div class="apg-section" id="apg-missions">
    <div class="apg-section-title"><i class="ph-fill ph-target apg-section-icon" style="color:var(--apg-amber);"></i> {{ $t('guide_mission_title') }}</div>
    <div class="apg-card">
      <p>{{ $t('guide_mission_intro') }}</p>
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_mission_col1') }}</th><th>{{ $t('guide_mission_col2') }}</th></tr></thead>
        <tbody>
          <tr><td>🔥 <strong>{{ $t('duel_streak') }}</strong></td><td>{{ $t('guide_m_streak') }}</td></tr>
          <tr><td>🏅 <strong>{{ $t('duel_milestone') }}</strong></td><td>{{ $t('guide_m_milestone') }}</td></tr>
          <tr><td>🏆 <strong>{{ $t('duel_ranking') }}</strong></td><td>{{ $t('guide_m_ranking') }}</td></tr>
          <tr><td>🗺️ <strong>{{ $t('duel_airports') }}</strong></td><td>{{ $t('guide_m_airports') }}</td></tr>
          <tr><td>✈️ <strong>{{ $t('duel_fleet') }}</strong></td><td>{{ $t('guide_m_fleet') }}</td></tr>
          <tr><td>📉 <strong>{{ $t('duel_record') }}</strong></td><td>{{ $t('guide_m_record', ['min' => $minLdg]) }}</td></tr>
          <tr><td>⚡ <strong>{{ $t('duel_daily') }}</strong></td><td>{{ $t('guide_m_daily', ['goal' => $dailyFlights]) }}</td></tr>
          <tr><td>📏 <strong>{{ $t('duel_longest') }}</strong></td><td>{{ $t('guide_m_longest', ['unit' => $distLabel]) }}</td></tr>
          <tr><td>🏢 <strong>{{ $t('duel_airlines') }}</strong></td><td>{{ $t('guide_m_airlines') }}</td></tr>
          <tr><td>🌅 <strong>{{ $t('duel_weekend_pct') }}</strong></td><td>{{ $t('guide_m_weekend') }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- ══ DUEL ══ --}}
  <div class="apg-section" id="apg-duel">
    <div class="apg-section-title"><i class="ph-fill ph-sword apg-section-icon" style="color:var(--apg-red);"></i> {{ $t('guide_duel_title') }}</div>
    <div class="apg-card">
      <p>{!! $t('guide_duel_intro') !!}</p>
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_duel_color') }}</th><th>{{ $t('guide_duel_meaning') }}</th></tr></thead>
        <tbody>
          <tr><td><span class="apg-badge apg-badge-green">Green</span></td><td>{{ $t('guide_duel_win') }}</td></tr>
          <tr><td><span class="apg-badge apg-badge-amber">Amber</span></td><td>{{ $t('guide_duel_lose') }}</td></tr>
          <tr><td><span class="apg-badge apg-badge-cyan">Tie</span></td><td>{{ $t('guide_duel_tie') }}</td></tr>
        </tbody>
      </table>
      <p>{{ $t('guide_duel_cats') }}</p>
    </div>
  </div>

  {{-- ══ QUICKSTART ══ --}}
  <div class="apg-section" id="apg-quickstart">
    <div class="apg-section-title"><i class="ph-fill ph-shuffle apg-section-icon" style="color:var(--apg-cyan);"></i> {{ $t('guide_qs_title') }}</div>
    <div class="apg-card">
      <p>{{ $t('guide_qs_intro') }}</p>
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_qs_region') }}</th><th>{{ $t('guide_qs_prefixes') }}</th></tr></thead>
        <tbody>
          <tr><td>🇪🇺 <strong>{{ $t('region_eu') }}</strong></td><td>E (EGLL, EDDF…), L (LFPG, LIRF…), U (UUEE…)</td></tr>
          <tr><td>🌏 <strong>{{ $t('region_asia') }}</strong></td><td>Z (ZBAA…), V (VHHH…), R (RJTT…), W, O</td></tr>
          <tr><td>🌍 <strong>{{ $t('region_africa') }}</strong></td><td>D (DNMM…), F (FACT, FAOR…), G (GOOY…), H (HKJK…)</td></tr>
          <tr><td>🌊 <strong>{{ $t('region_oceania') }}</strong></td><td>A (AYMH…), N (NZAA, NFFN…), Y (YSSY, YMML…)</td></tr>
          <tr><td>🇺🇸 <strong>{{ $t('region_us') }}</strong></td><td>K (KJFK, KLAX…)</td></tr>
        </tbody>
      </table>
      <p>{{ $t('guide_qs_info') }}</p>
    </div>
  </div>

  {{-- ══ TOP PILOTS & AIRCRAFT ══ --}}
  <div class="apg-section" id="apg-toppilots">
    <div class="apg-section-title"><i class="ph-fill ph-trophy apg-section-icon" style="color:var(--apg-amber);"></i> {{ $t('guide_top_title') }}</div>
    <div class="apg-card">
      <p>{!! $t('guide_top_pilots') !!}</p>
      <p>{!! $t('guide_top_aircraft') !!}</p>
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_top_metric') }}</th><th>{{ $t('guide_top_meaning') }}</th></tr></thead>
        <tbody>
          <tr><td><span class="apg-badge apg-badge-amber"><i class="ph-fill ph-gas-pump"></i> {{ $effLabel }}</span></td><td>{{ $t('guide_top_eff') }}</td></tr>
          <tr><td><span class="apg-badge apg-badge-amber"><i class="ph-fill ph-timer"></i> {{ $fphLabel }}</span> <span class="apg-new">NEW</span></td><td>{{ $t('guide_top_fph') }}</td></tr>
          <tr><td><span class="apg-badge apg-badge-green"><i class="ph-fill ph-leaf"></i> t CO₂</span></td><td>{{ $t('guide_top_co2', ['factor' => $co2Factor]) }}</td></tr>
        </tbody>
      </table>
      <p>{!! $t('guide_top_show') !!}</p>
    </div>
  </div>

  {{-- ══ FEED ══ --}}
  <div class="apg-section" id="apg-feed">
    <div class="apg-section-title"><i class="ph-fill ph-rss apg-section-icon" style="color:var(--apg-green);"></i> {{ $t('guide_feed_title') }}</div>
    <div class="apg-card">
      <p>{{ $t('guide_feed_intro') }}</p>
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_feed_event') }}</th><th>{{ $t('guide_feed_shows') }}</th></tr></thead>
        <tbody>
          <tr><td>✈️ <strong>{{ $t('flights') }}</strong></td><td>{{ $t('guide_feed_flight') }}</td></tr>
          <tr><td>👤 <strong>{{ $t('feed_new_user') }}</strong></td><td>{{ $t('guide_feed_newpilot') }}</td></tr>
          <tr><td>🔧 <strong>{{ $t('feed_maint') }}</strong></td><td>{{ $t('guide_feed_maint') }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- ══ SNAPSHOT ══ --}}
  <div class="apg-section" id="apg-snapshot">
    <div class="apg-section-title"><i class="ph-fill ph-buildings apg-section-icon" style="color:var(--apg-violet);"></i> {{ $t('guide_snap_title') }}</div>
    <div class="apg-card">
      <p>{!! $t('guide_snap_text') !!}</p>
    </div>
  </div>

  {{-- ══ LANDING RATES ══ --}}
  <div class="apg-section" id="apg-landing">
    <div class="apg-section-title"><i class="ph-fill ph-airplane-landing apg-section-icon" style="color:var(--apg-green);"></i> {{ $t('guide_ldg_title') }}</div>
    <div class="apg-card">
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_duel_color') }}</th><th>{{ $t('guide_ldg_range') }}</th><th>{{ $t('guide_ldg_rating') }}</th></tr></thead>
        <tbody>
          <tr><td><span class="apg-badge apg-badge-green">Green</span></td><td>{{ $t('guide_ldg_range_green', ['max' => $ldgGreen]) }}</td><td>{{ $t('guide_ldg_green') }}</td></tr>
          <tr><td><span class="apg-badge apg-badge-amber">Orange</span></td><td>{{ $t('guide_ldg_range_amber', ['min' => $ldgAmberMin, 'max' => $ldgOrange]) }}</td><td>{{ $t('guide_ldg_amber') }}</td></tr>
          <tr><td><span class="apg-badge apg-badge-red">Red</span></td><td>{{ $t('guide_ldg_range_red', ['min' => $ldgRed]) }}</td><td>{{ $t('guide_ldg_red') }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- ══ UNITS & PRIVACY (NEW) ══ --}}
  <div class="apg-section" id="apg-units">
    <div class="apg-section-title"><i class="ph-fill ph-gear apg-section-icon" style="color:var(--apg-cyan);"></i> {{ $t('guide_units_title') }} <span class="apg-new">NEW</span></div>
    <div class="apg-card">
      <p>{{ $t('guide_units_intro') }}</p>
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_units_setting') }}</th><th>{{ $t('guide_units_example') }}</th></tr></thead>
        <tbody>
          <tr><td>📏 <strong>{{ $t('guide_units_dist') }}</strong></td><td>{{ $t('guide_units_dist_ex') }}</td></tr>
          <tr><td>⛽ <strong>{{ $t('guide_units_fuel') }}</strong></td><td>{{ $t('guide_units_fuel_ex') }}</td></tr>
          <tr><td>📊 <strong>{{ $t('guide_units_eff_lbl') }}</strong></td><td>{{ $t('guide_units_eff_ex', ['label' => $effLabel]) }}</td></tr>
          <tr><td>⏱️ <strong>{{ $t('guide_units_fph_lbl') }}</strong> <span class="apg-new">NEW</span></td><td>{{ $t('guide_units_fph_ex', ['unit' => $fuelLabel]) }}</td></tr>
        </tbody>
      </table>
      <div class="apg-note">{{ $t('guide_units_note') }}</div>
    </div>
    <div class="apg-card">
      <p><strong><i class="ph-fill ph-shield-check" style="color:var(--apg-green);"></i> {{ $t('guide_privacy_title') }}</strong></p>
      <p>{{ $t('guide_privacy_text') }}</p>
    </div>
  </div>

  {{-- ══ TIPS ══ --}}
  <div class="apg-section">
    <div class="apg-section-title"><i class="ph-fill ph-lightbulb apg-section-icon" style="color:var(--apg-amber);"></i> {{ $t('guide_tips_title') }}</div>
    <div class="apg-card">
      <table class="apg-table">
        <thead><tr><th>{{ $t('guide_tips_goal') }}</th><th>{{ $t('guide_tips_how') }}</th></tr></thead>
        <tbody>
          <tr><td><strong>{{ $t('duel_streak') }}</strong></td><td>{{ $t('guide_tip_streak') }}</td></tr>
          <tr><td><strong>{{ $t('avg_landing') }}</strong></td><td>{{ $t('guide_tip_landing') }}</td></tr>
          <tr><td><strong>{{ $t('duel_ranking') }}</strong></td><td>{{ $t('guide_tip_ranking') }}</td></tr>
          <tr><td><strong>{{ $t('duel_airports') }}</strong></td><td>{{ $t('guide_tip_airports') }}</td></tr>
          <tr><td><strong>{{ $t('duel_airlines') }}</strong></td><td>{{ $t('guide_tip_airlines') }}</td></tr>
          <tr><td><strong>{{ $t('duel_weekend_pct') }}</strong></td><td>{{ $t('guide_tip_weekend') }}</td></tr>
          <tr><td><strong>{{ $t('duel_milestone') }}</strong></td><td>{{ $t('guide_tip_milestone') }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- ══ FAQ ══ --}}
  <div class="apg-section" id="apg-faq">
    <div class="apg-section-title"><i class="ph-fill ph-question apg-section-icon" style="color:var(--apg-cyan);"></i> {{ $t('guide_faq_title') }}</div>

    @php
    $faqReplace = [
        5 => ['hours' => $dailyGoalH, 'minutes' => $dailyGoalMin],
        6 => ['factor' => $co2Factor],
    ];
    @endphp

    @foreach(range(1, 8) as $i)
    <div class="apg-card">
      <p><strong>{{ $t('guide_faq'.$i.'_q', $faqReplace[$i] ?? []) }}</strong></p>
      <p>{!! $t('guide_faq'.$i.'_a', $faqReplace[$i] ?? []) !!}</p>
    </div>
    @endforeach
  </div>

  {{-- Back to Dashboard --}}
  <div style="text-align:center;margin:32px 0 16px;">
    <a href="{{ url('/airline-info-pulse') }}" class="apg-back" style="font-size:0.9rem;">
      <i class="ph-fill ph-arrow-left"></i> {{ $t('back_to_dashboard') }}
    </a>
  </div>

  <div style="text-align:center;padding:8px 0 16px;font-size:0.72rem;color:var(--apg-text-sub);letter-spacing:0.02em;">
    Airline Pulse — crafted with ♥ in Germany by <a href="https://github.com/MANFahrer-GF" target="_blank" rel="noopener" style="color:var(--apg-cyan);text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Thomas Kant</a>
  </div>

</div>
@endsection
