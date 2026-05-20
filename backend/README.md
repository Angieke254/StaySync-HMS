# Backend

This directory contains the StaySync HMS Laravel-style API implementation for the backend lead scope in Days 2-8 of the project plan.

## Implemented Scope

- Auth endpoints for register, login, logout, and current user.
- Room type and room CRUD, including room status changes with status log records.
- Dashboard statistics for rooms, arrivals, departures, occupancy, and revenue.
- Guest CRUD with search and booking history loading.
- Booking creation, listing, detail, update, availability checks, and lifecycle actions.
- Pessimistic booking checks in `BookingService` to prevent overlapping room bookings.
- Tape chart data endpoint grouped by room with booking color metadata.
- Housekeeping task listing, creation, updates, completion, and scheduled task creation.
- Folio charges, payment recording, balance summaries, charge voiding, and invoice view.
- Dynamic pricing with rate overrides, weekend surcharge, tax settings, and occupancy yield adjustment.
- Reports for occupancy, revenue, room type performance, and guest statistics.
- Role middleware for admin, manager, front desk, housekeeping, and POS staff access boundaries.
- Activity logging for critical booking, folio, payment, and rate actions.
- Broadcast events/channels for booking and room status changes.
- Mail classes and Blade views for booking confirmation, pre-arrival, and post-departure emails.
- SQL schema and seed data in `database/sql/staysync_v1.sql`.

## Important Runtime Note

The repository currently has a backend skeleton, not a complete installable Laravel application. It is still missing standard framework files such as `composer.json`, `artisan`, config files, migrations, and PHPUnit configuration.

To run this backend as a real API, convert the skeleton into a full Laravel app, install Sanctum, configure MySQL, register the middleware/events/routes, then convert the SQL schema into migrations or import `database/sql/staysync_v1.sql`.

## Main Files

- `routes/api.php` - API route surface and role protection.
- `routes/channels.php` - private broadcast channel authorization.
- `app/Models` - Eloquent models and relationships.
- `app/Services` - booking, pricing, folio, room status, and activity log business logic.
- `app/Http/Controllers` - JSON API controllers.
- `app/Http/Middleware/CheckRole.php` - role-based route access.
- `app/Console/Kernel.php` - scheduled housekeeping and email jobs.
- `resources/views` - invoice and email templates.
