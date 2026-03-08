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
     * Daily flight hour goal in minutes (default: 4 hours = 240 minutes)
     * Used for the cockpit progress bar. Multiplied by the number of days
     * in the selected period.
     */
    'daily_goal_minutes' => 240,

    /**
     * Milestone levels for the "Next Milestone" mission
     */
    'milestones' => [10, 25, 50, 100, 150, 200, 300, 500, 750, 1000, 1500, 2000, 5000],

    /**
     * Daily Challenge: number of flights to complete per day
     */
    'daily_challenge_flights' => 3,

    /**
     * Quick Start: maximum number of random routes loaded
     */
    'quickstart_max_routes' => 200,

    /**
     * CO2 conversion factor (kg fuel → kg CO2)
     */
    'co2_factor' => 3.16,

    /**
     * Landing rate color thresholds (fpm, negative values)
     */
    'landing_rate_thresholds' => [
        'green'  => -299,   // 0 to -299 = green (butter landing)
        'orange' => -499,   // -300 to -499 = orange (acceptable)
        // everything below = red (hard landing)
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
     * Number of entries in Top Pilots / Top Aircraft lists
     */
    'top_limit' => 10,

    /**
     * Number of entries in the Activity Feed
     */
    'feed_limit' => 25,

    /**
     * Number of large Airline Snapshot cards
     */
    'snapshot_top_count' => 4,

    /**
     * ─── DESIGN MODE ───────────────────────────────────────────────
     *
     * true  = Glass (default). Cards have a subtle blur effect.
     *         Works with all standard phpVMS themes.
     *
     * false = Solid. Cards get opaque backgrounds (colors below).
     *         Use this if your theme has a background image that
     *         shines through the dashboard cards.
     */
    'glass_mode' => true,

    // Solid mode card colors (dark theme) — only used when glass_mode = false
    'solid_card'   => '#1a1f2e',
    'solid_border' => '#2a3040',
    'solid_select' => '#1e2535',
    'solid_kpi'    => '#171c28',
    'solid_accent' => '#3b82f6',   // Active button / highlight color

    // Solid mode card colors (light theme) — only used when glass_mode = false
    'solid_card_light'   => '#ffffff',
    'solid_border_light' => '#e2e8f0',
    'solid_select_light' => '#f1f5f9',
    'solid_kpi_light'    => '#f8fafc',
    'solid_accent_light' => '#3b82f6',
];
