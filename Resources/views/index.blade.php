{{-- AirlineInfoPulse :: index.blade.php --}}
@extends('app')
@section('title', __('airlineinfopulse::pulse.title'))

@php
use Modules\AirlineInfoPulse\Helpers\PulseHelper;

// Translation shorthand
$t = fn($key, $replace = []) => __('airlineinfopulse::pulse.' . $key, $replace);

$tfNice = PulseHelper::filterLabel($filter);

$fmtMin = function($m) {
    if ($m === null || $m === 0) return '0:00 h';
    $m = (int) $m;
    return sprintf('%d:%02d h', intdiv(abs($m), 60), abs($m) % 60);
};

$fmtDelta = function($delta) {
    if (!$delta || $delta['direction'] === 'neutral') return '';
    $color = $delta['direction'] === 'up' ? '#4ade80' : '#f87171';
    $icon  = $delta['direction'] === 'up' ? '↑' : '↓';
    $sign  = $delta['direction'] === 'up' ? '+' : '-';
    return '<span class="ap-delta" style="color:'.$color.';">'.$icon.$sign.$delta['value'].'%</span>';
};

$landingClass = function($v) {
    if (is_null($v)) return '';
    $v = abs((int) $v);
    if ($v >= 500) return 'ldg-bad';
    if ($v >= 300) return 'ldg-ok';
    return 'ldg-good';
};

$mkPirepUrl = function($id) {
    return \Illuminate\Support\Facades\Route::has('frontend.pireps.show')
        ? route('frontend.pireps.show', $id) : url('/pireps/'.$id);
};
$mkPilotUrl = function($id) {
    return \Illuminate\Support\Facades\Route::has('frontend.profile.show')
        ? route('frontend.profile.show', $id) : url('/profile/'.$id);
};
$mkAircraftUrl = function($reg) {
    return $reg ? url('/daircraft/'.rawurlencode($reg)) : '#';
};
$mkAirlineUrl = function($airline) {
    if (!$airline) return '#';
    $icao = strtoupper($airline->icao ?? '');
    return $icao ? url('/dairlines/'.$icao) : '#';
};

$fullAcName = function($ac) {
    if (!$ac) return null;
    $icao = $ac->icao ?? ($ac->subfleet->type ?? '');
    $name = $ac->name ?? ($ac->subfleet->name ?? null);
    return trim(($icao ?: '') . ($icao && $name ? ' — ' : '') . ($name ?: ''));
};
@endphp

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2/src/fill/style.css" rel="stylesheet">

{{-- ═══ Theme detection — adds .ap-light / .ap-dark to <html> ═══ --}}
<script>
(function(){
  function detectTheme(){
    var h=document.documentElement,b=document.body;
    var isDark=h.getAttribute('data-bs-theme')==='dark'
      ||h.getAttribute('data-theme')==='dark'
      ||b.getAttribute('data-bs-theme')==='dark'
      ||b.getAttribute('data-theme')==='dark'
      ||b.classList.contains('dark-mode')||b.classList.contains('dark')
      ||h.classList.contains('dark-mode')||h.classList.contains('dark')
      ||(!h.getAttribute('data-bs-theme')&&!b.getAttribute('data-bs-theme')
         &&!h.getAttribute('data-theme')&&!b.getAttribute('data-theme')
         &&!b.classList.contains('light-mode')&&!b.classList.contains('light')
         &&!h.classList.contains('light-mode')&&!h.classList.contains('light')
         &&window.matchMedia('(prefers-color-scheme:dark)').matches);
    h.classList.toggle('ap-dark',isDark);
    h.classList.toggle('ap-light',!isDark);
  }
  detectTheme();
  new MutationObserver(detectTheme).observe(document.documentElement,{attributes:true,attributeFilter:['data-bs-theme','data-theme','class']});
  new MutationObserver(detectTheme).observe(document.body,{attributes:true,attributeFilter:['data-bs-theme','data-theme','class']});
  window.matchMedia('(prefers-color-scheme:dark)').addEventListener('change',detectTheme);
})();
</script>

{{-- ═══ DESIGN SYSTEM CSS ═══ --}}
<style>
/* ── Accent colors (theme-independent) ── */
:root {
  --ap-cyan:    #0ea5e9;
  --ap-blue:    #3b82f6;
  --ap-violet:  #818cf8;
  --ap-green:   #22c55e;
  --ap-amber:   #f59e0b;
  --ap-red:     #ef4444;
  --ap-font-head: 'Outfit', sans-serif;
  --ap-font-mono: 'JetBrains Mono', monospace;
  --ap-font-body: 'Inter', sans-serif;
  /* Dark defaults */
  --ap-surface:    rgba(255,255,255,0.04);
  --ap-border:     rgba(255,255,255,0.08);
  --ap-border2:    rgba(255,255,255,0.18);
  --ap-card-bg:    rgba(255,255,255,0.03);
  --ap-text:       #e2e8f0;
  --ap-text-head:  #ffffff;
  --ap-muted:      #cbd5e1;
  --ap-kpi-text:   #ffffff;
  --ap-select-bg:  rgba(255,255,255,0.07);
  --ap-progress-bg:rgba(255,255,255,0.08);
  --ap-rank-bg:    rgba(255,255,255,0.03);
  --ap-tag-bg:     rgba(255,255,255,0.07);
  --ap-tag-color:  #e2e8f0;
  --ap-divider:    rgba(255,255,255,0.07);
  --ap-my-kpi-bg:  rgba(255,255,255,0.04);
  --ap-motiv-bg:   rgba(59,130,246,0.1);
  --ap-motiv-bdr:  rgba(59,130,246,0.25);
  --ap-motiv-col:  #93c5fd;
}

/* ── Light mode ── */
html.ap-light {
  --ap-surface:    rgba(255,255,255,0.9);
  --ap-border:     rgba(0,0,0,0.1);
  --ap-border2:    rgba(0,0,0,0.2);
  --ap-card-bg:    rgba(255,255,255,0.8);
  --ap-text:       #1e293b;
  --ap-text-head:  #0f172a;
  --ap-muted:      #64748b;
  --ap-kpi-text:   #0f172a;
  --ap-select-bg:  rgba(0,0,0,0.05);
  --ap-progress-bg:rgba(0,0,0,0.08);
  --ap-rank-bg:    rgba(255,255,255,0.85);
  --ap-tag-bg:     rgba(0,0,0,0.06);
  --ap-tag-color:  #334155;
  --ap-divider:    rgba(0,0,0,0.08);
  --ap-my-kpi-bg:  rgba(255,255,255,0.85);
  --ap-motiv-bg:   rgba(59,130,246,0.08);
  --ap-motiv-bdr:  rgba(59,130,246,0.2);
  --ap-motiv-col:  #1d4ed8;
}
html.ap-light .ap-glass { box-shadow: 0 2px 16px rgba(0,0,0,0.07), 0 1px 4px rgba(0,0,0,0.05); }
html.ap-light .ap-kpi { box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
html.ap-light .ap-rank-item { box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
html.ap-light .ap-tag.ap-tag-blue { color: #1d4ed8; background: rgba(59,130,246,0.1); }
html.ap-light .ap-tag.ap-tag-cyan { color: #0369a1; background: rgba(14,165,233,0.1); }
html.ap-light .ap-tag.ap-tag-amber { color: #92400e; background: rgba(245,158,11,0.1); }
html.ap-light .ap-tag.ap-tag-green { color: #166534; background: rgba(34,197,94,0.1); }
html.ap-light .ap-tag.ap-tag-red { color: #991b1b; background: rgba(239,68,68,0.1); }
html.ap-light .ap-feed-route { color: var(--ap-blue); }
html.ap-light .ap-feed-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); }
html.ap-light .ap-filter-pill { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.1); }
html.ap-light .ap-filter-pill button:hover { background: rgba(0,0,0,0.07); }
html.ap-light .ap-select { background: #ffffff !important; border-color: var(--ap-border); color: #1e293b !important; }
html.ap-light .ap-select option { background: #ffffff !important; color: #1e293b !important; }
html.ap-light .ap-motiv-item { background: var(--ap-motiv-bg); border-color: var(--ap-motiv-bdr); color: var(--ap-motiv-col); }
html.ap-light .ap-region-btn { border-color: var(--ap-border); color: var(--ap-muted); }
html.ap-light .ap-region-btn:hover { color: var(--ap-blue); border-color: var(--ap-blue); background: rgba(59,130,246,0.06); }
html.ap-light .ap-progress { background: rgba(0,0,0,0.08); }
html.ap-light .ap-recent-row { border-bottom-color: rgba(0,0,0,0.06); }
html.ap-light .ap-asnap-featured { box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
html.ap-light .ap-asnap-featured:hover { background: rgba(129,140,248,0.06); }
html.ap-light .ap-asnap-row:hover { background: rgba(129,140,248,0.06); }
html.ap-light .ap-asnap-trend.up { color: #166534; background: rgba(34,197,94,0.1); }
html.ap-light .ap-asnap-trend.down { color: #991b1b; background: rgba(239,68,68,0.1); }
html.ap-light .ap-mx-badge.mx-hard { color: #991b1b; background: rgba(239,68,68,0.1); }
html.ap-light .ap-mx-badge.mx-soft { color: #92400e; background: rgba(245,158,11,0.1); }
html.ap-light .ap-mission-icon.ic-streak { background: rgba(245,158,11,0.1); }
html.ap-light .ap-mission-icon.ic-milestone { background: rgba(129,140,248,0.1); }
html.ap-light .ap-mission-icon.ic-rank { background: rgba(59,130,246,0.1); }
html.ap-light .ap-mission-icon.ic-explore { background: rgba(14,165,233,0.1); }
html.ap-light .ap-mission-icon.ic-aircraft { background: rgba(34,197,94,0.1); }
html.ap-light .ap-mission-icon.ic-landing { background: rgba(74,222,128,0.1); }
html.ap-light .ap-mission-icon.ic-daily { background: rgba(236,72,153,0.1); }

/* ── Base ── */
.ap-wrap { font-family: var(--ap-font-body); color: var(--ap-text); font-variant-numeric: lining-nums; }

/* ── Typography ── */
.ap-page-title { font-family: var(--ap-font-head); font-weight: 800; font-size: 1.6rem; letter-spacing: -0.03em; color: var(--ap-text-head); }
.ap-section-label { font-family: var(--ap-font-head); font-weight: 700; font-size: 0.7rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--ap-muted); }
.ap-card-title { font-family: var(--ap-font-head); font-weight: 700; font-size: 0.9rem; letter-spacing: 0.01em; color: var(--ap-text-head); }
.ap-mono { font-family: var(--ap-font-mono); font-variant-numeric: lining-nums tabular-nums; }

/* ── Glass Card ── */
.ap-glass { background: var(--ap-surface); border: 1px solid var(--ap-border); border-radius: 16px; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); transition: border-color 0.2s, box-shadow 0.2s; }
.ap-glass:hover { border-color: var(--ap-border2); }
.ap-glass-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid var(--ap-border); }

/* ── KPI Strip ── */
.ap-kpi-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px; }
.ap-kpi { background: var(--ap-surface); border: 1px solid var(--ap-border); border-radius: 14px; padding: 16px 18px; position: relative; overflow: hidden; transition: transform 0.15s, border-color 0.2s; }
.ap-kpi:hover { transform: translateY(-2px); border-color: var(--ap-border2); }
.ap-kpi::before { content: ''; position: absolute; inset: 0; background: var(--kpi-gradient, linear-gradient(135deg, rgba(59,130,246,0.12) 0%, transparent 60%)); pointer-events: none; }
.ap-kpi-label { font-size: 0.68rem; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; color: var(--ap-muted); margin-bottom: 6px; }
.ap-kpi-value { font-family: var(--ap-font-head); font-size: 1.55rem; font-weight: 800; color: var(--ap-text-head); line-height: 1; font-variant-numeric: lining-nums tabular-nums; }
.ap-kpi-value .ap-delta { font-size: 0.7rem; font-family: var(--ap-font-mono); margin-left: 6px; font-weight: 600; }
.ap-kpi-icon { position: absolute; right: 14px; top: 12px; opacity: 0.15; font-size: 1.8rem; }
.ap-kpi[data-type="flights"] { --kpi-gradient: linear-gradient(135deg, rgba(59,130,246,0.15) 0%, transparent 60%); }
.ap-kpi[data-type="flights"] .ap-kpi-icon { color: var(--ap-blue); opacity: 0.25; }
.ap-kpi[data-type="time"]    { --kpi-gradient: linear-gradient(135deg, rgba(129,140,248,0.15) 0%, transparent 60%); }
.ap-kpi[data-type="time"] .ap-kpi-icon { color: var(--ap-violet); opacity: 0.25; }
.ap-kpi[data-type="dist"]    { --kpi-gradient: linear-gradient(135deg, rgba(34,211,238,0.15) 0%, transparent 60%); }
.ap-kpi[data-type="dist"] .ap-kpi-icon { color: var(--ap-cyan); opacity: 0.25; }
.ap-kpi[data-type="fuel"]    { --kpi-gradient: linear-gradient(135deg, rgba(251,191,36,0.12) 0%, transparent 60%); }
.ap-kpi[data-type="fuel"] .ap-kpi-icon { color: var(--ap-amber); opacity: 0.25; }
.ap-kpi[data-type="ldg"]     { --kpi-gradient: linear-gradient(135deg, rgba(74,222,128,0.12) 0%, transparent 60%); }
.ap-kpi[data-type="ldg"] .ap-kpi-icon { color: var(--ap-green); opacity: 0.25; }
.ap-kpi[data-type="acc"]     { --kpi-gradient: linear-gradient(135deg, rgba(34,211,238,0.12) 0%, transparent 60%); }

/* ── Filter Pills ── */
.ap-filter-pill { display: flex; gap: 4px; background: rgba(255,255,255,0.05); border-radius: 10px; padding: 4px; border: 1px solid var(--ap-border); }
.ap-filter-pill button { border: none; background: transparent; color: var(--ap-muted); font-family: var(--ap-font-head); font-size: 0.78rem; font-weight: 600; padding: 5px 12px; border-radius: 7px; cursor: pointer; transition: all 0.15s; }
.ap-filter-pill button:hover { color: var(--ap-text-head); background: var(--ap-surface); }
.ap-filter-pill button.active { background: var(--ap-blue); color: #fff; }

/* ── Progress Bar ── */
.ap-progress { height: 6px; background: rgba(255,255,255,0.08); border-radius: 99px; overflow: hidden; }
.ap-progress-bar { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--ap-blue), var(--ap-cyan)); transition: width 0.6s cubic-bezier(0.4,0,0.2,1); position: relative; }
.ap-progress-bar::after { content: ''; position: absolute; right: 0; top: 0; bottom: 0; width: 40px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4)); animation: shimmer 1.5s infinite; }
@keyframes shimmer { 0%{opacity:0} 50%{opacity:1} 100%{opacity:0} }

/* ── My Stats ── */
.ap-my-kpi { background: var(--ap-my-kpi-bg); border: 1px solid var(--ap-border); border-radius: 10px; padding: 12px 14px; }
.ap-my-kpi-label { font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--ap-muted); margin-bottom: 4px; font-weight: 500; }
.ap-my-kpi-val { font-family: var(--ap-font-head); font-size: 1.2rem; font-weight: 700; color: var(--ap-text-head); font-variant-numeric: lining-nums tabular-nums; }

/* ── Rank Items ── */
.ap-rank-item { background: var(--ap-card-bg); border: 1px solid var(--ap-border); border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; transition: border-color 0.15s, background 0.15s; }
.ap-rank-item:last-of-type { margin-bottom: 0; }
.ap-rank-item:hover { border-color: rgba(59,130,246,0.4); background: rgba(59,130,246,0.05); }
.ap-rank-medal { font-size: 1.1rem; margin-right: 6px; }
.ap-rank-name { font-family: var(--ap-font-head); font-weight: 700; font-size: 0.95rem; color: var(--ap-text-head); }
.ap-rank-name a { color: inherit; text-decoration: none; }
.ap-rank-name a:hover { color: var(--ap-cyan); }

/* ── Tags / Chips ── */
.ap-tag { display: inline-flex; align-items: center; gap: 4px; font-family: var(--ap-font-mono); font-size: 0.68rem; font-weight: 500; background: var(--ap-tag-bg); color: var(--ap-tag-color); border: 1px solid var(--ap-border); border-radius: 6px; padding: 3px 8px; white-space: nowrap; }
.ap-tag.ap-tag-blue  { background: rgba(59,130,246,0.15); border-color: rgba(59,130,246,0.3); color: #93c5fd; }
.ap-tag.ap-tag-cyan  { background: rgba(34,211,238,0.12); border-color: rgba(34,211,238,0.3); color: #67e8f9; }
.ap-tag.ap-tag-amber { background: rgba(251,191,36,0.12); border-color: rgba(251,191,36,0.3); color: #fde68a; }
.ap-tag.ap-tag-green { background: rgba(74,222,128,0.12); border-color: rgba(74,222,128,0.3); color: #86efac; }
.ap-tag.ap-tag-red   { background: rgba(248,113,113,0.12); border-color: rgba(248,113,113,0.3); color: #fca5a5; }

/* ── Feed ── */
.ap-feed-item { background: var(--ap-card-bg); border: 1px solid var(--ap-border); border-radius: 10px; padding: 10px 13px; margin-bottom: 8px; transition: border-color 0.15s; }
.ap-feed-item:hover { border-color: var(--ap-border2); }
.ap-feed-time { font-family: var(--ap-font-mono); font-size: 0.68rem; color: var(--ap-muted); white-space: nowrap; }
.ap-feed-route { font-family: var(--ap-font-mono); font-size: 0.78rem; color: var(--ap-cyan); font-weight: 600; }
.ap-feed-scroll { overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent; flex: 1 1 0; min-height: 0; }
.ap-feed-scroll::-webkit-scrollbar { width: 4px; }
.ap-feed-scroll::-webkit-scrollbar-track { background: transparent; }
.ap-feed-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 99px; }

/* ── Maintenance Events ── */
.ap-feed-mx { background: var(--ap-card-bg); border: 1px solid var(--ap-border); border-radius: 10px; padding: 10px 13px; margin-bottom: 8px; transition: border-color 0.15s; position: relative; overflow: hidden; }
.ap-feed-mx::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; }
.ap-feed-mx.mx-hard::before { background: var(--ap-red); }
.ap-feed-mx.mx-soft::before { background: var(--ap-amber); }
.ap-feed-mx:hover { border-color: var(--ap-border2); }
.ap-mx-badge { display: inline-flex; align-items: center; gap: 4px; font-family: var(--ap-font-mono); font-size: 0.68rem; font-weight: 600; border-radius: 6px; padding: 3px 8px; white-space: nowrap; }
.ap-mx-badge.mx-hard { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
.ap-mx-badge.mx-soft { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); color: #fde68a; }

/* ── Avatar ── */
.ap-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--ap-blue), var(--ap-cyan)); display: flex; align-items: center; justify-content: center; font-family: var(--ap-font-head); font-weight: 800; font-size: 1rem; color: var(--ap-text-head); flex-shrink: 0; }

/* ── Select override ── */
.ap-select { background: #1e293b !important; border: 1px solid var(--ap-border); color: #f1f5f9 !important; border-radius: 8px; padding: 4px 10px; font-size: 0.78rem; font-family: var(--ap-font-head); font-weight: 600; -webkit-appearance: auto; appearance: auto; }
.ap-select option { background: #1e293b !important; color: #f1f5f9 !important; }
.ap-select:focus { outline: none; border-color: var(--ap-blue); }

/* ── Region Toggle ── */
.ap-region-btn { border: 1px solid var(--ap-border); background: transparent; color: var(--ap-muted); font-family: var(--ap-font-head); font-weight: 600; font-size: 0.72rem; padding: 4px 10px; border-radius: 7px; cursor: pointer; transition: all 0.15s; }
.ap-region-btn:hover { color: #fff; border-color: var(--ap-border2); }
.ap-region-btn.active { background: var(--ap-cyan); border-color: var(--ap-cyan); color: #080c14; }

/* ── Collapse ── */
.ap-collapse-btn { font-size: 0.72rem; color: var(--ap-blue); cursor: pointer; border: none; background: none; padding: 0; display: inline-flex; align-items: center; gap: 4px; font-family: var(--ap-font-mono); font-weight: 600; text-decoration: none; }
.ap-collapse-btn:hover { color: var(--ap-cyan); }

/* ── Divider ── */
.ap-divider { border: none; border-top: 1px solid var(--ap-border); margin: 14px 0; }

/* ── Recent flights mini-table ── */
.ap-recent-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.75rem; }
.ap-recent-row:last-child { border-bottom: none; }
.ap-recent-route a { color: var(--ap-cyan); text-decoration: none; font-family: var(--ap-font-mono); font-weight: 600; }
.ap-recent-route a:hover { color: var(--ap-text-head); }

/* ── Landing rate colors ── */
.ldg-good { color: var(--ap-green); }
.ldg-ok   { color: var(--ap-amber); }
.ldg-bad  { color: var(--ap-red); }

/* ── Stagger animation ── */
.ap-stagger > * { animation: ap-fadeup 0.4s both; }
@keyframes ap-fadeup { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
.ap-stagger > *:nth-child(1){animation-delay:.05s} .ap-stagger > *:nth-child(2){animation-delay:.10s}
.ap-stagger > *:nth-child(3){animation-delay:.15s} .ap-stagger > *:nth-child(4){animation-delay:.20s}
.ap-stagger > *:nth-child(5){animation-delay:.25s} .ap-stagger > *:nth-child(6){animation-delay:.30s}

/* ── Mission Cards ── */
.ap-mission-grid { display: grid !important; grid-template-columns: repeat(5, 1fr) !important; gap: 12px !important; }
@media (max-width: 1399px) { .ap-mission-grid { grid-template-columns: repeat(4, 1fr) !important; } }
@media (max-width: 991px)  { .ap-mission-grid { grid-template-columns: repeat(3, 1fr) !important; } }
@media (max-width: 767px)  { .ap-mission-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 8px !important; } }
@media (max-width: 575px)  { .ap-mission-grid { grid-template-columns: 1fr !important; } }
.ap-mission-card { background: var(--ap-card-bg); border: 1px solid var(--ap-border); border-radius: 14px; padding: 18px 16px; position: relative; overflow: hidden; transition: border-color 0.2s, transform 0.15s; }
.ap-mission-card:hover { border-color: var(--ap-border2); transform: translateY(-2px); }
.ap-mission-card::before { content: ''; position: absolute; inset: 0; pointer-events: none; }
.ap-mission-card[data-accent="streak"]::before    { background: linear-gradient(135deg, rgba(245,158,11,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="milestone"]::before  { background: linear-gradient(135deg, rgba(129,140,248,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="rank"]::before       { background: linear-gradient(135deg, rgba(59,130,246,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="explore"]::before    { background: linear-gradient(135deg, rgba(34,211,238,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="aircraft"]::before   { background: linear-gradient(135deg, rgba(34,197,94,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="landing"]::before    { background: linear-gradient(135deg, rgba(74,222,128,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="daily"]::before      { background: linear-gradient(135deg, rgba(236,72,153,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="distance"]::before   { background: linear-gradient(135deg, rgba(251,146,60,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="airlines"]::before   { background: linear-gradient(135deg, rgba(245,158,11,0.12) 0%, transparent 60%); }
.ap-mission-card[data-accent="weekend"]::before    { background: linear-gradient(135deg, rgba(234,179,8,0.12) 0%, transparent 60%); }
.ap-mission-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 12px; }
.ap-mission-icon.ic-streak    { background: rgba(245,158,11,0.15); color: var(--ap-amber); }
.ap-mission-icon.ic-milestone { background: rgba(129,140,248,0.15); color: var(--ap-violet); }
.ap-mission-icon.ic-rank      { background: rgba(59,130,246,0.15); color: var(--ap-blue); }
.ap-mission-icon.ic-explore   { background: rgba(34,211,238,0.15); color: var(--ap-cyan); }
.ap-mission-icon.ic-aircraft  { background: rgba(34,197,94,0.15); color: var(--ap-green); }
.ap-mission-icon.ic-landing   { background: rgba(74,222,128,0.15); color: var(--ap-green); }
.ap-mission-icon.ic-daily     { background: rgba(236,72,153,0.15); color: #ec4899; }
.ap-mission-icon.ic-distance  { background: rgba(251,146,60,0.15); color: #fb923c; }
.ap-mission-icon.ic-airlines  { background: rgba(245,158,11,0.15); color: #f59e0b; }
.ap-mission-icon.ic-weekend   { background: rgba(234,179,8,0.15); color: #eab308; }
.ap-mission-title { font-family: var(--ap-font-head); font-weight: 700; font-size: 0.78rem; color: var(--ap-text-head); margin-bottom: 4px; }
.ap-mission-value { font-family: var(--ap-font-head); font-weight: 800; font-size: 1.5rem; color: var(--ap-text-head); line-height: 1; margin-bottom: 6px; font-variant-numeric: lining-nums tabular-nums; }
.ap-mission-sub { font-family: var(--ap-font-mono); font-size: 0.68rem; color: var(--ap-muted); }
.ap-mission-progress { height: 4px; background: var(--ap-progress-bg); border-radius: 99px; overflow: hidden; margin-top: 10px; }
.ap-mission-progress-bar { height: 100%; border-radius: 99px; transition: width 0.6s cubic-bezier(0.4,0,0.2,1); }

/* ── Airline Snapshot ── */
.ap-asnap-featured { background: var(--ap-card-bg); border: 1px solid var(--ap-border); border-radius: 14px; padding: 16px 18px; transition: border-color 0.2s, background 0.15s, transform 0.15s; position: relative; overflow: hidden; }
.ap-asnap-featured:hover { border-color: rgba(129,140,248,0.4); background: rgba(129,140,248,0.04); transform: translateY(-2px); }
.ap-asnap-featured::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(129,140,248,0.06) 0%, transparent 50%); pointer-events: none; }
.ap-asnap-icao { font-family: var(--ap-font-mono); font-weight: 600; font-size: 0.78rem; color: var(--ap-cyan); letter-spacing: 0.05em; }
.ap-asnap-name { font-family: var(--ap-font-head); font-weight: 700; font-size: 0.92rem; color: var(--ap-text-head); margin-top: 2px; }
.ap-asnap-share-bar { height: 5px; background: var(--ap-progress-bg); border-radius: 99px; overflow: hidden; }
.ap-asnap-share-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--ap-violet), var(--ap-blue)); transition: width 0.5s ease; }
.ap-asnap-trend { font-family: var(--ap-font-mono); font-size: 0.68rem; font-weight: 600; padding: 2px 8px; border-radius: 6px; white-space: nowrap; display: inline-flex; align-items: center; gap: 3px; }
.ap-asnap-trend.up   { color: #4ade80; background: rgba(74,222,128,0.12); }
.ap-asnap-trend.down { color: #f87171; background: rgba(248,113,113,0.12); }
.ap-asnap-trend.flat { color: var(--ap-muted); background: var(--ap-tag-bg); }
.ap-asnap-compact-list { border-top: 1px solid var(--ap-border); padding-top: 8px; }
.ap-asnap-row { display: flex; align-items: center; gap: 12px; padding: 8px 10px; border-radius: 10px; transition: background 0.15s; color: var(--ap-text); text-decoration: none; }
.ap-asnap-row:hover { background: rgba(129,140,248,0.05); }
.ap-asnap-row-left { display: flex; align-items: center; gap: 8px; min-width: 180px; flex-shrink: 0; }
.ap-asnap-row-rank { font-family: var(--ap-font-mono); font-size: 0.68rem; color: var(--ap-muted); font-weight: 600; min-width: 24px; }
.ap-asnap-row-name { font-family: var(--ap-font-head); font-weight: 600; font-size: 0.82rem; color: var(--ap-text-head); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ap-asnap-row-bar { flex: 1; min-width: 60px; max-width: 120px; }
.ap-asnap-row-stats { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; justify-content: flex-end; flex: 1; }

/* ── Route Cards ── */
.ap-route-card { background: var(--ap-card-bg); border: 1px solid var(--ap-border); border-radius: 10px; padding: 10px 12px; transition: border-color 0.15s, background 0.15s; overflow: hidden; height: 100%; display: flex; flex-direction: column; }
.ap-route-card:hover { border-color: rgba(34,211,238,0.35); background: rgba(34,211,238,0.05); }
.ap-route-icao { font-family: var(--ap-font-head); font-size: 0.9rem; font-weight: 700; color: var(--ap-text-head); }

/* ── Schnellstart ── */
.ap-qs-flightnr { font-family: var(--ap-font-mono); font-size: 0.68rem; color: var(--ap-cyan); font-weight: 600; }
.ap-qs-booked { display: inline-flex; align-items: center; gap: 3px; font-family: var(--ap-font-mono); font-size: 0.6rem; color: var(--ap-green); font-weight: 600; }
.ap-qs-action-btn { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 7px; border: 1px solid var(--ap-border); background: transparent; color: var(--ap-muted); font-size: 0.82rem; cursor: pointer; transition: all 0.15s; text-decoration: none; }
.ap-qs-action-btn:hover { color: var(--ap-text-head); border-color: var(--ap-border2); background: var(--ap-surface); }
.ap-qs-book-btn:hover { color: var(--ap-cyan); border-color: var(--ap-cyan); }
.ap-qs-logo { height: 28px; width: auto; max-width: 80px; object-fit: contain; background: #fff; border-radius: 6px; padding: 3px 8px; flex-shrink: 0; }
.ap-qs-aptnames { font-family: var(--ap-font-body); font-size: 0.65rem; color: var(--ap-muted); margin-top: 1px; line-height: 1.3; opacity: 0.8; }
.ap-qs-sf-row { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
.ap-qs-sf-pill { font-family: var(--ap-font-mono); font-size: 0.58rem; font-weight: 600; color: var(--ap-green); background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2); border-radius: 4px; padding: 1px 5px; white-space: nowrap; }

/* ── Duell-Vergleich ── */
.ap-duel-bar { margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--ap-border); font-size: 0.7rem; animation: ap-fadeup 0.3s both; }
.ap-duel-row { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
.ap-duel-track { flex: 1; height: 6px; background: var(--ap-progress-bg); border-radius: 99px; overflow: hidden; display: flex; }
.ap-duel-fill-me { height: 100%; background: var(--ap-cyan); border-radius: 99px 0 0 99px; transition: width 0.5s ease; }
.ap-duel-fill-rival { height: 100%; background: var(--ap-amber); border-radius: 0 99px 99px 0; transition: width 0.5s ease; }
.ap-duel-val { font-family: var(--ap-font-mono); font-size: 0.72rem; font-weight: 700; }
.ap-duel-val.win { color: var(--ap-green); } .ap-duel-val.lose { color: var(--ap-red); opacity: 0.7; } .ap-duel-val.tie { color: var(--ap-muted); }
#ap-compare-tbody tr { border-bottom: 1px solid var(--ap-border); }
#ap-compare-tbody td { padding: 8px 10px; font-family: var(--ap-font-mono); }
.ap-ct-win { color: var(--ap-green); font-weight: 700; }
.ap-ct-lose { color: var(--ap-muted); opacity: 0.7; }
.ap-ct-icon { font-size: 0.9rem; text-align: center; }

/* ── Widget Overrides ── */
.ap-wrap .table-responsive { overflow-x: visible; }
.ap-wrap table { width: 100%; table-layout: auto; }
.ap-wrap table td, .ap-wrap table th { white-space: normal; word-break: break-word; }
.gap-x-3 { column-gap: 12px; } .gap-y-1 { row-gap: 4px; }

/* ── Responsive ── */
@media (max-width: 1399px) { .ap-kpi-strip { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; } }
@media (max-width: 991px)  { .ap-kpi-strip { grid-template-columns: repeat(3, 1fr); } .ap-page-title { font-size: 1.3rem; } .ap-asnap-row-left { min-width: 140px; } .ap-asnap-row-bar { display: none; } }
@media (max-width: 767px)  { .ap-kpi-strip { grid-template-columns: repeat(2, 1fr); gap: 8px; } .ap-kpi { padding: 12px 14px; } .ap-kpi-value { font-size: 1.25rem; } .ap-mission-card { padding: 14px 12px; } .ap-mission-value { font-size: 1.25rem; } .ap-glass-header { padding: 12px 14px; flex-wrap: wrap; gap: 8px; } .ap-filter-pill { flex-wrap: wrap; } .ap-filter-pill button { font-size: 0.7rem; padding: 4px 8px; } .ap-asnap-row { flex-wrap: wrap; gap: 6px; } .ap-asnap-row-stats { justify-content: flex-start; } .ap-asnap-row-bar { display: none; } }
@media (max-width: 575px)  { .ap-kpi-strip { grid-template-columns: repeat(2, 1fr); } .ap-page-title { font-size: 1.1rem; } .ap-rank-item { padding: 10px 12px; } .ap-asnap-row-left { min-width: auto; } }
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
#ap-dynamic-content { transition: opacity 0.25s ease; }
</style>

{{-- ═══ LAYOUT ═══ --}}
<div class="ap-wrap" style="padding: 20px 0;">

  {{-- ── TOPBAR ── --}}
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
      <div>
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="ph-fill ph-wave-sine" style="color:var(--ap-cyan);font-size:1.4rem;"></i>
          <h1 class="ap-page-title mb-0">{{ $t('title') }}</h1>
          <a href="{{ url('/airline-info-pulse/guide') }}" title="{{ $t('pilot_guide') }}" style="color:var(--ap-muted);font-size:1.1rem;transition:color .2s;" onmouseover="this.style.color='var(--ap-cyan)'" onmouseout="this.style.color='var(--ap-muted)'"><i class="ph-fill ph-question"></i></a>
        </div>
        <div class="ap-section-label mt-1">{{ now()->format('d. M. Y') }} · {{ $tfNice }}</div>
      </div>
    </div>
    <div class="ap-filter-pill" id="ap-tf-buttons">
      @foreach(['today','yesterday','week','month','quarter','year'] as $k)
        <button type="button" data-tf="{{ $k }}" class="{{ $filter==$k?'active':'' }}" onclick="apTimeFilter('{{ $k }}',this)">{{ $t($k) }}</button>
      @endforeach
    </div>
  </div>

  <div id="ap-dynamic-content">

    {{-- KPI Strip --}}
    @include('airlineinfopulse::partials.kpis')

    {{-- Cockpit --}}
    @include('airlineinfopulse::partials.cockpit')

    {{-- Widgets: FlightBoard + ActiveBookings --}}
    @include('airlineinfopulse::partials.widgets')

    {{-- Top Lists + Feed --}}
    <div class="row g-3" style="align-items:stretch;">
      <div class="col-12 col-xl-8">
        @include('airlineinfopulse::partials.toplists')
      </div>
      <div class="col-12 col-xl-4 d-flex">
        @include('airlineinfopulse::partials.feed')
      </div>
    </div>

    {{-- Mission Cards --}}
    @include('airlineinfopulse::partials.missions')

    {{-- Schnellstart --}}
    @include('airlineinfopulse::partials.quickstart')

    {{-- Airline Snapshot --}}
    @include('airlineinfopulse::partials.snapshot')

  </div>{{-- /ap-dynamic-content --}}
</div>

{{-- ═══ JAVASCRIPT ═══ --}}
<script>
(function(){
  var csrfToken=document.querySelector('meta[name="csrf-token"]');
  csrfToken=csrfToken?csrfToken.getAttribute('content'):'';

  // Translations for JS
  var _t={
    noFlightsRegion:{!! json_encode($t('no_flights_region'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    viewFlight:{!! json_encode($t('view_flight'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    fleetTypes:{!! json_encode($t('duel_fleet_types'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    bestLanding:{!! json_encode($t('duel_best_landing'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    today:{!! json_encode($t('duel_today'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    nFlights:{!! json_encode($t('duel_n_flights'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    airlines:{!! json_encode($t('duel_airlines'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    weekendPct:{!! json_encode($t('duel_weekend_pct'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    streak:{!! json_encode($t('duel_streak'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    streakUnit:{!! json_encode($t('duel_streak_unit'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    longest:{!! json_encode($t('duel_longest'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    totalFlights:{!! json_encode($t('flights'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    ranking:{!! json_encode($t('your_ranking'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!},
    airports:{!! json_encode($t('airports'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!}
  };
  /* ═══ AJAX Zeitfilter ═══ */
  window.apTimeFilter=function(tf,btn){
    document.querySelectorAll('#ap-tf-buttons button').forEach(function(b){b.classList.remove('active')});
    btn.classList.add('active');
    var dynContent=document.getElementById('ap-dynamic-content');
    if(dynContent)dynContent.style.opacity='0.45';
    var params=new URLSearchParams(window.location.search);
    params.set('filter',tf);
    var newUrl=window.location.pathname+'?'+params.toString();
    window.location.href=newUrl;
  };

  /* ═══ Schnellstart ═══ */
  var allFlights={!! $quickstartJson !!};
  var currentRegion='eu';

  window.apQsFilter=function(region,btn){
    currentRegion=region;
    document.querySelectorAll('#ap-qs-regions .ap-region-btn').forEach(function(b){b.classList.remove('active')});
    if(btn)btn.classList.add('active');
    renderFlights();
  };

  function renderFlights(){
    var grid=document.getElementById('ap-qs-grid');
    if(!grid)return;
    var filtered=allFlights.filter(function(f){return f.region_d===currentRegion||f.region_a===currentRegion;});
    filtered=filtered.slice(0,8);
    if(filtered.length===0){grid.innerHTML='<div class="col-12" style="color:var(--ap-muted);font-size:0.8rem;">'+_t.noFlightsRegion+'</div>';return;}
    var html='';
    filtered.forEach(function(f){
      html+='<div class="col-12 col-sm-6 col-lg-3"><div class="ap-route-card">';
      html+='<div class="d-flex justify-content-between align-items-start"><div style="min-width:0;">';
      html+='<div class="ap-route-icao">'+f.dep+' <span style="color:var(--ap-cyan);">→</span> '+f.arr+'</div>';
      if(f.depName||f.arrName)html+='<div class="ap-qs-aptnames">'+(f.depName||'')+' → '+(f.arrName||'')+'</div>';
      if(f.fltNr)html+='<div class="ap-qs-flightnr">'+f.fltNr+'</div>';
      html+='</div><div class="text-end" style="flex-shrink:0;">';
      if(f.time)html+='<div style="font-family:var(--ap-font-mono);font-size:0.68rem;color:var(--ap-cyan);">~'+f.time+'</div>';
      if(f.dist)html+='<div style="font-family:var(--ap-font-mono);font-size:0.62rem;color:var(--ap-muted);">'+f.dist+'</div>';
      html+='</div></div>';
      if(f.airline)html+='<div class="d-flex align-items-center gap-2 mt-2" style="min-height:28px;">'+(f.logo?'<img src="'+f.logo+'" alt="" class="ap-qs-logo" onerror="this.style.display=\'none\'">':'')+
        '<span style="font-size:0.72rem;color:var(--ap-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+f.airline+'</span></div>';
      if(f.subfleets&&f.subfleets.length){html+='<div class="ap-qs-sf-row">';f.subfleets.forEach(function(sf){html+='<span class="ap-qs-sf-pill"><i class="ph-fill ph-airplane-tilt" style="font-size:0.5rem;"></i> '+sf+'</span>';});html+='</div>';}
      html+='<div class="d-flex justify-content-between align-items-center mt-auto pt-2"><span></span>';
      html+='<div class="d-flex gap-1">';
      html+='<a href="'+f.url+'" class="ap-qs-action-btn ap-qs-book-btn" title="Buchen"><i class="ph-fill ph-calendar-plus"></i></a>';
      html+='<a href="'+f.url+'" class="ap-qs-action-btn" title="'+_t.viewFlight+'"><i class="ph-fill ph-eye"></i></a>';
      html+='</div></div></div></div>';
    });
    grid.innerHTML=html;
  }
  renderFlights();

  /* ═══ Piloten-Duell ═══ */
  var myData={
    streak:{{ $missions['streak'] ?? 0 }},
    flights:{{ $missions['total_flights'] ?? 0 }},
    rank:{{ $missions['rank'] ?? 'null' }},
    airports:{{ $missions['airports'] ?? 0 }},
    acTypes:{{ $missions['aircraft_types'] ?? 0 }},
    bestLanding:{{ $missions['best_landing'] ?? 'null' }},
    todayFlights:{{ $missions['today_flights'] ?? 0 }},
    longestDist:{{ round(($missions['longest_flight'] ?? 0) * $units['distance_factor'], 1) }},
    airlinesFlown:{{ $missions['airlines_flown'] ?? 0 }},
    weekendPct:{{ $missions['weekend_pct'] ?? 0 }}
  };
  var myName={!! json_encode(explode(' ',$user->name ?? 'Du')[0], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) !!};

  var missionMap={
    streak:{key:'streak',label:_t.streak,unit:' '+_t.streakUnit,higher:true},
    milestone:{key:'flights',label:_t.totalFlights,unit:'',higher:true},
    rank:{key:'rank',label:_t.ranking,unit:'',higher:false},
    explore:{key:'airports',label:_t.airports,unit:'',higher:true},
    aircraft:{key:'acTypes',label:_t.fleetTypes,unit:'',higher:true},
    landing:{key:'bestLanding',label:_t.bestLanding,unit:' fpm',higher:false},
    daily:{key:'todayFlights',label:_t.today,unit:' '+_t.nFlights,higher:true},
    distance:{key:'longestDist',label:_t.longest,unit:' {{ $units['distance_label'] }}',higher:true},
    airlines:{key:'airlinesFlown',label:_t.airlines,unit:'',higher:true},
    weekend:{key:'weekendPct',label:_t.weekendPct,unit:'%',higher:true}
  };

  var sel=document.getElementById('ap-compare-pilot');
  var table=document.getElementById('ap-compare-table');
  if(sel){
    sel.addEventListener('change',async function(){
      var pid=this.value;
      document.querySelectorAll('.ap-duel-bar').forEach(function(el){el.remove()});
      var tbody=document.getElementById('ap-compare-tbody');
      if(tbody)tbody.innerHTML='';
      if(!pid){if(table)table.style.display='none';return;}
      sel.disabled=true;
      try{
        var resp=await fetch('{{ route("airlineinfopulse.compare") }}?compare_pilot='+pid,{headers:{'X-Requested-With':'XMLHttpRequest'}});
        var rival=await resp.json();
        if(!rival.error)showComparison(rival);
      }catch(e){console.error(e);}
      sel.disabled=false;
    });
  }

  function showComparison(rival){
    var meWins=0,rivalWins=0;
    Object.entries(missionMap).forEach(function(e){
      var accent=e[0],cfg=e[1];
      var card=document.querySelector('.ap-mission-card[data-accent="'+accent+'"]');
      if(!card)return;
      var mv=myData[cfg.key],rv=rival[cfg.key];
      if(mv===null&&rv===null)return;
      var mvN=mv||0,rvN=rv||0;
      var meWin,tie;
      if(cfg.higher){meWin=mvN>rvN;tie=mvN===rvN;}
      else{meWin=Math.abs(mvN)<Math.abs(rvN)||(mvN!==0&&rvN===0);tie=mvN===rvN;if(mvN===0&&rvN!==0&&cfg.key==='bestLanding')meWin=false;}
      if(!tie){if(meWin)meWins++;else rivalWins++;}
      var bar=document.createElement('div');bar.className='ap-duel-bar';
      var mA=Math.abs(mvN),rA=Math.abs(rvN),total=mA+rA||1;
      var mP=Math.round((mA/total)*100),rP=100-mP;
      var mC=tie?'tie':(meWin?'win':'lose'),rC=tie?'tie':(meWin?'lose':'win');
      bar.innerHTML='<div class="ap-duel-row"><span class="ap-duel-val '+mC+'">'+mvN+'</span><div class="ap-duel-track"><div class="ap-duel-fill-me" style="width:'+mP+'%"></div><div class="ap-duel-fill-rival" style="width:'+rP+'%"></div></div><span class="ap-duel-val '+rC+'">'+rvN+'</span></div>';
      card.appendChild(bar);
    });

    if(table){
      table.style.display='';
      document.getElementById('ap-compare-rival-name').textContent=rival.name;
      document.getElementById('ap-compare-me-name').textContent=myName;
      document.getElementById('ap-ct-rival-head').textContent=rival.name;
      var scoreEl=document.getElementById('ap-compare-score');
      var col=meWins>rivalWins?'var(--ap-green)':(meWins<rivalWins?'var(--ap-red)':'var(--ap-amber)');
      var ico=meWins>rivalWins?'🏆':(meWins<rivalWins?'💪':'🤝');
      scoreEl.innerHTML='<span style="color:'+col+';">'+ico+' '+meWins+' : '+rivalWins+'</span>';
      var tbody=document.getElementById('ap-compare-tbody');
      tbody.innerHTML='';
      Object.entries(missionMap).forEach(function(e){
        var cfg=e[1];var mv=myData[cfg.key],rv=rival[cfg.key];
        if(mv===null&&rv===null)return;
        var mvN=mv||0,rvN=rv||0;var meWin,tie;
        if(cfg.higher){meWin=mvN>rvN;tie=mvN===rvN;}else{meWin=Math.abs(mvN)<Math.abs(rvN);tie=mvN===rvN;}
        var mCls=tie?'':(meWin?'ap-ct-win':'ap-ct-lose');
        var rCls=tie?'':(meWin?'ap-ct-lose':'ap-ct-win');
        var icon=tie?'🤝':(meWin?'✅':'❌');
        var tr=document.createElement('tr');
        tr.innerHTML='<td style="color:var(--ap-text);font-family:var(--ap-font-head);font-weight:600;">'+cfg.label+'</td><td style="text-align:right;" class="'+mCls+'">'+mvN+'</td><td class="ap-ct-icon">'+icon+'</td><td class="'+rCls+'">'+rvN+'</td>';
        tbody.appendChild(tr);
      });
    }
  }
})();
</script>
@endsection
