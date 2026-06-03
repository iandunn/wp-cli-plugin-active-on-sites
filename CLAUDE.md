# wp-cli-plugin-active-on-sites

WP-CLI command for Multisite: `wp plugin active-on-sites <plugin_slug>` — lists all sites where a given plugin is active.

## Architecture

Single-file plugin (`wp-cli-plugin-active-on-sites.php`), no classes — flat functions under namespace `WP_CLI\Plugin\Active_On_Sites`. No build step.

Entry point: `invoke()` → `pre_flight_checks()` → `find_sites_with_plugin()` → `display_results()`

## Testing

Behat integration tests (no unit tests). Two feature files in `features/`:
- `plugin-active-on-sites.feature` — multisite scenarios
- `plugin-active-on-sites-single-site.feature` — single site error

Run tests inside the LocalWP shell:
```
composer test
```

The `bin/behat-localwp` wrapper auto-detects the MySQL socket so no env vars needed. Requires running inside a LocalWP shell (`wp-cli/wp-cli-tests` handles WP scaffolding).

## Key Behaviors

- Errors if not Multisite
- Warns and halts (exit 0) if plugin is network-activated
- Iterates all sites via `switch_to_blog()` / `restore_current_blog()`
- Supports `--field`, `--fields`, `--format` via `WP_CLI\Formatter`
