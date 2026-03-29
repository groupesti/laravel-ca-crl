# Architecture — laravel-ca-crl (Certificate Revocation Lists)

## Overview

`laravel-ca-crl` manages the generation, storage, distribution, and scheduling of X.509 Certificate Revocation Lists (CRLs) as defined by RFC 5280. It supports automatic CRL generation on a configurable schedule, distribution to multiple endpoints, and provides both API and CLI access. It depends on `laravel-ca` (core) and `laravel-ca-crt` (certificate and revocation data).

## Directory Structure

```
src/
├── CrlServiceProvider.php             # Registers generator, distributor, manager, scheduler
├── Console/
│   └── Commands/
│       ├── CrlGenerateCommand.php     # Generate a CRL for a specific CA (ca-crl:generate)
│       ├── CrlPublishCommand.php      # Publish CRL to distribution points (ca-crl:publish)
│       └── CrlListCommand.php         # List generated CRLs with metadata
├── Contracts/
│   ├── CrlManagerInterface.php        # Contract for CRL lifecycle management
│   └── CrlDistributionInterface.php   # Contract for CRL distribution backends
├── Events/
│   └── CrlGenerated.php              # Fired when a new CRL is generated
├── Facades/
│   └── CaCrl.php                      # Facade resolving CrlManagerInterface
├── Http/
│   ├── Controllers/
│   │   └── CrlController.php         # REST API and public download endpoint for CRLs
│   └── Resources/
│       └── CrlResource.php           # JSON API resource for CRL metadata
├── Models/
│   └── Crl.php                        # Eloquent model storing CRL blobs and metadata
├── Scheduling/
│   └── CrlAutoGenerate.php           # Callable registered with Laravel Scheduler for auto-generation
└── Services/
    ├── CrlGenerator.php               # Generates DER-encoded CRLs with revoked certificate entries
    ├── CrlManager.php                 # Orchestrates generation, storage, and distribution
    └── CrlDistributor.php            # Publishes CRLs to configured distribution points
```

## Service Provider

`CrlServiceProvider` registers the following:

| Category | Details |
|---|---|
| **Config** | Merges `config/ca-crl.php`; publishes under tag `ca-crl-config` |
| **Singletons** | `CrlGenerator`, `CrlDistributionInterface` (resolved to `CrlDistributor`), `CrlManagerInterface` (resolved to `CrlManager`) |
| **Alias** | `ca-crl` points to `CrlManagerInterface` |
| **Migrations** | `ca_crls` table |
| **Commands** | `ca-crl:generate`, `ca-crl:publish`, `ca-crl:list` |
| **Routes** | API routes under configurable prefix (default `api/ca/crls`) |
| **Scheduler** | Registers `CrlAutoGenerate` on a configurable frequency (default `daily`) when `ca-crl.auto_generate` is true |

## Key Classes

**CrlManager** -- The central service coordinating CRL generation, storage, and distribution. It collects revoked certificates from the certificate package, delegates to `CrlGenerator` for DER encoding, stores the result via the `Crl` model, and triggers distribution via `CrlDistributor`.

**CrlGenerator** -- Builds a DER-encoded CRL conforming to RFC 5280. It encodes the issuer DN, thisUpdate/nextUpdate timestamps, revoked certificate entries with reason codes, and signs the CRL with the CA's private key. Uses phpseclib for all ASN.1 encoding and signing operations.

**CrlDistributor** -- Implements `CrlDistributionInterface` to publish generated CRLs to configured distribution points (HTTP endpoints, filesystem paths, CDN locations). Extensible for custom distribution mechanisms.

**CrlAutoGenerate** -- A callable class registered with Laravel's task scheduler. When invoked, it iterates all active CAs and generates fresh CRLs for any whose current CRL has expired or is within the overlap window.

## Design Decisions

- **Automatic scheduling**: CRL generation is registered directly with Laravel's `Schedule` during the boot phase, using a configurable frequency string. This ensures CRLs stay fresh without requiring manual cron setup.

- **Overlap window**: The config supports `lifetime_hours` and `overlap_hours`, so a new CRL is generated before the old one expires. This prevents a window where no valid CRL exists.

- **Distribution as a separate concern**: CRL generation and distribution are decoupled. The `CrlDistributionInterface` allows plugging in CDN push, S3 upload, or LDAP publication independently of the generation logic.

## PHP 8.4 Features Used

- **`readonly` constructor promotion**: Used in `CrlManager` and `CrlGenerator` for immutable dependencies.
- **Named arguments**: Used in service construction and scheduler registration.
- **Strict types**: Every file declares `strict_types=1`.

## Extension Points

- **CrlDistributionInterface**: Implement to add custom CRL distribution backends (e.g., push to CDN, S3, LDAP).
- **CrlManagerInterface**: Bind a custom implementation for alternative CRL workflows.
- **Events**: Listen to `CrlGenerated` for monitoring, alerting, or triggering downstream processes.
- **Config `ca-crl.auto_generate`**: Disable automatic generation to trigger CRL creation manually or via custom scheduling.
- **Config `ca-crl.schedule_frequency`**: Set to any Laravel schedule frequency method name (`hourly`, `daily`, `twiceDaily`, etc.).
