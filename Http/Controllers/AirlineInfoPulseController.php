<?php

namespace Modules\AirlineInfoPulse\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\User;
use App\Models\Enums\PirepState;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AirlineInfoPulse\Helpers\PulseHelper;

class AirlineInfoPulseController extends Controller
{
    private $co2Factor;
    private $allAirlines;
    private $airCols = [];
    private $pfx;
    private $units;
    private $minLdg;
    private static $schemaCache = []; // Schema-Calls cachen (pro Request)

    // Erlaubte Filter-Werte (Whitelist)
    private const VALID_FILTERS = ['today', 'yesterday', 'week', 'month', 'quarter', 'year', 'custom'];

    public function __construct()
    {
        $this->co2Factor = config('airlineinfopulse.co2_factor', 3.16);
        $this->pfx = DB::getTablePrefix();
        $this->units = PulseHelper::getUnits();
        $this->minLdg = abs((int) config('airlineinfopulse.min_landing_rate', 10));

        // Airlines einmalig cachen (bereits gecacht durch Laravel's Query Cache im selben Request)
        $this->allAirlines = Airline::all()->keyBy('id');

        // Airlines-Spalten dynamisch erkennen — Ergebnisse cachen
        if ($this->schemaHasTable('airlines')) {
            foreach (['icao', 'iata', 'name', 'logo', 'country'] as $col) {
                if ($this->schemaHasColumn('airlines', $col)) {
                    $this->airCols[] = $col;
                }
            }
        }
    }

    /** Schema::hasTable() mit Cache */
    private function schemaHasTable(string $table): bool
    {
        $key = "table:{$table}";
        if (!isset(self::$schemaCache[$key])) {
            self::$schemaCache[$key] = Schema::hasTable($table);
        }
        return self::$schemaCache[$key];
    }

    /** Schema::hasColumn() mit Cache */
    private function schemaHasColumn(string $table, string $col): bool
    {
        $key = "col:{$table}.{$col}";
        if (!isset(self::$schemaCache[$key])) {
            self::$schemaCache[$key] = Schema::hasColumn($table, $col);
        }
        return self::$schemaCache[$key];
    }

    /** Prefixed table name für Raw-SQL (z.B. 'phpvmspireps') */
    private function t(string $table): string
    {
        return $this->pfx . $table;
    }

    /** Sichere airline SELECT-Spalten für ->select() (prefix handled by Laravel) */
    private function airlineSelectCols(string $prefix = 'air'): array
    {
        $cols = [];
        foreach ($this->airCols as $col) {
            $cols[] = "airlines.{$col} as {$prefix}_{$col}";
        }
        return $cols;
    }

    /** Airline SELECT-Spalten für selectRaw() (prefix NICHT handled by Laravel) */
    private function airlineSelectRaw(string $prefix = 'air'): string
    {
        $parts = [];
        foreach ($this->airCols as $col) {
            $parts[] = "{$this->t('airlines')}.{$col} as {$prefix}_{$col}";
        }
        return $parts ? ', ' . implode(', ', $parts) : '';
    }

    /** Airline GROUP BY-Spalten — OHNE DB-Prefix (Laravel's groupBy() handelt das) */
    private function airlineGroupCols(): array
    {
        $cols = [];
        foreach ($this->airCols as $col) {
            $cols[] = "airlines.{$col}";
        }
        return $cols;
    }

    /** Airline-Wert sicher lesen */
    private function airVal($row, string $col, string $prefix = 'air', $default = '')
    {
        $key = "{$prefix}_{$col}";
        return $row->$key ?? $default;
    }

    public function index(Request $request)
    {
        // Input-Validierung — Whitelist für filter
        $filter = in_array($request->get('filter'), self::VALID_FILTERS) ? $request->get('filter') : 'today';

        // Custom-Dates sicher parsen (nur Y-m-d akzeptieren)
        $customStart = null;
        $customEnd = null;
        if ($filter === 'custom') {
            try {
                $rawStart = $request->get('start', '');
                $rawEnd = $request->get('end', '');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawStart)) $customStart = $rawStart;
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawEnd)) $customEnd = $rawEnd;
            } catch (\Throwable $e) {
                $filter = 'today'; // Fallback bei ungültigen Dates
            }
        }

        $pilotSort   = in_array($request->get('psort', 'flights'), ['flights', 'time', 'dist']) ? $request->get('psort', 'flights') : 'flights';
        $acSort      = in_array($request->get('asort', 'time'), ['time', 'flights']) ? $request->get('asort', 'time') : 'time';
        $showAllPilots = $request->boolean('pilot_more', false);
        $showAllAc     = $request->boolean('ac_more', false);
        $user        = Auth::user();

        $range     = PulseHelper::getDateRange($filter, $customStart, $customEnd);
        $prevRange = PulseHelper::getPreviousPeriod($range['start'], $range['end']);

        // KPIs
        $kpis      = $this->getKpis($range);
        $prevKpis  = $this->getKpis($prevRange);
        $kpiDeltas = $this->buildDeltas($kpis, $prevKpis);

        // Cockpit
        $cockpit      = $this->getCockpit($range, $user);
        $prevCockpit  = $this->getCockpit($prevRange, $user);
        $cockpitDeltas = $this->buildDeltas($cockpit, $prevCockpit);
        $favourites   = $this->getFavourites($range, $user);

        $daysInPeriod = max(1, $range['start']->diffInDays($range['end']) + 1);
        $goalMinutes  = $daysInPeriod * config('airlineinfopulse.daily_goal_minutes', 240);
        $progressPct  = $goalMinutes > 0 ? min(100, round(($cockpit['block_time'] / $goalMinutes) * 100, 1)) : 0;

        // Missions
        $missions = $this->getMissions($user);

        // Pilot comparison dropdown
        $comparePilots = $this->getComparePilotList($user);

        // Top Piloten + Extras
        [$topPilotsAll, $pilotExtras] = $this->getTopPilots($range, $pilotSort);
        $pilotLimit  = $showAllPilots ? 12 : 5;
        $topPilots   = array_slice($topPilotsAll, 0, $pilotLimit);

        // Top Aircraft + Extras
        [$topAircraftAll, $acExtras] = $this->getTopAircraft($range, $acSort);
        $acLimit     = $showAllAc ? 12 : 5;
        $topAircraft = array_slice($topAircraftAll, 0, $acLimit);

        // Quickstart + Flights JSON
        $quickstart     = $this->getQuickstart();
        $quickstartJson = json_encode($quickstart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // Feed
        $feed = $this->getFeed($range, $filter);

        // Snapshot
        $snapshot     = $this->getAirlineSnapshot($range);
        $prevSnapshot = $this->getAirlineSnapshot($prevRange);

        // GDPR helper: "Dan Evans" → "Dan E."
        $shortName = fn(?string $name) => PulseHelper::shortName($name);

        // Unit config for views (reads phpVMS Admin settings)
        $units = $this->units;

        return view('airlineinfopulse::index', compact(
            'filter', 'customStart', 'customEnd', 'pilotSort', 'acSort',
            'showAllPilots', 'showAllAc',
            'range', 'kpis', 'kpiDeltas',
            'cockpit', 'cockpitDeltas', 'favourites', 'progressPct', 'goalMinutes',
            'missions', 'comparePilots',
            'topPilots', 'topPilotsAll', 'pilotExtras',
            'topAircraft', 'topAircraftAll', 'acExtras',
            'quickstartJson',
            'feed', 'snapshot', 'prevSnapshot',
            'user', 'shortName', 'units'
        ));
    }

    /**
     * Pilot Guide page
     */
    public function guide()
    {
        $units = $this->units;

        // Config values for dynamic guide content
        $guideConfig = [
            'ldg_green'       => abs((int) config('airlineinfopulse.landing_rate_thresholds.green', -299)),
            'ldg_orange'      => abs((int) config('airlineinfopulse.landing_rate_thresholds.orange', -499)),
            'daily_goal_h'    => round(config('airlineinfopulse.daily_goal_minutes', 240) / 60, 1),
            'daily_goal_min'  => (int) config('airlineinfopulse.daily_goal_minutes', 240),
            'co2_factor'      => (float) config('airlineinfopulse.co2_factor', 3.16),
            'min_landing_rate'=> $this->minLdg,
            'daily_goal_flights' => (int) config('airlineinfopulse.daily_challenge_flights', 3),
        ];

        return view('airlineinfopulse::guide', compact('units', 'guideConfig'));
    }

    /**
     * AJAX: Piloten-Vergleich
     */
    public function comparePilot(Request $request)
    {
        $cpId = (int) $request->get('compare_pilot', 0);
        if ($cpId <= 0) {
            return response()->json(['error' => 'invalid']);
        }
        $cpUser = User::find($cpId);
        if (!$cpUser) {
            return response()->json(['error' => 'not_found']);
        }

        $q = DB::table('pireps')->where('user_id', $cpId)->where('state', PirepState::ACCEPTED);

        // Lifetime aggregate
        $minLdg = $this->minLdg;
        $lt = (clone $q)->selectRaw("
            COUNT(*) as flights,
            MIN(CASE WHEN landing_rate != 0 AND ABS(landing_rate) >= ? THEN ABS(landing_rate) ELSE NULL END) as best_ldg,
            MAX(distance) as max_dist,
            COUNT(DISTINCT aircraft_id) as ac_types,
            COUNT(DISTINCT airline_id) as airlines_flown,
            SUM(CASE WHEN DAYOFWEEK(submitted_at) IN (1,7) THEN 1 ELSE 0 END) as weekend
        ", [$minLdg])->first();

        $flights = $lt ? (int) $lt->flights : 0;

        // Streak — use PulseHelper (same approach as getMissions)
        $streakDates = (clone $q)
            ->where('submitted_at', '>=', now()->subDays(365)->startOfDay())
            ->selectRaw('DATE(submitted_at) as fd')
            ->distinct()->pluck('fd')
            ->map(fn($d) => substr((string) $d, 0, 10))
            ->unique()->sort()->values()->toArray();
        $streak = PulseHelper::calculateStreak($streakDates);

        // Rank
        $rank = DB::table('pireps')->where('state', PirepState::ACCEPTED)
            ->selectRaw('user_id, COUNT(*) as flights')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > ?', [$flights])
            ->count() + 1;

        // Airports — distinct counts (2 schnelle Queries)
        $dptApts = (clone $q)->whereNotNull('dpt_airport_id')->distinct()->pluck('dpt_airport_id');
        $arrApts = (clone $q)->whereNotNull('arr_airport_id')->distinct()->pluck('arr_airport_id');
        $airports = $dptApts->merge($arrApts)->unique()->count();

        // Milestone
        $milestones = config('airlineinfopulse.milestones', [10,25,50,100,150,200,300,500,750,1000,1500,2000,5000]);
        $nextMs = null; $msDiff = 0;
        foreach ($milestones as $ms) {
            if ($flights < $ms) { $nextMs = $ms; $msDiff = $ms - $flights; break; }
        }

        // Today flights
        $todayFlights = (clone $q)->whereDate('submitted_at', today())->count();

        $weekend = $lt ? (int) $lt->weekend : 0;

        return response()->json([
            'name'           => PulseHelper::shortName($cpUser->name ?? null),
            'user_id'        => $cpId,
            'streak'         => $streak,
            'flights'        => $flights,
            'nextMilestone'  => $nextMs,
            'milestoneDiff'  => $msDiff,
            'rank'           => $rank,
            'airports'       => $airports,
            'acTypes'        => $lt ? (int) $lt->ac_types : 0,
            'bestLanding'    => $lt ? $lt->best_ldg : null,
            'todayFlights'   => $todayFlights,
            'longestDist'    => $lt ? round((float) ($lt->max_dist ?? 0) * $this->units['distance_factor'], 1) : 0,
            'airlinesFlown'  => $lt ? (int) $lt->airlines_flown : 0,
            'weekendFlights' => $weekend,
            'weekendPct'     => $flights > 0 ? round(($weekend / $flights) * 100) : 0,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  PRIVATE QUERY METHODS — alle über DB::table()
    // ═══════════════════════════════════════════════════════════════

    private function getKpis(array $range): array
    {
        $result = DB::table('pireps')
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->selectRaw('
                COUNT(*) as accepted_pireps,
                COALESCE(SUM(flight_time), 0) as block_time,
                COALESCE(SUM(distance), 0) as total_distance,
                COALESCE(SUM(fuel_used), 0) as total_fuel,
                COALESCE(AVG(landing_rate), 0) as avg_landing_rate
            ')->first();

        $totalFlights = DB::table('pireps')
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->count();

        return [
            'flights'          => $totalFlights,
            'block_time'       => (int) ($result->block_time ?? 0),
            'distance'         => round((float) ($result->total_distance ?? 0), 1),
            'fuel'             => round((float) ($result->total_fuel ?? 0), 1),
            'avg_landing_rate' => round((float) ($result->avg_landing_rate ?? 0), 1),
            'accepted_pireps'  => (int) ($result->accepted_pireps ?? 0),
        ];
    }

    private function getCockpit(array $range, $user): array
    {
        if (!$user) {
            return array_fill_keys(['flights','block_time','distance','fuel','avg_landing_rate','accepted_pireps'], 0);
        }

        $result = DB::table('pireps')
            ->where('user_id', $user->id)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->selectRaw('
                COUNT(*) as flights,
                COALESCE(SUM(flight_time), 0) as block_time,
                COALESCE(SUM(distance), 0) as total_distance,
                COALESCE(SUM(fuel_used), 0) as total_fuel,
                COALESCE(AVG(landing_rate), 0) as avg_landing_rate
            ')->first();

        return [
            'flights'          => (int) ($result->flights ?? 0),
            'block_time'       => (int) ($result->block_time ?? 0),
            'distance'         => round((float) ($result->total_distance ?? 0), 1),
            'fuel'             => round((float) ($result->total_fuel ?? 0), 1),
            'avg_landing_rate' => round((float) ($result->avg_landing_rate ?? 0), 1),
            'accepted_pireps'  => (int) ($result->flights ?? 0),
        ];
    }

    private function getFavourites(array $range, $user): array
    {
        if (!$user) {
            return ['aircraft' => null, 'airline' => null];
        }

        $q = DB::table('pireps')
            ->where('user_id', $user->id)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$range['start'], $range['end']]);

        $favAcRow = (clone $q)->selectRaw('aircraft_id, COUNT(*) as c')
            ->groupBy('aircraft_id')->orderByDesc('c')->limit(1)->first();
        $favAirRow = (clone $q)->selectRaw('airline_id, COUNT(*) as c')
            ->groupBy('airline_id')->orderByDesc('c')->limit(1)->first();

        $favAc = $favAcRow ? Aircraft::with(['subfleet', 'subfleet.airline'])->find($favAcRow->aircraft_id) : null;
        $favAir = $favAirRow ? $this->allAirlines->get($favAirRow->airline_id) : null;

        return ['aircraft' => $favAc, 'airline' => $favAir];
    }

    private function getMissions($user): array
    {
        if (!$user) return [];

        $userId = $user->id;
        $q = DB::table('pireps')->where('user_id', $userId)->where('state', PirepState::ACCEPTED);

        // Single aggregate query for all lifetime stats
        // Softest landing: smallest ABS value above min_landing_rate threshold
        $minLdg = $this->minLdg;
        $lt = (clone $q)->selectRaw("
            COUNT(*) as flights,
            MIN(CASE WHEN landing_rate != 0 AND ABS(landing_rate) >= ? THEN ABS(landing_rate) ELSE NULL END) as best_ldg,
            MAX(distance) as max_dist,
            COUNT(DISTINCT aircraft_id) as ac_types,
            COUNT(DISTINCT airline_id) as airlines_flown,
            SUM(CASE WHEN DAYOFWEEK(submitted_at) IN (1,7) THEN 1 ELSE 0 END) as weekend
        ", [$minLdg])->first();

        $totalFlights = $lt ? (int) $lt->flights : 0;

        // Streak
        $flightDates = (clone $q)
            ->where('submitted_at', '>=', now()->subDays(365)->startOfDay())
            ->selectRaw('DATE(submitted_at) as fd')
            ->distinct()->pluck('fd')
            ->map(fn($d) => substr((string) $d, 0, 10))
            ->unique()->sort()->values()->toArray();
        $streak = PulseHelper::calculateStreak($flightDates);

        // Milestone
        $milestones = config('airlineinfopulse.milestones', [10,25,50,100,150,200,300,500,750,1000,1500,2000,5000]);
        $nextMilestone = collect($milestones)->first(fn($m) => $m > $totalFlights) ?? end($milestones);
        $milestoneDiff = max(0, $nextMilestone - $totalFlights);

        // Ranking — effizient: nur zählen wer mehr Flüge hat (kein volles Ranking laden)
        $totalPilots = DB::table('pireps')->where('state', PirepState::ACCEPTED)
            ->distinct('user_id')->count('user_id');
        $userRank = DB::table('pireps')->where('state', PirepState::ACCEPTED)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > ?', [$totalFlights])
            ->count() + 1;

        // Airports
        $dpt = (clone $q)->distinct()->pluck('dpt_airport_id');
        $arr = (clone $q)->distinct()->pluck('arr_airport_id');
        $airports = $dpt->merge($arr)->unique()->count();

        // Longest flight with route
        $lfRow = (clone $q)->selectRaw('distance as d, dpt_airport_id as dep, arr_airport_id as arr')
            ->whereNotNull('distance')->orderByDesc('distance')->limit(1)->first();
        $longestDist = $lfRow ? (float) $lfRow->d : 0;
        $longestRoute = $lfRow ? (($lfRow->dep ?? '?') . ' → ' . ($lfRow->arr ?? '?')) : null;

        // Today
        $todayFlights = (clone $q)->whereDate('submitted_at', today())->count();
        $dailyGoal = config('airlineinfopulse.daily_challenge_flights', 3);

        $weekendFlights = $lt ? (int) $lt->weekend : 0;
        $weekendPct = $totalFlights > 0 ? round(($weekendFlights / $totalFlights) * 100, 1) : 0;

        return [
            'streak'          => $streak,
            'total_flights'   => $totalFlights,
            'next_milestone'  => $nextMilestone,
            'milestone_diff'  => $milestoneDiff,
            'rank'            => $userRank,
            'total_pilots'    => $totalPilots,
            'airports'        => $airports,
            'aircraft_types'  => $lt ? (int) $lt->ac_types : 0,
            'best_landing'    => $lt ? (float) ($lt->best_ldg ?? 0) : 0,
            'today_flights'   => $todayFlights,
            'daily_goal'      => $dailyGoal,
            'longest_flight'  => round($longestDist, 1),
            'longest_route'   => $longestRoute,
            'airlines_flown'  => $lt ? (int) $lt->airlines_flown : 0,
            'total_airlines'  => $this->allAirlines->count(),
            'weekend_flights' => $weekendFlights,
            'weekend_pct'     => $weekendPct,
        ];
    }

    private function getComparePilotList($user): array
    {
        if (!$user) return [];

        $rows = DB::table('pireps')->where('state', PirepState::ACCEPTED)
            ->selectRaw('user_id, COUNT(*) as flights')
            ->groupBy('user_id')->orderByDesc('flights')->limit(50)->get();

        $userIds = $rows->pluck('user_id')->all();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        return $rows->filter(fn($r) => $r->user_id !== $user->id)
            ->map(function ($r) use ($users) {
                $u = $users->get($r->user_id);
                if (!$u) return null;
                $name = PulseHelper::shortName($u->name ?? null);
                $ident = $u->ident ?? ($u->pilot_id ?? null);
                if ($ident && $ident !== ($u->name ?? '')) $name .= " ($ident)";
                return ['id' => $r->user_id, 'name' => $name, 'flights' => $r->flights];
            })->filter()->values()->toArray();
    }

    private function getTopPilots(array $range, string $sort): array
    {
        $orderCol = match($sort) { 'time' => 'block_time', 'dist' => 'total_distance', default => 'flights' };

        $rows = DB::table('pireps')
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->selectRaw('
                user_id,
                COUNT(*) as flights,
                COALESCE(SUM(flight_time), 0) as block_time,
                COALESCE(SUM(distance), 0) as total_distance,
                COALESCE(AVG(landing_rate), 0) as avg_landing_rate
            ')
            ->groupBy('user_id')
            ->orderByDesc($orderCol)
            ->limit(12)
            ->get();

        $userIds = $rows->pluck('user_id')->toArray();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        // Pilot extras — batched
        $extras = $this->getPilotExtras($userIds, $range);

        $pilots = $rows->map(function ($row, $index) use ($users) {
            return [
                'rank'             => $index + 1,
                'user_id'          => $row->user_id,
                'user'             => $users->get($row->user_id),
                'flights'          => (int) $row->flights,
                'block_time'       => (int) $row->block_time,
                'distance'         => round((float) $row->total_distance, 1),
                'avg_landing_rate' => round((float) $row->avg_landing_rate, 1),
            ];
        })->toArray();

        return [$pilots, $extras];
    }

    private function getPilotExtras(array $userIds, array $range): array
    {
        if (empty($userIds)) return [];

        $extras = [];
        $from = $range['start'];
        $to = $range['end'];

        // Batch: Top routes per pilot
        $allRoutes = DB::table('pireps')
            ->whereIn('user_id', $userIds)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$from, $to])
            ->selectRaw("user_id, CONCAT(dpt_airport_id, ' → ', arr_airport_id) as route, COUNT(*) as cnt")
            ->groupBy('user_id', 'route')
            ->orderByDesc('cnt')
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => $g->take(3));

        // Batch: Recent flights per pilot
        $recentAll = DB::table('pireps')
            ->whereIn('user_id', $userIds)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$from, $to])
            ->select('id', 'user_id', 'submitted_at', 'dpt_airport_id as dep', 'arr_airport_id as arr', 'flight_time as blk', 'landing_rate')
            ->orderByDesc('submitted_at')
            ->limit(60)
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => $g->take(5));

        // Batch: Last flight timestamp
        $lastTs = DB::table('pireps')
            ->whereIn('user_id', $userIds)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$from, $to])
            ->selectRaw('user_id, MAX(submitted_at) as last_ts')
            ->groupBy('user_id')
            ->get()
            ->pluck('last_ts', 'user_id');

        foreach ($userIds as $uid) {
            $extras[$uid] = [
                'top_routes' => $allRoutes->get($uid, collect()),
                'recent'     => $recentAll->get($uid, collect()),
                'last_ts'    => isset($lastTs[$uid]) ? Carbon::parse($lastTs[$uid]) : null,
            ];
        }

        return $extras;
    }

    private function getTopAircraft(array $range, string $sort): array
    {
        $orderCol = $sort === 'flights' ? 'flights' : 'block_time';

        $rows = DB::table('pireps')
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->selectRaw('
                aircraft_id,
                COUNT(*) as flights,
                COALESCE(SUM(flight_time), 0) as block_time,
                COALESCE(SUM(distance), 0) as total_distance,
                COALESCE(SUM(fuel_used), 0) as total_fuel,
                COALESCE(AVG(landing_rate), 0) as avg_landing_rate
            ')
            ->groupBy('aircraft_id')
            ->orderByDesc($orderCol)
            ->limit(12)
            ->get();

        $acIds = $rows->pluck('aircraft_id')->toArray();
        $acMap = Aircraft::with(['subfleet', 'subfleet.airline'])->whereIn('id', $acIds)->get()->keyBy('id');

        $extras = $this->getAircraftExtras($acIds, $range);

        $aircraft = $rows->map(function ($row) use ($acMap) {
            $totalFuel = (float) $row->total_fuel;
            $totalDist = (float) $row->total_distance;
            $blockMin  = (int) $row->block_time;
            $fuelPerNm = $totalDist > 0 ? round($totalFuel / $totalDist, 2) : 0;
            $fuelPerHr = $blockMin > 0 ? round(($totalFuel / $blockMin) * 60, 1) : 0;

            return [
                'aircraft_id'      => $row->aircraft_id,
                'aircraft'         => $acMap->get($row->aircraft_id),
                'flights'          => (int) $row->flights,
                'block_time'       => $blockMin,
                'total_fuel'       => round($totalFuel, 1),
                'total_distance'   => round($totalDist, 1),
                'fuel_per_nm'      => $fuelPerNm,
                'fuel_per_hour'    => $fuelPerHr,
                'co2'              => round($totalFuel * $this->co2Factor, 1),
                'avg_landing_rate' => round((float) $row->avg_landing_rate, 1),
            ];
        })->toArray();

        return [$aircraft, $extras];
    }

    private function getAircraftExtras(array $acIds, array $range): array
    {
        if (empty($acIds)) return [];

        $extras = [];
        $from = $range['start'];
        $to = $range['end'];

        // Batch: Top routes
        $allRoutes = DB::table('pireps')
            ->whereIn('aircraft_id', $acIds)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$from, $to])
            ->selectRaw("aircraft_id, CONCAT(dpt_airport_id, ' → ', arr_airport_id) as route, COUNT(*) as cnt")
            ->groupBy('aircraft_id', 'route')
            ->orderByDesc('cnt')
            ->get()
            ->groupBy('aircraft_id')
            ->map(fn($g) => $g->take(3));

        // Batch: Recent flights
        $recentAll = DB::table('pireps')
            ->whereIn('aircraft_id', $acIds)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$from, $to])
            ->select('id', 'aircraft_id', 'user_id', 'submitted_at', 'dpt_airport_id as dep', 'arr_airport_id as arr', 'flight_time as blk', 'landing_rate')
            ->orderByDesc('submitted_at')
            ->limit(60)
            ->get();

        // Load users for recent flights
        $recentUserIds = $recentAll->pluck('user_id')->filter()->unique()->toArray();
        $recentUsers = !empty($recentUserIds) ? User::whereIn('id', $recentUserIds)->get()->keyBy('id') : collect();

        $recentByAc = $recentAll->groupBy('aircraft_id')->map(fn($g) => $g->take(5));

        // Batch: Last ts
        $lastTs = DB::table('pireps')
            ->whereIn('aircraft_id', $acIds)
            ->where('state', PirepState::ACCEPTED)
            ->whereBetween('submitted_at', [$from, $to])
            ->selectRaw('aircraft_id, MAX(submitted_at) as last_ts')
            ->groupBy('aircraft_id')
            ->get()
            ->pluck('last_ts', 'aircraft_id');

        foreach ($acIds as $aid) {
            $extras[$aid] = [
                'top_routes'   => $allRoutes->get($aid, collect()),
                'recent'       => $recentByAc->get($aid, collect()),
                'recent_users' => $recentUsers,
                'last_ts'      => isset($lastTs[$aid]) ? Carbon::parse($lastTs[$aid]) : null,
            ];
        }

        return $extras;
    }

    private function getQuickstart(): array
    {
        if (!$this->schemaHasTable('flights')) return [];

        // JOIN airlines dynamisch — nur vorhandene Spalten selecten
        $q = DB::table('flights')
            ->leftJoin('airlines', 'flights.airline_id', '=', 'airlines.id')
            ->select(array_merge(
                ['flights.id', 'flights.dpt_airport_id as dep', 'flights.arr_airport_id as arr', 'flights.airline_id'],
                $this->airlineSelectCols()
            ));

        if ($this->schemaHasColumn('flights', 'active')) {
            $q->where('flights.active', 1);
        }
        if ($this->schemaHasColumn('flights', 'flight_number')) {
            $q->addSelect('flights.flight_number');
        }
        if ($this->schemaHasColumn('flights', 'distance')) {
            $q->addSelect('flights.distance');
        }
        if ($this->schemaHasColumn('flights', 'flight_time')) {
            $q->addSelect('flights.flight_time');
        }

        $rows = $q->inRandomOrder()->limit(200)->get();

        // Airport names
        $aptNames = [];
        if ($this->schemaHasTable('airports')) {
            $allIcaos = $rows->pluck('dep')->merge($rows->pluck('arr'))->filter()->unique()->toArray();
            if (!empty($allIcaos)) {
                $aptRows = DB::table('airports')->whereIn('id', $allIcaos)->select('id as icao', 'name')->get();
                foreach ($aptRows as $apt) $aptNames[$apt->icao] = $apt->name;
            }
        }

        // Subfleets pro Flight — Pivot-Tabelle flight_subfleet → subfleets
        $sfMap = []; // flight_id => ['A320', 'B738', ...]
        $sfPivot = $this->schemaHasTable('flight_subfleet') ? 'flight_subfleet' : null;
        if (!$sfPivot) $sfPivot = $this->schemaHasTable('flight_sub_fleet') ? 'flight_sub_fleet' : null;
        if ($sfPivot && $this->schemaHasTable('subfleets')) {
            $flightIds = $rows->pluck('id')->toArray();
            if (!empty($flightIds)) {
                // Spaltenname: flight_id oder flights_id
                $fkCol = $this->schemaHasColumn($sfPivot, 'flight_id') ? 'flight_id' : 'flights_id';
                $sfkCol = $this->schemaHasColumn($sfPivot, 'subfleet_id') ? 'subfleet_id' : 'subfleets_id';

                // Typ-Spalte in subfleets: type, icao oder name
                $typeCol = 'name';
                foreach (['type', 'icao'] as $tc) {
                    if ($this->schemaHasColumn('subfleets', $tc)) { $typeCol = $tc; break; }
                }

                $sfRows = DB::table($sfPivot)
                    ->join('subfleets', $sfPivot.'.'.$sfkCol, '=', 'subfleets.id')
                    ->whereIn($sfPivot.'.'.$fkCol, $flightIds)
                    ->select($sfPivot.'.'.$fkCol.' as fid', 'subfleets.'.$typeCol.' as stype')
                    ->get();

                foreach ($sfRows as $sr) {
                    $sfMap[$sr->fid][] = $sr->stype;
                }
            }
        }

        $icaoRegion = function ($icao) {
            $icao = strtoupper((string) $icao);
            if (!$icao) return 'other';
            if (str_starts_with($icao, 'K')) return 'us';
            if (str_starts_with($icao, 'E') || str_starts_with($icao, 'L') || str_starts_with($icao, 'U')) return 'eu';
            if (str_starts_with($icao, 'Z') || str_starts_with($icao, 'V') || str_starts_with($icao, 'R') || str_starts_with($icao, 'W') || str_starts_with($icao, 'O')) return 'asia';
            return 'other';
        };

        $fmtMin = fn($m) => $m ? (intdiv((int)$m, 60) . ':' . str_pad((int)$m % 60, 2, '0', STR_PAD_LEFT) . ' h') : null;

        return $rows->map(function ($f) use ($aptNames, $icaoRegion, $fmtMin, $sfMap) {
            $airIcao = strtoupper(trim($this->airVal($f, 'icao')));
            $logo = null;
            $airLogo = $this->airVal($f, 'logo', 'air', null);
            if (!empty($airLogo)) {
                $logo = str_starts_with($airLogo, 'http') ? $airLogo : url($airLogo);
            }

            // Subfleets für diesen Flug (max 3 anzeigen)
            $subfleets = array_slice(array_unique($sfMap[$f->id] ?? []), 0, 3);

            return [
                'id'       => $f->id,
                'dep'      => e($f->dep),
                'arr'      => e($f->arr),
                'depName'  => e($aptNames[$f->dep] ?? ''),
                'arrName'  => e($aptNames[$f->arr] ?? ''),
                'region_d' => $icaoRegion($f->dep),
                'region_a' => $icaoRegion($f->arr),
                'fltNr'    => isset($f->flight_number) ? e(($airIcao ? $airIcao . ' ' : '') . $f->flight_number) : null,
                'airline'  => e($this->airVal($f, 'name')),
                'airIcao'  => e($airIcao),
                'logo'     => $logo,
                'time'     => isset($f->flight_time) ? $fmtMin((int) $f->flight_time) : null,
                'dist'     => isset($f->distance) ? number_format((float)$f->distance, 0, '', ' ') . ' NM' : null,
                'url'      => url('/flights/' . $f->id),
                'subfleets' => array_map('e', $subfleets),
            ];
        })->values()->toArray();
    }

    private function getFeed(array $range, string $filter = 'today'): array
    {
        // Dynamische Limits je nach Zeitraum
        $baseLimits = [
            'yesterday' => 30, 'week' => 40, 'month' => 60,
            'quarter' => 100, 'year' => 150, 'custom' => 80,
        ];
        $limit = $baseLimits[$filter] ?? 40;
        $feed = collect();

        // PIREPs — JOIN airlines dynamisch
        $pireps = DB::table('pireps')
            ->leftJoin('airlines', 'pireps.airline_id', '=', 'airlines.id')
            ->whereBetween('pireps.submitted_at', [$range['start'], $range['end']])
            ->orderByDesc('pireps.submitted_at')
            ->limit($limit)
            ->select(array_merge(
                ['pireps.id', 'pireps.user_id', 'pireps.airline_id', 'pireps.aircraft_id',
                 'pireps.submitted_at', 'pireps.dpt_airport_id as dpt', 'pireps.arr_airport_id as arr',
                 'pireps.flight_time as blk', 'pireps.landing_rate', 'pireps.state', 'pireps.flight_number'],
                $this->airlineSelectCols()
            ))
            ->get();

        $feedUserIds = $pireps->pluck('user_id')->filter()->unique()->toArray();
        $feedAcIds   = $pireps->pluck('aircraft_id')->filter()->unique()->toArray();

        $feedUsers = !empty($feedUserIds) ? User::whereIn('id', $feedUserIds)->get()->keyBy('id') : collect();
        $feedAcMap = !empty($feedAcIds) ? Aircraft::with('subfleet')->whereIn('id', $feedAcIds)->get()->keyBy('id') : collect();

        foreach ($pireps as $p) {
            $u = $feedUsers->get($p->user_id);
            $ac = $feedAcMap->get($p->aircraft_id);
            $airIcao = strtoupper(trim($this->airVal($p, 'icao')));

            $feed->push([
                'ts'   => Carbon::parse($p->submitted_at),
                'type' => 'pirep',
                'data' => [
                    'id'            => $p->id,
                    'pilot_name'    => PulseHelper::shortName($u->name ?? null),
                    'pilot_id'      => $p->user_id,
                    'airline_name'  => $this->airVal($p, 'name'),
                    'airline_icao'  => $airIcao,
                    'dpt'           => $p->dpt,
                    'arr'           => $p->arr,
                    'flight_number' => $p->flight_number ? (($airIcao ? $airIcao . ' ' : '') . $p->flight_number) : null,
                    'aircraft_type' => $ac ? ($ac->icao ?? ($ac->subfleet->type ?? '')) : '',
                    'aircraft_reg'  => $ac->registration ?? '',
                    'flight_time'   => (int) ($p->blk ?? 0),
                    'landing_rate'  => $p->landing_rate ? (int) $p->landing_rate : null,
                    'state'         => (int) ($p->state ?? 0),
                ],
            ]);
        }

        // New users in timeframe
        $newUsers = User::whereBetween('created_at', [$range['start'], $range['end']])
            ->orderByDesc('created_at')->limit(10)->get();
        foreach ($newUsers as $u) {
            $feed->push([
                'ts'   => Carbon::parse($u->created_at),
                'type' => 'user',
                'data' => ['id' => $u->id, 'name' => PulseHelper::shortName($u->name ?? null)],
            ]);
        }

        // Maintenance — DisposableBasic: Echte Check-Events via last_note
        // updated_at = wann Record geändert wurde, last_note = Check-Typ (z.B. "Hard Landing Check")
        $mxTbl = null;
        foreach (['disposable_maintenance', 'disposable_maintenances'] as $candidate) {
            if ($this->schemaHasTable($candidate)) { $mxTbl = $candidate; break; }
        }

        if ($mxTbl && $this->schemaHasColumn($mxTbl, 'aircraft_id') && $this->schemaHasColumn($mxTbl, 'last_note')) {
            $mxSelect = [
                $mxTbl.'.id',
                $mxTbl.'.aircraft_id',
                $mxTbl.'.last_note',
            ];

            // Timestamp: last_time bevorzugen (Check-Zeitpunkt), Fallback updated_at
            $tsCol = $this->schemaHasColumn($mxTbl, 'last_time') ? 'last_time' : 'updated_at';
            $mxSelect[] = $mxTbl.'.'.$tsCol.' as mx_ts';
            // Auch updated_at laden falls last_time leer ist
            if ($tsCol === 'last_time' && $this->schemaHasColumn($mxTbl, 'updated_at')) {
                $mxSelect[] = $mxTbl.'.updated_at as mx_ts_fallback';
            }

            // JOIN aircraft + subfleets
            if ($this->schemaHasTable('aircraft')) {
                $mxSelect[] = 'aircraft.registration as ac_reg';
                if ($this->schemaHasColumn('aircraft', 'icao')) $mxSelect[] = 'aircraft.icao as ac_icao';
            }
            if ($this->schemaHasTable('subfleets') && $this->schemaHasColumn('aircraft', 'subfleet_id')) {
                $sfTypeCol = $this->schemaHasColumn('subfleets', 'type') ? 'type' : 'name';
                $mxSelect[] = 'subfleets.'.$sfTypeCol.' as sf_type';
            }

            // Filtern: last_note nicht leer UND Timestamp im Zeitraum
            // Wichtig: Filter muss dieselbe Spalte nutzen wie die Anzeige ($tsCol),
            // sonst erscheinen alte Checks im Feed wenn DisposableSpecial updated_at ändert
            $filterCol = $mxTbl.'.'.$tsCol;
            $mxQ = DB::table($mxTbl)
                ->whereNotNull($mxTbl.'.last_note')
                ->where($mxTbl.'.last_note', '!=', '')
                ->whereBetween($filterCol, [$range['start'], $range['end']])
                ->orderByDesc($filterCol)
                ->limit($limit);

            if ($this->schemaHasTable('aircraft')) {
                $mxQ->leftJoin('aircraft', $mxTbl.'.aircraft_id', '=', 'aircraft.id');
                if ($this->schemaHasTable('subfleets') && $this->schemaHasColumn('aircraft', 'subfleet_id')) {
                    $mxQ->leftJoin('subfleets', 'aircraft.subfleet_id', '=', 'subfleets.id');
                }
            }

            $mxRows = $mxQ->select($mxSelect)->get();

            foreach ($mxRows as $mx) {
                $acType = $mx->ac_icao ?? ($mx->sf_type ?? '');
                $checkType = trim($mx->last_note);
                $ts = !empty($mx->mx_ts) ? $mx->mx_ts : ($mx->mx_ts_fallback ?? null);
                if (!$ts) continue;

                $feed->push([
                    'ts'   => Carbon::parse($ts),
                    'type' => 'maintenance',
                    'data' => [
                        'id'         => $mx->id,
                        'ac_reg'     => $mx->ac_reg ?? '',
                        'ac_icao'    => (string) $acType,
                        'note'       => '',
                        'status'     => '',
                        'mx_type'    => $checkType,
                        'state_pct'  => null,
                        'pilot_name' => '',
                        'pilot_id'   => 0,
                    ],
                ]);
            }
        }

        return $feed->sortByDesc('ts')->values()->toArray();
    }

    private function getAirlineSnapshot(array $range): array
    {
        $p = $this->t('pireps');     // z.B. 'phpvmspireps'
        $a = $this->t('airlines');   // z.B. 'phpvmsairlines'
        $airSelectRaw = $this->airlineSelectRaw();
        $airGroupCols = $this->airlineGroupCols();  // unprefixed für groupBy()

        $rows = DB::table('pireps')
            ->join('airlines', 'pireps.airline_id', '=', 'airlines.id')
            ->where('pireps.state', PirepState::ACCEPTED)
            ->whereNotNull('pireps.airline_id')
            ->whereBetween('pireps.submitted_at', [$range['start'], $range['end']])
            ->selectRaw(
                "{$p}.airline_id{$airSelectRaw}, " .
                "COUNT(*) as flights, " .
                "COALESCE(SUM({$p}.flight_time), 0) as block_time, " .
                "COALESCE(SUM({$p}.distance), 0) as total_distance, " .
                "COALESCE(AVG({$p}.landing_rate), 0) as avg_landing_rate, " .
                "COUNT(DISTINCT {$p}.user_id) as pilots"
            )
            ->groupBy(array_merge(['pireps.airline_id'], $airGroupCols))
            ->orderByDesc('flights')
            ->get();

        return $rows->map(function ($row) {
            $airline = $this->allAirlines->get($row->airline_id);
            if (!$airline) {
                $fallback = ['id' => $row->airline_id];
                foreach ($this->airCols as $col) {
                    $key = "air_{$col}";
                    $fallback[$col] = $row->$key ?? '';
                }
                $airline = (object) $fallback;
            }

            return [
                'airline_id'       => $row->airline_id,
                'airline'          => $airline,
                'flights'          => (int) $row->flights,
                'block_time'       => (int) $row->block_time,
                'distance'         => round((float) $row->total_distance, 1),
                'avg_landing_rate' => round((float) $row->avg_landing_rate, 1),
                'pilots'           => (int) $row->pilots,
            ];
        })->toArray();
    }

    private function buildDeltas(array $current, array $previous): array
    {
        $deltas = [];
        foreach ($current as $key => $value) {
            $prev = $previous[$key] ?? 0;
            $deltas[$key] = PulseHelper::calculateDelta($value, $prev);
        }
        return $deltas;
    }
}
