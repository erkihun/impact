# Development

Run `composer run dev` for the Laravel server, queue listener, log viewer and Vite watcher. Use a local MySQL database when changing schema behavior; Pest uses in-memory SQLite for fast isolation.

Code rules:

- `declare(strict_types=1)` in project PHP source
- PHP 8.4 and Laravel 12 conventions
- Form Requests for validation, Actions for transactions, Resources for API payloads
- UUIDv7 identifiers and enum-backed states
- every sensitive transition produces audit evidence
- no requirement is marked complete without a test or operational proof

Useful VS Code extensions: PHP Intelephense, Laravel Extra Intellisense, Blade Formatter, Tailwind CSS IntelliSense and Pest.
