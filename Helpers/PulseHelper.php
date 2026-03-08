<?php

namespace Modules\AirlineInfoPulse\Helpers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PulseHelper
{
    /**
     * Detect unit settings from phpVMS Admin → Settings.
     * phpVMS 7 stores distance in NM and fuel/weight in lbs internally.
     * Returns display units + conversion factors.
     */
    public static function getUnits(): array
    {
        // Try reading phpVMS admin settings (Settings table)
        $distSetting = null;
        $fuelSetting = null;
        $weightSetting = null;

        if (function_exists('setting')) {
            try {
                $distSetting   = setting('units.distance');   // e.g. 'nmi', 'km', 'mi'
                $fuelSetting   = setting('units.fuel');        // e.g. 'kg', 'lbs'
                $weightSetting = setting('units.weight');      // e.g. 'kg', 'lbs'
            } catch (\Throwable $e) {
                // Setting not available — fall through to config
            }
        }

        // Module config override / fallback
        $distUnit   = $distSetting   ?: config('airlineinfopulse.distance_unit', 'nmi');
        $fuelUnit   = $fuelSetting   ?: config('airlineinfopulse.fuel_unit', 'kg');
        $weightUnit = $weightSetting ?: config('airlineinfopulse.weight_unit', 'kg');

        // Normalize common variants
        $distUnit   = self::normalizeDistanceUnit($distUnit);
        $fuelUnit   = self::normalizeWeightUnit($fuelUnit);
        $weightUnit = self::normalizeWeightUnit($weightUnit);

        // phpVMS 7 DB stores: distance = NM, fuel_used = lbs
        // Calculate conversion factors from internal → display unit
        $distFactor = match ($distUnit) {
            'km' => 1.852,       // NM → km
            'mi' => 1.15078,     // NM → statute miles
            default => 1.0,      // NM → NM (no conversion)
        };

        $fuelFactor = match ($fuelUnit) {
            'kg'  => 0.453592,   // lbs → kg
            default => 1.0,      // lbs → lbs (no conversion)
        };

        $weightFactor = match ($weightUnit) {
            'kg'  => 0.453592,   // lbs → kg
            default => 1.0,      // lbs → lbs (no conversion)
        };

        // Labels for display
        $distLabel = match ($distUnit) {
            'km' => 'km',
            'mi' => 'mi',
            default => 'NM',
        };

        $fuelLabel = match ($fuelUnit) {
            'lbs' => 'lbs',
            default => 'kg',
        };

        $weightLabel = match ($weightUnit) {
            'lbs' => 'lbs',
            default => 'kg',
        };

        // Fuel efficiency label: fuel_unit / distance_unit
        $efficiencyLabel = $fuelLabel . '/' . $distLabel;

        return [
            'distance_unit'    => $distUnit,
            'distance_label'   => $distLabel,
            'distance_factor'  => $distFactor,
            'fuel_unit'        => $fuelUnit,
            'fuel_label'       => $fuelLabel,
            'fuel_factor'      => $fuelFactor,
            'weight_unit'      => $weightUnit,
            'weight_label'     => $weightLabel,
            'weight_factor'    => $weightFactor,
            'efficiency_label' => $efficiencyLabel,
        ];
    }

    /**
     * Convert a distance value from DB (NM) to display unit
     */
    public static function convertDistance(float $valueNm, array $units): float
    {
        return $valueNm * $units['distance_factor'];
    }

    /**
     * Convert a fuel/weight value from DB (lbs) to display unit
     */
    public static function convertFuel(float $valueLbs, array $units): float
    {
        return $valueLbs * $units['fuel_factor'];
    }

    /**
     * Convert fuel efficiency (fuel_per_nm in lbs/NM) to display units
     */
    public static function convertEfficiency(float $lbsPerNm, array $units): float
    {
        if ($lbsPerNm == 0) return 0;
        return ($lbsPerNm * $units['fuel_factor']) / $units['distance_factor'];
    }

    /**
     * Format distance using phpVMS native Unit class (if available).
     * Falls back to manual conversion if phpVMS classes are missing.
     *
     * @param float  $valueNm  Distance in nautical miles (DB internal unit)
     * @param int    $decimals Number of decimal places
     * @param array  $units    Units array from getUnits()
     * @return string Formatted string, e.g. "1.451 NM" or "2.687 km"
     */
    public static function formatDistance(float $valueNm, int $decimals, array $units): string
    {
        // Try phpVMS native Distance class — most reliable, respects admin settings
        if (class_exists('App\Support\Units\Distance')) {
            try {
                $dist = new \App\Support\Units\Distance($valueNm, 'nmi');
                $local = $dist->local($decimals);
                if ($local !== null && $local !== '') {
                    return (string) $local;
                }
            } catch (\Throwable $e) {
                // Fall through to manual
            }
        }

        // Fallback: manual conversion
        $converted = $valueNm * $units['distance_factor'];
        return number_format($converted, $decimals, ',', ' ') . ' ' . $units['distance_label'];
    }

    /**
     * Format fuel/weight using phpVMS native Unit class (if available).
     *
     * @param float  $valueLbs Fuel in pounds (DB internal unit)
     * @param int    $decimals Number of decimal places
     * @param array  $units    Units array from getUnits()
     * @return string Formatted string, e.g. "3.543 kg" or "7.813 lbs"
     */
    public static function formatFuel(float $valueLbs, int $decimals, array $units): string
    {
        if (class_exists('App\Support\Units\Fuel')) {
            try {
                $fuel = new \App\Support\Units\Fuel($valueLbs, 'lbs');
                $local = $fuel->local($decimals);
                if ($local !== null && $local !== '') {
                    return (string) $local;
                }
            } catch (\Throwable $e) {
                // Fall through
            }
        }

        $converted = $valueLbs * $units['fuel_factor'];
        return number_format($converted, $decimals, ',', ' ') . ' ' . $units['fuel_label'];
    }

    /**
     * Get only the numeric value of a distance in local units (for calculations in views).
     */
    public static function distanceValue(float $valueNm, array $units): float
    {
        if (class_exists('App\Support\Units\Distance')) {
            try {
                $dist = new \App\Support\Units\Distance($valueNm, 'nmi');
                // toUnit returns a float in the target unit
                $targetUnit = match ($units['distance_unit']) {
                    'km' => 'km', 'mi' => 'mi', default => 'nmi',
                };
                return round($dist->toUnit($targetUnit), 2);
            } catch (\Throwable $e) {}
        }
        return $valueNm * $units['distance_factor'];
    }

    /**
     * Get only the numeric value of fuel in local units (for calculations in views).
     */
    public static function fuelValue(float $valueLbs, array $units): float
    {
        if (class_exists('App\Support\Units\Fuel')) {
            try {
                $fuel = new \App\Support\Units\Fuel($valueLbs, 'lbs');
                $targetUnit = $units['fuel_unit'] === 'lbs' ? 'lbs' : 'kg';
                return round($fuel->toUnit($targetUnit), 2);
            } catch (\Throwable $e) {}
        }
        return $valueLbs * $units['fuel_factor'];
    }

    /**
     * Normalize distance unit string from phpVMS settings
     */
    private static function normalizeDistanceUnit(?string $unit): string
    {
        if (!$unit) return 'nmi';
        $u = strtolower(trim($unit));
        return match (true) {
            in_array($u, ['km', 'kilometer', 'kilometers'])         => 'km',
            in_array($u, ['mi', 'mile', 'miles', 'statute'])        => 'mi',
            default                                                  => 'nmi', // nm, nmi, nautical
        };
    }

    /**
     * Normalize weight/fuel unit string from phpVMS settings
     */
    private static function normalizeWeightUnit(?string $unit): string
    {
        if (!$unit) return 'kg';
        $u = strtolower(trim($unit));
        return match (true) {
            in_array($u, ['lbs', 'lb', 'pound', 'pounds'])  => 'lbs',
            default                                          => 'kg',  // kg, kilogram, kgs
        };
    }

    /**
     * Zeitraum-Definitionen basierend auf dem Filter
     */
    public static function getDateRange(string $filter, ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = Carbon::now();

        switch ($filter) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                break;

            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end   = $now->copy()->subDay()->endOfDay();
                break;

            case 'week':
                $start = $now->copy()->startOfWeek(Carbon::MONDAY);
                $end   = $now->copy()->endOfWeek(Carbon::SUNDAY);
                break;

            case 'month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                break;

            case 'quarter':
                $start = $now->copy()->firstOfQuarter();
                $end   = $now->copy()->lastOfQuarter()->endOfDay();
                break;

            case 'year':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
                break;

            case 'custom':
                try {
                    $start = $customStart ? Carbon::parse($customStart)->startOfDay() : $now->copy()->startOfMonth();
                    $end   = $customEnd ? Carbon::parse($customEnd)->endOfDay() : $now->copy()->endOfDay();
                    // Max 366 Tage Range erlauben (Schutz gegen Mega-Queries)
                    if ($start->diffInDays($end) > 366) {
                        $start = $end->copy()->subDays(366)->startOfDay();
                    }
                    // End darf nicht in ferner Zukunft liegen
                    if ($end->greaterThan($now->copy()->endOfYear())) {
                        $end = $now->copy()->endOfDay();
                    }
                } catch (\Throwable $e) {
                    $start = $now->copy()->startOfDay();
                    $end   = $now->copy()->endOfDay();
                }
                break;

            default:
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                break;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Vorperiode berechnen (gleich lange Zeitspanne davor)
     */
    public static function getPreviousPeriod(Carbon $start, Carbon $end): array
    {
        $diffDays = $start->diffInDays($end) + 1;

        return [
            'start' => $start->copy()->subDays($diffDays),
            'end'   => $start->copy()->subDay()->endOfDay(),
        ];
    }

    /**
     * Calculate delta badge (percentage change)
     */
    public static function calculateDelta($current, $previous): array
    {
        if ($previous == 0 && $current == 0) {
            return ['value' => 0, 'direction' => 'neutral', 'label' => '—'];
        }

        if ($previous == 0) {
            return ['value' => 100, 'direction' => 'up', 'label' => '↑ neu'];
        }

        $delta = (($current - $previous) / abs($previous)) * 100;

        return [
            'value'     => round(abs($delta), 1),
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
            'label'     => ($delta > 0 ? '↑' : '↓') . ' ' . round(abs($delta), 1) . '%',
        ];
    }

    /**
     * Landing Rate Farbe bestimmen
     */
    public static function landingRateColor(float $rate): string
    {
        $abs = abs($rate);

        if ($abs <= 299) {
            return 'success';  // green
        }
        if ($abs <= 499) {
            return 'warning';  // orange
        }

        return 'danger';  // rot
    }

    /**
     * Minuten in h:mm Format
     */
    public static function minutesToHours(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate flight streak (consecutive days with flights)
     */
    public static function calculateStreak(array $flightDates): int
    {
        if (empty($flightDates)) {
            return 0;
        }

        $dates = collect($flightDates)
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        $streak  = 0;
        $current = Carbon::today();

        // Count backwards from today
        while ($dates->contains($current->format('Y-m-d'))) {
            $streak++;
            $current->subDay();
        }

        return $streak;
    }

    /**
     * Filter label for display
     */
    public static function filterLabel(string $filter): string
    {
        return match ($filter) {
            'today'     => __('airlineinfopulse::pulse.today'),
            'yesterday' => __('airlineinfopulse::pulse.yesterday'),
            'week'      => __('airlineinfopulse::pulse.this_week'),
            'month'     => __('airlineinfopulse::pulse.this_month'),
            'quarter'   => __('airlineinfopulse::pulse.this_quarter'),
            'year'      => __('airlineinfopulse::pulse.this_year'),
            'custom'    => __('airlineinfopulse::pulse.custom'),
            default     => __('airlineinfopulse::pulse.today'),
        };
    }

    /**
     * GDPR: Shorten name → "First L."
     * "Dan Evans" → "Dan E."
     * "Thomas Kantt" → "Thomas K."
     * Single-word names remain unchanged.
     */
    public static function shortName(?string $fullName): string
    {
        if (!$fullName || !trim($fullName)) {
            return 'Pilot';
        }

        $parts = preg_split('/\s+/', trim($fullName));

        if (count($parts) <= 1) {
            return $parts[0];
        }

        $first = $parts[0];
        $lastInitial = mb_strtoupper(mb_substr(end($parts), 0, 1));

        return $first . ' ' . $lastInitial . '.';
    }
}
