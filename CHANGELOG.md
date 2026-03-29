# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-03-29

### Added

- `Crl` Eloquent model with UUID support, tenant scoping, and query scopes (`current`, `delta`, `full`, `forCa`).
- `CrlManager` service for generating full and delta CRLs, checking revocation status, and retrieving CRLs.
- `CrlGenerator` service for building DER/PEM-encoded CRLs using phpseclib v3.
- `CrlDistributor` service for publishing CRLs to configurable Laravel filesystem disks.
- `CrlAutoGenerate` scheduled task for automatic CRL generation with configurable overlap window.
- `CrlManagerInterface` and `CrlDistributionInterface` contracts for extensibility.
- `CrlGenerated` event dispatched after every CRL generation.
- `CaCrl` facade for convenient static access.
- Artisan commands: `ca-crl:generate`, `ca-crl:publish`, `ca-crl:list`.
- REST API routes for CRL retrieval via `CrlController` and `CrlResource`.
- Configurable CRL lifetime, overlap hours, schedule frequency, delta CRL support, storage disk, and distribution URL.
- Database migration for the `ca_crls` table.
- Multi-tenant support via `BelongsToTenant` trait.
- Audit trail support via `Auditable` trait.
