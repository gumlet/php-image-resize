# Changelog

All notable changes to this project are documented in this file.

## [3.0.1] - 2026-10-06 — **Security release** (GHSA-3g9v-hjrx-mg5h)

### Security

- **CWE-400 — Image bomb / uncontrolled resource consumption.**
  `__construct()` now checks the source image pixel count (`width × height`)
  against the new `$max_source_pixels` property (default **15,000,000**)
  **before** calling `imagecreatefromXXX()`. A 4,000 × 4,000 PNG that is
  only ~56 KB on disk but requires ~61 MB of memory to decode is now rejected
  immediately with `ImageResizeException`, so the fatal OOM error can no longer
  kill a PHP-FPM worker. Set `$resize->max_source_pixels = 0` to disable the
  limit for fully trusted input.

### Added

- `$max_source_pixels` public property (default `15_000_000`).
- 2 PHPUnit regression tests covering the image-bomb scenario.

## [3.0.0] - 2026-07-22


### Added

- Full **typed public API** (parameters and return types on `ImageResize`).
- **PHPStan** (level 3) and **PSR-12** (PHP-CS-Fixer) in development; CI static-analysis job.
- PHPUnit 10+ configuration migration; tests on PHP 8.2–8.5.
- Additional tests: missing file, `allow_enlarge`, `exact_size` save, WebP/AVIF save, gamma save, stronger filter test.

### Changed

- **Minimum PHP version: 8.1** (`ext-gd`, `ext-fileinfo` required).
- `ImageResize::__construct()` requires `string $filename` (non-empty path, `data:` URL, or valid file).
- `save()` documents `array|false $exact_size` for fixed canvas output.
- `chmod` after save only when saving to a file path string.
- Open `finfo` only when `getimagesize()` fails (faster loads on success path).

### Fixed

- BMP save no longer passes `null` to `imagebmp()` (PHP 8.5 deprecation).
- Removed `finfo_close()` (deprecated in PHP 8.5).
- Constructor no longer uses an invalid `return` statement.

### Removed

- Support for PHP versions below 8.1 (use `2.x` releases).

[3.0.0]: https://github.com/gumlet/php-image-resize/compare/2.1.0...3.0.0
