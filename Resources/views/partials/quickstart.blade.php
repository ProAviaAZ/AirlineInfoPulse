{{-- partials/quickstart.blade.php --}}
<div class="ap-glass mb-4">
  <div class="ap-glass-header">
    <div class="d-flex align-items-center gap-2">
      <i class="ph-fill ph-compass" style="color:var(--ap-cyan);font-size:1rem;"></i>
      <span class="ap-card-title">{{ $t('quickstart') }}</span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <div class="d-flex gap-1" id="ap-qs-regions">
        <button class="ap-region-btn active" data-region="eu" onclick="apQsFilter('eu',this)">{{ $t('region_eu') }}</button>
        <button class="ap-region-btn" data-region="asia" onclick="apQsFilter('asia',this)">{{ $t('region_asia') }}</button>
        <button class="ap-region-btn" data-region="africa" onclick="apQsFilter('africa',this)">{{ $t('region_africa') }}</button>
        <button class="ap-region-btn" data-region="oceania" onclick="apQsFilter('oceania',this)">{{ $t('region_oceania') }}</button>
        <button class="ap-region-btn" data-region="us" onclick="apQsFilter('us',this)">{{ $t('region_us') }}</button>
      </div>
    </div>
  </div>
  <div style="padding:14px 16px;">
    <div class="row g-3" id="ap-qs-grid">
      <div style="color:var(--ap-muted);font-size:0.8rem;">{{ $t('loading_flights') }}</div>
    </div>
  </div>
</div>
