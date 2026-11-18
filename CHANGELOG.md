# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-11-18

First release: the engine shared by the privacy suites ([laravel-lgpd](https://github.com/jeffersongoncalves/laravel-lgpd), [laravel-gdpr](https://github.com/jeffersongoncalves/laravel-gdpr), [laravel-ccpa](https://github.com/jeffersongoncalves/laravel-ccpa), [laravel-us-privacy](https://github.com/jeffersongoncalves/laravel-us-privacy), [laravel-quebec](https://github.com/jeffersongoncalves/laravel-quebec)), which use it from their 2.0.0.

- Personal data declarations: `PersonalDataAttribute`, `HasPersonalData`, `InteractsWithPersonalData`, anonymize strategies (nullify, hash, mask, keep).
- Inventory and k-anonymity (`Privacy`, `Generalize`, inventory and k-anonymity commands).
- Retention with tombstones, re-applied after a backup restore.
- Hash-chained, append-only audit log with tamper verification.
- Append-only consent ledger with versions, expiry, visitor claims and a banner endpoint.

Each suite subclasses these with its own config, tables, attribute and events, so suites installed side by side never see each other's events.

## [Unreleased]
