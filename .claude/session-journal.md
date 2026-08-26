# Session Journal — pelican-plugins

## Current State

**Focus:** Compatibility audit of `server-documentation` plugin (v1.1.5, last code change 2026-02-12 for Pelican beta32) against current Pelican Panel + the plugin-system conventions a Pelican maintainer flagged.

**Established facts (verified in source, 2026-08-27):**
- Pelican `main` (canary): `filament/filament ^5.7`, `laravel/framework ^13.27`, `php ^8.3||^8.4||^8.5`. Plugin currently targets Filament 4 / PHP ^8.2.
- `PluginService::loadPlugins()` (app/Services/Helpers/PluginService.php) auto-loads: config (`config/<id>.php` → config key `<id>`), `lang/` (namespace = plugin id), all providers in `src/Providers`, `database/migrations`, `resources/views` (namespace = plugin id). Plugin's `loadMigrationsFrom/loadViewsFrom/loadTranslationsFrom/mergeConfigFrom` are all redundant.
- Laravel policy auto-discovery (`Models\X` → `Policies\XPolicy`) makes `Gate::policy()` calls redundant; official plugins rely on it.
- `PanelRegistry::register()` calls `Panel::register()` immediately (register phase) → Livewire components collected then. `ServerResource::registerCustomRelations()` in provider **boot()** is too late → that's why manual `Livewire::component` exists. Docs + official plugins call it in provider **register()**. `AppServiceProvider` (which registers plugin providers) precedes panel providers in bootstrap/providers.php, so register() timing works.
- Pelican docs: https://pelican.dev/docs/panel/advanced/plugins/ — permissions via `Role::registerCustomDefaultPermissions('document')` + `registerCustomModelIcon`; policies use `App\Policies\DefaultAdminPolicies` trait; `panels` + `panel_version` in plugin.json; update_url JSON format keyed by panel version or `"*"` with `{version, download_url}`.
- `PluginService::downloadPluginFromFile` now replaces an existing plugin in place (README's "cannot upload over existing plugin" claim is stale); `updatePlugin()` exists via `update_url`.
- Pelican code uses `->schema([])` on actions, `TextEntry` not `Placeholder`, `->recordActions()` not `->actions()`.
- Official reference plugins imported into RepoQL: `github://pelican-dev/plugins` (see `subdomains`, `tickets`).
- Local test run blocked: no PHP on PATH; Podman WSL machine fails to start (HCS_E_CONNECTION_TIMEOUT x2). Tests run in CI only.
- Uncommitted changes in git status (images + pre-upgrade-patch.php) are zero-byte/line-ending noise — not ours, leave alone.

**Findings written:** `.notes/pelican-compat-findings.md` (gitignored) + published artifact. Releases since beta32: beta33 (02-18), **beta34 (05-08: Laravel 13 / Filament 5 / PHP≥8.3)**, beta35 (06-22), beta36 (08-05), beta37 (08-13), beta38 (08-16, org rename pelican-dev→pelican). Filament 5 has no PHP API changes vs v4; Livewire 4 deprecates `Livewire.hook('commit')` (still works).

**Implemented on branch `feat/pelican-beta34-compat`** (v1.2.0): slim provider (hooks + role permissions in `register()`, fallback gate + assets + relation in `boot()`), PHP ^8.3 + CI 8.3, `plugin.json` panels/panel_version/update_url, root `update.json` (keyed by plugin id, `"*"` entry), Livewire 4 `interceptMessage`, `->schema()`/`->recordActions()`, README/CHANGELOG rewrite, test harness mirrors Pelican loader + new `tests/Unit/Providers/ServiceProviderRegistrationTest.php`. Verified locally: PHP 8.5.9 installed via `scoop install php` (user scope; run with `-d extension_dir=... -d extension=pdo_sqlite,...`), Pest 186 passed / 9 skipped, Pint passes after a separate `style:` commit (main already failed pint.json's concat_space rule). The verify-gate hook doesn't recognise `vendor/bin/pest` — stamp `.claude/.verified` manually after a green run. PR #5 merged to main (f31574e). CI on #5 failed at `composer install`: Composer's advisory blocking rejects every laravel/framework 11.x, and testbench ^9 only resolves to 11.x. Follow-up branch `chore/ci-dev-deps`: testbench ^11 + pest ^4 (Laravel 13, same generation as Pelican) + Pint 1.30 reformat. Verified locally 184/9, Pint pass. Composer installed via `scoop install composer` (run `composer.phar` with the same php -d flags). Still to do after merge: tag v1.2.0. Gotcha: the Bash tool mangles `\\` in heredocs — verify JSON with `perl -MJSON::PP` after writing.

**Original plan was:** implement — slim provider (drop load*/mergeConfig/Gate::policy/Livewire::component; move `registerCustomRelations` + `Role::registerCustomDefaultPermissions('document')` into `register()`), bump php ^8.3 + CI, add `panel_version`/`panels`/`update_url` to plugin.json + `update.json`, rewrite README update section, fix pelican-dev links, optionally `Livewire.interceptMessage`, `->schema()`/`TextEntry`/`recordActions` alignment. Run `vendor/bin/filament-v5` and Pest in CI (no local PHP).

## Log

### 2026-08-27 08:40 — Decision: Role editor is the only admin-permission path
- Gavin agreed to drop the "server admins inherit document access" fallback and `SERVER_DOCS_EXPLICIT_PERMISSIONS`. Reason: chaining unrelated permissions is a hidden privilege expansion; Pelican's role-permission API removes the original need; v1.2.0 is already a breaking release; failure mode is loss of access (safe). Upgrade note added to CHANGELOG + README.

### 2026-08-27 08:10 — Completed: findings delivered
- Two researcher reports merged (release notes; Filament5/Livewire4/Laravel13). Findings doc + artifact published. No code changed yet.

### 2026-08-27 07:30 — Started: Pelican compatibility audit
- User asked to review plugin, find last update, pull Pelican release notes since then, identify needed updates.
- Maintainer comment relayed: plugins shouldn't manually register views/migrations/langs; only routes via RouteServiceProvider.
- Imported pelican-dev/panel and pelican-dev/plugins into RepoQL for source-level verification.
