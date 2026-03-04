# ✈️ Airline Info Pulse — phpVMS 7 Module

> Real-time statistics dashboard for virtual airlines — gamified missions, pilot duels, fleet analytics, and a live activity feed.

![phpVMS 7](https://img.shields.io/badge/phpVMS-7.x-blue?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple?style=flat-square)
![Laravel](https://img.shields.io/badge/Laravel-10%2F11-red?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![i18n](https://img.shields.io/badge/i18n-DE%20%7C%20EN-orange?style=flat-square)

---

## 📋 Table of Contents

- [Features](#-features)
- [Screenshots](#-screenshots)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Multi-Language Support](#-multi-language-support)
- [Data Visibility & Restrictions](#-data-visibility--restrictions)
- [Theme & Dark Mode](#-theme--dark-mode)
- [Optional Module Integrations](#-optional-module-integrations)
- [Security](#-security)
- [File Structure](#-file-structure)
- [Customization](#-customization)
- [Pilot Guide](#-pilot-guide)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [Changelog](#-changelog)
- [License](#-license)

---

## ✨ Features

### KPI Dashboard
- **Global KPI Strip** — Flights, block time, distance, fuel, average landing rate, and accepted PIREPs for all pilots in the selected time period
- **Trend Indicators** — Percentage change compared to previous period (↑ green / ↓ red)

### Personal Cockpit
- **My Stats** — Your personal KPIs with progress bar toward configurable monthly goal
- **Favourite Airline & Aircraft** — Automatically detected from your most-flown data

### Missions & Gamification
- **Flight Streak** — Consecutive days with at least one flight
- **Next Milestone** — Progress toward the next flight count milestone (10, 25, 50, 100, …)
- **Your Ranking** — Position among all pilots with podium detection
- **Airport Explorer** — Number of unique airports you've visited
- **Fleet Types** — How many different aircraft types you've flown
- **Personal Record** — Your softest landing ever (fpm)
- **Daily Challenge** — Configurable daily flight goal with progress bar
- **Longest Flight** — Your distance record with route display
- **Airlines Flown** — Collection progress across all available airlines
- **Weekend Pilot** — Weekend flight percentage tracker

### Pilot Duel
- **Head-to-Head Comparison** — Compare your stats against any other pilot via AJAX dropdown
- **Score Table** — Win/lose/tie per category with color-coded results

### Quick Start
- **Random Flight Suggestions** — Region-filtered (Europe, Asia, USA) random flights from the flight schedule
- **Subfleet Type Display** — Shows aircraft types available for each suggested route

### Top Lists
- **Top Pilots** — Sortable by flights, block time, or distance with expandable recent flights and top routes
- **Top Aircraft** — Sortable by flights or block time with fuel efficiency (kg/NM) and CO₂ emissions

### Activity Feed
- **PIREP Events** — Real-time feed of completed flights with route, aircraft, landing rate, and flight time
- **New Pilots** — Welcome notifications for newly registered pilots
- **Maintenance Events** — Aircraft checks from DisposableBasic (optional)

### Airline Snapshot
- **Airline Cards** — Top 4 airlines with trend indicators, share percentages, and detailed stats
- **Compact List** — Remaining airlines in a sortable compact row layout

---

## 📸 Screenshots

### Dark Mode
![Dashboard Dark](docs/screenshots/dashboard-dark.png)

### Light Mode
![Dashboard Light](docs/screenshots/dashboard-light.png)

### Missions & Gamification
![Missions](docs/screenshots/missions.png)

### Pilot Duel
![Duel](docs/screenshots/duel.png)

---

## 📦 Requirements

### Required

| Component | Version | Notes |
|-----------|---------|-------|
| **phpVMS** | 7.x (dev/stable) | Core framework |
| **PHP** | 8.1 or higher | Required by phpVMS 7 |
| **Laravel** | 10.x or 11.x | Bundled with phpVMS 7 |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ | Supports custom table prefixes |

### phpVMS Core Tables Used

The module reads from these standard phpVMS tables (read-only, no migrations needed):

| Table | Purpose |
|-------|---------|
| `pireps` | Flight reports (the core data source) |
| `users` | Pilot names, registration dates |
| `airlines` | Airline names, ICAO/IATA codes, logos |
| `aircraft` | Aircraft registrations, types |
| `subfleets` | Subfleet types linked to aircraft |
| `flights` | Flight schedule (for Quick Start) |
| `airports` | Airport names and ICAO codes |
| `flight_subfleet` | Pivot table linking flights to subfleets |

> **Important:** The module uses `DB::getTablePrefix()` to support custom table prefixes (e.g., `phpvms_pireps`). No hardcoded table names in raw SQL.

### Optional Modules

| Module | Effect if installed | Effect if missing |
|--------|-------------------|------------------|
| **[DisposableBasic](https://github.com/FatihKoz/DisposableBasic)** | Maintenance events appear in the Activity Feed (Hard Landing Check, A-Check, etc.) and FlightBoard / ActiveBookings widgets are displayed | Feed shows only PIREPs and new pilots, widget section is empty |
| **[DisposableTheme](https://github.com/FatihKoz/DisposableTheme)** | Theme dark/light mode is automatically detected via `data-bs-theme` attribute | Module falls back to `prefers-color-scheme` media query |

> The module **never crashes** if optional modules are missing. All external table access is wrapped in `Schema::hasTable()` / `Schema::hasColumn()` checks with per-request result caching.

---

## 🚀 Installation

### Method A — phpVMS Admin Panel (empfohlen)

Der einfachste Weg — kein FTP, kein SSH nötig:

1. **ZIP herunterladen** — Lade `AirlineInfoPulse.zip` von der [Releases](../../releases) Seite herunter
2. **Admin Panel öffnen** — Gehe zu `https://deine-domain.de/admin`
3. **Modul hochladen** — Navigiere zu **addons/modules** → klicke oben rechts **"Add New"**
4. **ZIP auswählen** — Klicke "Datei auswählen", wähle die `AirlineInfoPulse.zip` und klicke **"Add Module"**
5. **Cache leeren** — Gehe zu **maintenance** (in der linken Sidebar) und klicke **"Clear All Caches"**
6. **Fertig!** — Das Modul ist automatisch aktiv. Navigiere zu `/airline-info-pulse`

> **Wichtig:** Der ZIP-Name muss `AirlineInfoPulse.zip` sein und der Ordner im ZIP muss `AirlineInfoPulse/` heißen (ist beim Release-Download bereits korrekt).

---

### Method B — Manuell per FTP / SSH

Falls der Upload über das Admin Panel nicht funktioniert:

#### Per SSH / Terminal

```bash
# Repository klonen oder ZIP entpacken
git clone https://github.com/YOUR-USERNAME/AirlineInfoPulse.git

# In das phpVMS modules-Verzeichnis kopieren
cp -r AirlineInfoPulse/ /path/to/phpvms/modules/AirlineInfoPulse/
```

#### Per FTP / File Manager

1. ZIP entpacken auf dem lokalen PC
2. Den gesamten `AirlineInfoPulse/`-Ordner nach `modules/` auf dem Server hochladen
3. Ergebnis: `modules/AirlineInfoPulse/module.json` muss existieren

> ⚠️ Kein doppelter Ordner: `modules/AirlineInfoPulse/AirlineInfoPulse/` ist **falsch**.

#### Cache leeren

**Mit SSH:**
```bash
php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear
```

**Ohne SSH:** Admin Panel → **maintenance** → **"Clear All Caches"**

---

### Nach der Installation

#### Navigation Link hinzufügen

In deinem Theme-Layout (z.B. `resources/views/layouts/default.blade.php`):

```html
<a class="nav-link" href="{{ route('airlineinfopulse.index') }}">
    <i class="fas fa-chart-line"></i> Airline Pulse
</a>
```

Für **DisposableTheme** Sidebar:

```html
<li class="nav-item">
    <a class="nav-link" href="{{ route('airlineinfopulse.index') }}">
        <i class="fas fa-chart-line"></i>
        <span>Airline Pulse</span>
    </a>
</li>
```

#### Prüfen

Navigiere zu:

```
https://deine-domain.de/airline-info-pulse
```

---

## ⚙️ Configuration

All settings are in `Config/config.php`:

```php
return [
    // Monthly goal in minutes (default: 240 = 4 hours)
    'daily_goal_minutes' => 240,

    // Milestone flight counts for the "Next Milestone" mission
    'milestones' => [10, 25, 50, 100, 150, 200, 300, 500, 750, 1000, 1500, 2000, 5000],

    // Daily challenge: number of flights to complete per day
    'daily_challenge_flights' => 3,

    // Quick Start: maximum random routes loaded
    'quickstart_max_routes' => 200,

    // CO₂ conversion factor (kg fuel → kg CO₂)
    'co2_factor' => 3.16,

    // Landing rate color thresholds (fpm, negative values)
    'landing_rate_thresholds' => [
        'green'  => -299,   // 0 to -299 = green (butter)
        'orange' => -499,   // -300 to -499 = orange (acceptable)
        // everything below = red (hard)
    ],

    // Number of entries in Top Pilots / Top Aircraft lists
    'top_limit' => 10,

    // Number of entries in the Activity Feed
    'feed_limit' => 25,

    // Number of large Airline Snapshot cards
    'snapshot_top_count' => 4,
];
```

---

## 🌐 Multi-Language Support

The module supports **German (DE)** and **English (EN)** out of the box. The language switches automatically based on the phpVMS language setting (the flag selector in the top navigation bar).

### Language Files

```
Resources/lang/
├── de/
│   └── pulse.php    ← ~130 translation keys (German)
└── en/
    └── pulse.php    ← ~130 translation keys (English)
```

### How It Works

phpVMS sets `App::setLocale()` when a user changes the language via the flag icon. The module picks it up automatically via Laravel's `__()` translation helper. No additional configuration needed.

### Adding a New Language

1. Copy `Resources/lang/en/pulse.php` to `Resources/lang/xx/pulse.php` (where `xx` is the language code, e.g., `fr`, `es`, `pt`)
2. Translate all values in the new file
3. Clear the cache: `php artisan cache:clear`

The new language will be available immediately when phpVMS is set to that locale.

### Overriding Translations Without Modifying the Module

```bash
# Create override directory
mkdir -p resources/lang/modules/airlineinfopulse/de/

# Copy and edit
cp modules/AirlineInfoPulse/Resources/lang/de/pulse.php \
   resources/lang/modules/airlineinfopulse/de/pulse.php
```

Laravel will automatically prefer the override file if it exists.

---

## 🔒 Data Visibility & Restrictions

### Authentication

| Restriction | Details |
|------------|---------|
| **Login Required** | Both routes (`/` and `/compare`) are protected by `auth` middleware. Only logged-in phpVMS users can access the dashboard. |
| **No Admin Required** | Every registered pilot can see the full dashboard. No role-based restrictions. |
| **No Data Modification** | The module is **read-only**. It never writes to, inserts into, or deletes from any database table. |

### What Each Pilot Can See

| Section | Data Scope | Visibility |
|---------|-----------|------------|
| **KPI Strip** | All pilots' aggregated data | Everyone sees the same global numbers |
| **Cockpit (My Stats)** | Current logged-in user only | Each pilot sees only their own stats |
| **Missions** | Current logged-in user only | Personal achievements and progress |
| **Pilot Duel** | Own stats vs. selected rival | Anyone can compare with any other pilot |
| **Quick Start** | Flight schedule (all) | Same random suggestions for everyone |
| **Top Pilots** | All pilots ranked | All pilot names and flight stats are visible |
| **Top Aircraft** | All aircraft ranked | All aircraft stats are visible |
| **Activity Feed** | All pilots' recent activity | PIREPs, new registrations, maintenance events |
| **Airline Snapshot** | All airlines' aggregated data | Everyone sees the same airline stats |

### What Data Is NOT Exposed

- No email addresses, passwords, or personal user information
- No PIREP comments, notes, or rejection reasons
- No financial data (balances, transactions, PIREP pay)
- No admin settings or system configuration
- No raw database queries or SQL output
- No user IPs, sessions, or login history

### PIREP State Filter

Only **accepted PIREPs** (`PirepState::ACCEPTED`) are included in all statistics. Rejected, pending, or draft PIREPs are excluded everywhere — KPIs, rankings, feed, missions, and duels.

### Rate Limiting

The `/compare` AJAX endpoint is rate-limited to **30 requests per minute** per user to prevent automated abuse.

---

## 🎨 Theme & Dark Mode

The module automatically detects your theme's dark/light mode via multiple strategies:

| Detection Method | Priority | Trigger |
|-----------------|----------|---------|
| `data-bs-theme="dark"` on `<html>` | 1 (highest) | Bootstrap 5.3+ / DisposableTheme |
| `.dark` class on `<html>` or `<body>` | 2 | Common theme pattern |
| `data-theme="dark"` attribute | 3 | Alternative theme pattern |
| `prefers-color-scheme: dark` | 4 (fallback) | OS-level dark mode preference |

A `MutationObserver` watches for real-time theme changes — if the user toggles dark mode, the dashboard updates instantly without page reload.

### Custom CSS Variables

All colors are controlled via CSS custom properties in `index.blade.php`. You can override them in your theme:

```css
html.ap-dark {
    --ap-bg: #0f1219;
    --ap-surface: rgba(255,255,255,0.04);
    --ap-border: rgba(255,255,255,0.08);
    --ap-text: #c9d1d9;
    --ap-text-head: #e6edf3;
    --ap-muted: #7d8590;
    --ap-blue: #58a6ff;
    --ap-cyan: #3fc1c9;
    --ap-green: #3fb950;
    --ap-amber: #d29922;
    --ap-red: #f85149;
    --ap-violet: #a78bfa;
}

html.ap-light {
    --ap-bg: #f6f8fa;
    --ap-surface: #ffffff;
    --ap-border: rgba(0,0,0,0.1);
    --ap-text: #1f2937;
    --ap-text-head: #111827;
    /* ... etc ... */
}
```

---

## 🔌 Optional Module Integrations

### DisposableBasic

If installed, the module automatically integrates these features:

**Activity Feed — Maintenance Events**

The module auto-detects the `disposable_maintenance` (or `disposable_maintenances`) table and displays check events with color coding:

| Check Type | Color | Example |
|-----------|-------|---------|
| Hard Landing Check | 🔴 Red | Aircraft flagged after hard landing |
| Soft Landing / Inspection | 🟡 Amber | Routine inspection events |
| A-Check, B-Check, C-Check | 🔵 Cyan | Scheduled maintenance checks |

**FlightBoard & ActiveBookings Widgets**

The `widgets.blade.php` partial renders two DisposableBasic widgets:

```blade
@widget('DBasic::FlightBoard')      {{-- Live VATSIM/IVAO flights --}}
@widget('DBasic::ActiveBookings')    {{-- Currently booked flights --}}
```

> If DisposableBasic is not installed, these `@widget` calls silently produce no output.

### How Optional Detection Works

The controller never imports optional module classes. All access is runtime-detected:

```php
// 1. Check if table exists (result cached)
if ($this->schemaHasTable('disposable_maintenance')) {
    // 2. Check each column before using it
    if ($this->schemaHasColumn($mxTbl, 'last_note')) {
        // 3. Safe to query — uses standard DB facade
    }
}
```

---

## 🔐 Security

| Measure | Implementation |
|---------|---------------|
| **SQL Injection** | All queries use parameterized `?` placeholders or Eloquent. No string concatenation of user input. |
| **XSS Prevention** | All user data escaped via `{{ }}` (Blade auto-escape). JSON output uses all four `JSON_HEX_*` flags. |
| **CSRF Protection** | All forms and AJAX calls use Laravel's CSRF token from `<meta name="csrf-token">`. |
| **Input Validation** | Filter parameter validated against strict whitelist (`VALID_FILTERS` constant). Custom dates validated via regex (`Y-m-d` only). |
| **Rate Limiting** | `/compare` endpoint: 30 requests/minute via `throttle` middleware. |
| **Date Range Limits** | Custom date ranges capped at 366 days. End date cannot exceed current year. |
| **Auth Middleware** | Both routes require authentication (`['web', 'auth']`). |
| **Read-Only** | Module never writes to any database table. |
| **Defense-in-Depth** | `try/catch` around all `Carbon::parse()` calls with safe fallback values. |

---

## 📁 File Structure

```
modules/AirlineInfoPulse/
├── Config/
│   └── config.php                          # All configurable settings
├── Helpers/
│   └── PulseHelper.php                     # Date ranges, deltas, landing colors
├── Http/
│   ├── Controllers/
│   │   └── AirlineInfoPulseController.php  # Main controller (~900 lines)
│   └── Routes/
│       └── web.php                         # 2 routes: index + compare (AJAX)
├── Providers/
│   └── AirlineInfoPulseServiceProvider.php # Views, translations, config, routes
├── Resources/
│   ├── lang/
│   │   ├── de/pulse.php                    # German translations (~130 keys)
│   │   └── en/pulse.php                    # English translations (~130 keys)
│   └── views/
│       ├── index.blade.php                 # Main page (CSS + JS + layout)
│       └── partials/
│           ├── kpis.blade.php              # Global KPI strip
│           ├── cockpit.blade.php           # Personal cockpit with progress
│           ├── missions.blade.php          # Gamification cards + duel table
│           ├── quickstart.blade.php        # Random flight suggestions
│           ├── toplists.blade.php          # Top Pilots + Top Aircraft
│           ├── feed.blade.php              # Activity feed
│           ├── snapshot.blade.php          # Airline snapshot with trends
│           └── widgets.blade.php           # DisposableBasic widgets (optional)
├── module.json                             # phpVMS module manifest
├── composer.json                           # PSR-4 autoload configuration
├── README.md                               # This file
├── PILOTGUIDE.md                           # End-user documentation for pilots
└── LICENSE
```

---

## 🛠️ Customization

### Changing the Monthly Goal

Edit `Config/config.php`:

```php
'daily_goal_minutes' => 480, // 8 hours per month
```

### Adjusting Milestones

```php
'milestones' => [5, 10, 25, 50, 100, 250, 500, 1000],
```

### Adjusting Landing Rate Colors

```php
'landing_rate_thresholds' => [
    'green'  => -199,   // stricter: only 0 to -199 is green
    'orange' => -399,   // -200 to -399 is orange
],
```

### Hiding Sections

Comment out the `@include` line in `index.blade.php`:

```blade
{{-- @include('airlineinfopulse::partials.quickstart') --}}
```

### Publishing Views for Theme Overrides

```bash
php artisan vendor:publish --tag=views --force
```

This copies all views to `resources/views/modules/airlineinfopulse/` where you can modify them without touching the module source. Your overrides will survive module updates.

---

## 👨‍✈️ Pilot Guide

A detailed end-user guide for pilots is available in [**PILOTGUIDE.md**](PILOTGUIDE.md).

It covers all dashboard sections, what each statistic means, how to use the pilot duel, and tips for improving your stats. Share it with your VA members!

---

## 🔧 Troubleshooting

| Problem | Solution |
|---------|----------|
| **404 Not Found** | Clear caches (via Admin Panel or `php artisan route:clear`) and check `modules_statuses.json` |
| **404 after FTP upload** | Make sure `modules_statuses.json` has `"AirlineInfoPulse": true` and caches are cleared. Delete `bootstrap/cache/*` and `storage/framework/views/*` via FTP if needed |
| **Class not found** | Folder must be named exactly `AirlineInfoPulse` (case-sensitive). Check there's no double folder (`modules/AirlineInfoPulse/AirlineInfoPulse/`) |
| **KPIs show 0** | Only accepted PIREPs are counted — check PIREP status in admin |
| **Maintenance not in feed** | Requires [DisposableBasic](https://github.com/FatihKoz/DisposableBasic) module |
| **Wrong language** | Clear caches: Admin Panel → Maintenance → Clear All, or delete `storage/framework/views/*` via FTP |
| **Dark mode not detected** | Set `data-bs-theme="dark"` on `<html>` in your theme |
| **Slow page load** | Check database indexes on `pireps.state`, `pireps.user_id`, `pireps.submitted_at` |
| **Blank page / 500 error** | Check `storage/logs/laravel.log` via FTP for the exact error message |

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -m 'Add my feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Open a Pull Request

### Code Standards

- Follow PSR-12 for PHP code
- Use Laravel conventions for Blade templates
- All database access must use parameterized queries
- New text must use translation keys (`$t('key')`) — no hardcoded strings
- Test with both MySQL and MariaDB if possible

---

## 📝 Changelog

### v1.1.0 — Bugfixes, Unit System & Pilot Guide

#### 🐛 Bugfixes

- **Softest Landing detection** — Fixed `MAX(landing_rate)` returning the *hardest* landing instead of the softest. Now uses `MIN(CASE WHEN ABS(landing_rate) >= threshold ...)` to correctly find the smallest absolute value. Works with both positive and negative ACARS values.
- **Default filter changed** — Dashboard now loads with "Today" filter instead of "Week" (updated in 4 locations).
- **Config `min_landing_rate` accepts negative values** — `abs()` is now applied on read, so entering `-75` or `75` produces the same result.

#### 🆕 New Features

- **Dynamic Unit System** — All distances (NM/km/mi), fuel/weight (kg/lbs), and efficiency labels automatically adapt to phpVMS Admin → Settings. Zero hardcoded unit strings in any view file.
- **Fuel per Hour** (`kg/h` / `lbs/h`) — New efficiency metric on each Top Aircraft card, calculated as `total_fuel / block_hours`.
- **GDPR / DSGVO Name Shortening** — Pilot names displayed as "First L." (e.g., "Thomas K.") in all public-facing sections: Top Lists, Activity Feed, Pilot Duel. Implemented via `PulseHelper::shortName()`.
- **Pilot Guide page** (`/airline-info-pulse/guide`) — Fully bilingual in-app documentation with 13 sections, interactive table of contents, and dynamic values from config (landing rate thresholds, daily goal, CO₂ factor, units). Linked from the dashboard via a ❓ icon next to the title.
- **Configurable landing rate threshold** — New `min_landing_rate` config option (default: 5 fpm). Landings with `ABS(landing_rate) < threshold` are excluded from "Softest Landing" / "Personal Record". Extensively documented in config with examples table.
- **Phosphor Icons self-loading** — Module now loads Phosphor Icons via CDN, ensuring compatibility with any theme (e.g., DisposableTheme which only ships FontAwesome 5.x).

#### 🌍 Translation Updates

- 251 translation keys per language (DE + EN), up from ~130 in v1.0.0
- All Pilot Guide content fully translatable — no hardcoded text
- New keys for: units, GDPR, fuel/hour, guide sections, FAQ entries

#### 📁 Files Changed

- `Config/config.php` — Added unit overrides, `min_landing_rate` with comprehensive documentation, updated comments
- `Helpers/PulseHelper.php` — Added `getUnits()`, `shortName()`, unit conversion methods
- `Http/Controllers/AirlineInfoPulseController.php` — Unit system integration, `guide()` method, softest landing fix, `abs()` on config read
- `Http/Routes/web.php` — Added `/guide` route
- `Resources/views/guide.blade.php` — New: fully bilingual Pilot Guide page
- `Resources/views/index.blade.php` — Guide link, Phosphor Icons CDN, dynamic units
- `Resources/views/partials/*.blade.php` — Dynamic unit labels in all 6 partial views
- `Resources/lang/en/pulse.php` — 120+ new keys
- `Resources/lang/de/pulse.php` — 120+ new keys

---

### v1.0.0 — Initial Release

- KPI Dashboard with 6 metrics and trend comparison
- Personal Cockpit with monthly goal progress
- 10 Mission / Gamification cards
- Pilot Duel comparison system
- Quick Start with region-based flight suggestions
- Top Pilots & Top Aircraft rankings with expandable details
- Activity Feed with PIREP, new pilot, and maintenance events
- Airline Snapshot with trend indicators
- Multi-language support (DE / EN)
- Full dark / light mode auto-detection
- Security hardening (XSS, SQLi, CSRF, rate limiting, input validation)
- Performance optimization (schema caching, efficient ranking queries)
- Optional DisposableBasic integration
- Custom table prefix support

---

## 📄 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

## 🙏 Credits

- **[phpVMS 7](https://github.com/nabeelio/phpvms)** — The virtual airline management system
- **[DisposableBasic](https://github.com/FatihKoz/DisposableBasic)** — Optional module for maintenance data and widgets
- **[Phosphor Icons](https://phosphoricons.com/)** — Icon set used throughout the dashboard
- **[Bootstrap 5](https://getbootstrap.com/)** — Grid system and utility classes

### 🧑‍✈️ Contributors & Testers

- **[@ProAvia](https://github.com/ProAvia)** — Testing, bug reports, and feature ideas (unit system, fuel/hour metric, landing rate threshold, GDPR name shortening, Pilot Guide improvements)

---

*Built with ❤️ for the virtual aviation community by [German Sky Group](https://german-sky-group.eu)*
