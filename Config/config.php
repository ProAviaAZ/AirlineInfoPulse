<?php

return [
    /**
     * ─── UNIT SETTINGS ──────────────────────────────────────────────
     * The module auto-reads phpVMS Admin → Settings for units.
     * These overrides are ONLY used if phpVMS settings can't be read.
     *
     * distance_unit: 'nmi' (nautical miles), 'km', 'mi' (statute miles)
     * fuel_unit:     'kg', 'lbs'
     * weight_unit:   'kg', 'lbs'
     *
     * phpVMS 7 stores distance in NM and fuel in lbs internally.
     * The module auto-converts for display based on settings.
     */
    'distance_unit' => 'nmi',
    'fuel_unit'     => 'kg',
    'weight_unit'   => 'kg',

    /**
     * Tagesziel in Minuten (Standard: 4 Stunden = 240 Minuten)
     */
    'daily_goal_minutes' => 240,

    /**
     * Meilenstein-Stufen für die "Nächster Meilenstein" Mission
     */
    'milestones' => [10, 25, 50, 100, 150, 200, 300, 500, 750, 1000, 1500, 2000, 5000],

    /**
     * Tages-Challenge: Anzahl Flüge für die tägliche Challenge
     */
    'daily_challenge_flights' => 3,

    /**
     * Schnellstart: Maximale Anzahl zufälliger Routen
     */
    'quickstart_max_routes' => 200,

    /**
     * CO2-Umrechnungsfaktor (kg Fuel → kg CO2)
     */
    'co2_factor' => 3.16,

    /**
     * Landing Rate Schwellenwerte (fpm)
     */
    'landing_rate_thresholds' => [
        'green'  => -299,   // 0 bis -299 = grün (butterweich)
        'orange' => -499,   // -300 bis -499 = orange (ok)
        // alles darunter = rot (hart)
    ],

    /**
     * ─────────────────────────────────────────────────────────────────────
     * Minimum Landing Rate Threshold (fpm)
     * ─────────────────────────────────────────────────────────────────────
     *
     * This setting controls the "Softest Landing / Personal Record" mission.
     * It defines the minimum absolute landing rate (in fpm) that is
     * considered a valid, realistic landing.
     *
     * HOW IT WORKS:
     * The module stores all landing rates as reported by ACARS (positive
     * or negative values, depending on the ACARS client). To find the
     * "softest landing", the module compares:
     *
     *     ABS(landing_rate) >= min_landing_rate
     *
     * Any landing where the absolute value is BELOW this threshold is
     * treated as a data error or sensor glitch and excluded from the
     * "Personal Record" / "Softest Landing" calculation.
     *
     * EXAMPLES WITH DIFFERENT SETTINGS:
     * ┌──────────────────┬───────┬────────┬──────────┬───────────────────┐
     * │ Landing Rate (DB) │  ABS  │ >= 5 ? │ >= 75 ?  │ >= 150 ?          │
     * ├──────────────────┼───────┼────────┼──────────┼───────────────────┤
     * │      -3 fpm       │   3   │   No   │   No     │   No  (filtered)  │
     * │     -27 fpm       │  27   │  Yes   │   No     │   No  (filtered)  │
     * │     -85 fpm       │  85   │  Yes   │  Yes     │   No  (filtered)  │
     * │    -180 fpm       │ 180   │  Yes   │  Yes     │  Yes  (valid)     │
     * │    -536 fpm       │ 536   │  Yes   │  Yes     │  Yes  (valid)     │
     * └──────────────────┴───────┴────────┴──────────┴───────────────────┘
     *
     * RECOMMENDED VALUES:
     *   -   0  = No filter — accept ALL landing rates (not recommended)
     *   -   5  = Very lenient — only filters near-zero glitches (default)
     *   -  50  = Moderate — filters unrealistically soft values
     *   -  75  = Strict — a real landing rarely produces < 75 fpm
     *   - 150  = Very strict — only counts clearly felt touchdowns
     *
     * NOTE:
     * - You can enter positive OR negative values — the module always
     *   converts to a positive number internally (e.g. -75 becomes 75).
     * - This filter applies ONLY to the "Softest Landing" / "Personal
     *   Record" mission and the Pilot Duel comparison. It does NOT
     *   affect the average landing rate in KPIs or the Activity Feed.
     * - The value should reflect what your VA considers a realistic
     *   minimum touchdown rate. Very low values (< 10 fpm) are often
     *   caused by ACARS measurement errors or float landings where the
     *   sensor didn't trigger properly.
     *
     * AFFECTED SECTIONS:
     *   → Missions: "Personal Record" (softest landing of all time)
     *   → Pilot Duel: "Best Landing" comparison category
     */
    'min_landing_rate' => 5,

    /**
     * Anzahl der Top-Einträge (Piloten / Aircraft)
     */
    'top_limit' => 10,

    /**
     * Anzahl Feed-Einträge
     */
    'feed_limit' => 25,

    /**
     * Anzahl Airline Snapshot Top-Karten
     */
    'snapshot_top_count' => 4,
];
