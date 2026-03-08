{{-- partials/toplists.blade.php --}}
<div class="row g-3">

  {{-- ═══ TOP PILOTS ═══ --}}
  <div class="col-12 col-lg-6">
    <div class="ap-glass h-100">
      <div class="ap-glass-header">
        <div class="d-flex align-items-center gap-2">
          <i class="ph-fill ph-trophy" style="color:var(--ap-amber);"></i>
          <span class="ap-card-title">{{ $t('top_pilots') }}</span>
        </div>
        <form method="get" class="m-0">
          <input type="hidden" name="filter" value="{{ $filter }}">
          <select class="ap-select" name="psort" onchange="this.form.submit()">
            <option value="flights" {{ $pilotSort==='flights'?'selected':'' }}>{{ $t('by_flights') }}</option>
            <option value="time"    {{ $pilotSort==='time'?'selected':'' }}>{{ $t('by_time') }}</option>
            <option value="dist"    {{ $pilotSort==='dist'?'selected':'' }}>{{ $t('by_distance') }}</option>
          </select>
        </form>
      </div>
      <div style="padding:14px 16px;">
        @forelse($topPilots as $pi)
          @php
            $u = $pi['user'] ?? null;
            if (!$u) continue;
            $rank = $pi['rank'];
            $ex = $pilotExtras[$pi['user_id']] ?? null;
            $medal = $rank===1?'🥇':($rank===2?'🥈':($rank===3?'🥉':'#'.$rank));
          @endphp
          <div class="ap-rank-item">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div>
                <span class="ap-rank-medal">{{ $medal }}</span>
                <span class="ap-rank-name"><a href="{{ $mkPilotUrl($pi['user_id']) }}">{{ $shortName($u->name ?? null) }}</a></span>
              </div>
              <div class="d-flex flex-wrap gap-1">
                <span class="ap-tag ap-tag-blue"><i class="ph-fill ph-airplane"></i>{{ $pi['flights'] }}</span>
                <span class="ap-tag ap-tag-blue"><i class="ph-fill ph-clock-countdown"></i>{{ $fmtMin($pi['block_time']) }}</span>
                @if($pi['avg_landing_rate'])<span class="ap-tag"><i class="ph-fill ph-gauge"></i>{{ round($pi['avg_landing_rate']) }} fpm</span>@endif
              </div>
            </div>

            @if($ex && isset($ex['top_routes']) && $ex['top_routes']->count())
              <div class="mt-2 d-flex flex-wrap gap-1">
                @foreach($ex['top_routes'] as $rt)
                  <span class="ap-tag ap-tag-cyan"><i class="ph-fill ph-map-pin"></i>{{ $rt->route }} ({{ $rt->cnt }}×)</span>
                @endforeach
              </div>
            @endif

            @php $collapseId = 'pd-'.$pi['user_id']; @endphp
            @if($ex && isset($ex['recent']) && $ex['recent']->count() > 1)
              <div class="mt-2">
                <a class="ap-collapse-btn" data-bs-toggle="collapse" href="#{{ $collapseId }}">
                  <i class="ph-fill ph-caret-down"></i> {{ $t('recent_flights') }}
                </a>
                <div class="collapse mt-2" id="{{ $collapseId }}">
                  @foreach($ex['recent'] as $rf)
                    @php $lr = $rf->landing_rate ? (int)$rf->landing_rate : null; @endphp
                    <div class="ap-recent-row">
                      <div class="ap-recent-route">
                        <a href="{{ $mkPirepUrl($rf->id) }}" target="_blank">{{ $rf->dep ?? '—' }} → {{ $rf->arr ?? '—' }}</a>
                      </div>
                      <div class="d-flex gap-2 align-items-center">
                        @if($rf->blk)<span style="font-family:var(--ap-font-mono);font-size:0.7rem;color:var(--ap-muted);">{{ $fmtMin((int)$rf->blk) }}</span>@endif
                        @if(!is_null($lr))<span class="{{ $landingClass($lr) }}" style="font-family:var(--ap-font-mono);font-size:0.7rem;"><i class="ph-fill ph-gauge me-1"></i>{{ $lr }}</span>@endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif

            @if($ex && isset($ex['last_ts']) && $ex['last_ts'])
              <div style="font-family:var(--ap-font-mono);font-size:0.65rem;color:var(--ap-muted);margin-top:8px;">
                <i class="ph-fill ph-clock me-1"></i>{{ $ex['last_ts']->format('d.m. H:i') }}
              </div>
            @endif
          </div>
        @empty
          <p style="color:var(--ap-muted);font-size:0.8rem;">{{ $t('no_data_period') }}</p>
        @endforelse

        @if(count($topPilotsAll) > count($topPilots))
          <div class="text-center mt-2">
            <form method="get">
              <input type="hidden" name="filter" value="{{ $filter }}">
              <input type="hidden" name="psort" value="{{ $pilotSort }}">
              <input type="hidden" name="asort" value="{{ $acSort }}">
              <button class="ap-collapse-btn" name="pilot_more" value="{{ $showAllPilots ? 0 : 1 }}">
                <i class="ph-fill ph-{{ $showAllPilots ? 'caret-up' : 'caret-down' }}"></i>
                {{ $showAllPilots ? $t('show_less') : $t('show_more') }}
              </button>
            </form>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ═══ TOP AIRCRAFT ═══ --}}
  <div class="col-12 col-lg-6">
    <div class="ap-glass h-100">
      <div class="ap-glass-header">
        <div class="d-flex align-items-center gap-2">
          <i class="ph-fill ph-airplane" style="color:var(--ap-violet);"></i>
          <span class="ap-card-title">{{ $t('top_aircraft') }}</span>
        </div>
        <form method="get" class="m-0">
          <input type="hidden" name="filter" value="{{ $filter }}">
          <select class="ap-select" name="asort" onchange="this.form.submit()">
            <option value="time"    {{ $acSort==='time'?'selected':'' }}>{{ $t('by_time') }}</option>
            <option value="flights" {{ $acSort==='flights'?'selected':'' }}>{{ $t('by_flights') }}</option>
          </select>
        </form>
      </div>
      <div style="padding:14px 16px;">
        @php $acRank = 0; @endphp
        @forelse($topAircraft as $ac)
          @php
            $acRank++;
            $a = $ac['aircraft'] ?? null;
            if (!$a) continue;
            $ex = $acExtras[$ac['aircraft_id']] ?? null;
            $medal = $acRank===1?'🥇':($acRank===2?'🥈':($acRank===3?'🥉':'#'.$acRank));
            $typeFull = $a ? $fullAcName($a) : null;
            $airlineName = $a && $a->subfleet && $a->subfleet->airline ? $a->subfleet->airline->name : '';
          @endphp
          <div class="ap-rank-item">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div>
                <span class="ap-rank-medal">{{ $medal }}</span>
                <a class="ap-rank-name" href="{{ $mkAircraftUrl($a->registration ?? null) }}" style="color:var(--ap-text-head);text-decoration:none;">{{ $a->registration ?? '#'.$ac['aircraft_id'] }}</a>
                @if($typeFull)<span class="ap-tag ms-1" style="vertical-align:middle;">{{ $typeFull }}</span>@endif
                @if($airlineName)<span class="ap-tag ap-tag-blue ms-1" style="vertical-align:middle;">{{ $airlineName }}</span>@endif
              </div>
              <div class="d-flex flex-wrap gap-1">
                <span class="ap-tag ap-tag-blue"><i class="ph-fill ph-airplane"></i>{{ $ac['flights'] }}</span>
                <span class="ap-tag ap-tag-blue"><i class="ph-fill ph-clock-countdown"></i>{{ $fmtMin($ac['block_time']) }}</span>
                @if($ac['fuel_per_nm'] > 0)
                  <span class="ap-tag ap-tag-amber"><i class="ph-fill ph-gas-pump"></i>{{ number_format($effVal($ac['fuel_per_nm']), 2, ',', '.') }} {{ $units['efficiency_label'] }}</span>
                @endif
                @if($ac['fuel_per_hour'] > 0)
                  <span class="ap-tag ap-tag-amber"><i class="ph-fill ph-timer"></i>{{ number_format($fuelVal($ac['fuel_per_hour']), 0, '', '.') }} {{ $units['fuel_label'] }}/h</span>
                @endif
                @if($ac['co2'] > 0)
                  <span class="ap-tag ap-tag-green"><i class="ph-fill ph-leaf"></i>{{ number_format($fuelVal($ac['co2']) / 1000, 2, ',', '.') }} t CO₂</span>
                @endif
              </div>
            </div>

            @if($ex && isset($ex['top_routes']) && $ex['top_routes']->count())
              <div class="mt-2 d-flex flex-wrap gap-1">
                @foreach($ex['top_routes'] as $rt)
                  <span class="ap-tag ap-tag-cyan"><i class="ph-fill ph-map-pin"></i>{{ $rt->route }} ({{ $rt->cnt }}×)</span>
                @endforeach
              </div>
            @endif

            @php $collapseId = 'ad-'.$ac['aircraft_id']; @endphp
            @if($ex && isset($ex['recent']) && $ex['recent']->count() > 1)
              <div class="mt-2">
                <a class="ap-collapse-btn" data-bs-toggle="collapse" href="#{{ $collapseId }}">
                  <i class="ph-fill ph-caret-down"></i> {{ $t('recent_flights') }}
                </a>
                <div class="collapse mt-2" id="{{ $collapseId }}">
                  @foreach($ex['recent'] as $rf)
                    @php
                      $recentUsers = $ex['recent_users'] ?? collect();
                      $pu = isset($rf->user_id) ? $recentUsers->get($rf->user_id) : null;
                      $lr = $rf->landing_rate ? (int)$rf->landing_rate : null;
                    @endphp
                    <div class="ap-recent-row">
                      <div class="ap-recent-route">
                        <a href="{{ $mkPirepUrl($rf->id) }}" target="_blank">{{ $rf->dep ?? '—' }} → {{ $rf->arr ?? '—' }}</a>
                        @if($pu)
                          <span style="color:var(--ap-muted);font-size:0.68rem;"> · <a href="{{ $mkPilotUrl($pu->id) }}" style="color:var(--ap-muted);">{{ $shortName($pu->name ?? null) }}</a></span>
                        @endif
                      </div>
                      <div class="d-flex gap-2 align-items-center">
                        @if($rf->blk)<span style="font-family:var(--ap-font-mono);font-size:0.7rem;color:var(--ap-muted);">{{ $fmtMin((int)$rf->blk) }}</span>@endif
                        @if(!is_null($lr))<span class="{{ $landingClass($lr) }}" style="font-family:var(--ap-font-mono);font-size:0.7rem;"><i class="ph-fill ph-gauge me-1"></i>{{ $lr }}</span>@endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif

            @if($ex && isset($ex['last_ts']) && $ex['last_ts'])
              <div style="font-family:var(--ap-font-mono);font-size:0.65rem;color:var(--ap-muted);margin-top:8px;">
                <i class="ph-fill ph-clock me-1"></i>{{ $ex['last_ts']->format('d.m. H:i') }}
              </div>
            @endif
          </div>
        @empty
          <p style="color:var(--ap-muted);font-size:0.8rem;">{{ $t('no_data_period') }}</p>
        @endforelse

        @if(count($topAircraftAll) > count($topAircraft))
          <div class="text-center mt-2">
            <form method="get">
              <input type="hidden" name="filter" value="{{ $filter }}">
              <input type="hidden" name="psort" value="{{ $pilotSort }}">
              <input type="hidden" name="asort" value="{{ $acSort }}">
              <button class="ap-collapse-btn" name="ac_more" value="{{ $showAllAc ? 0 : 1 }}">
                <i class="ph-fill ph-{{ $showAllAc ? 'caret-up' : 'caret-down' }}"></i>
                {{ $showAllAc ? $t('show_less') : $t('show_more') }}
              </button>
            </form>
          </div>
        @endif
      </div>
    </div>
  </div>

</div>
