# Roadmap

## v0.1.0 — Initial Release
- [x] Full CRL generation for Certificate Authorities
- [x] Delta CRL generation (optional)
- [x] Auto-scheduling of CRL renewal
- [x] CRL distribution to configurable filesystem disks
- [x] API routes for listing, generating, and downloading CRLs
- [x] Artisan commands (generate, list, publish)
- [x] CrlGenerated event
- [x] Multi-tenant support

## v0.2.0 — Planned
- [ ] CRL partitioning for very large CAs
- [ ] CRL caching layer with configurable TTL
- [ ] Support for indirect CRLs
- [ ] Webhook notifications on CRL generation

## v1.0.0 — Stable Release
- [ ] Full test coverage (90%+)
- [ ] Performance benchmarks for large revocation lists
- [ ] Comprehensive API documentation
- [ ] Production hardening and security audit

## Ideas / Backlog
- LDAP distribution point support
- CRL monitoring and alerting integration
- Bulk revocation list import
