# Feature Tests

Feature tests are grouped by API module:

- `AuthApiTest.php`
- `RoomApiTest.php`
- `GuestApiTest.php`
- `BookingApiTest.php`
- `HousekeepingApiTest.php`
- `FolioApiTest.php`
- `ReportSettingsApiTest.php`

Coverage includes successful flows, validation errors, authorization failures, booking conflict handling, booking lifecycle behavior, folio balances, housekeeping completion, reports, and settings updates.

These tests follow Laravel PHPUnit conventions and are intended to run once the backend skeleton is converted into a full Laravel application with migrations, Sanctum, and PHPUnit configured.
