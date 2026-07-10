# Compliance Control Mapping

This document maps the capabilities of the `boldlygrow/audit-log` package to common security and compliance control frameworks. It is intended to help engineering and GRC teams understand **where this package fits** in an audit-and-accountability program.

## Disclaimer

> This document is **not legal, audit, or compliance advice**, and it does not certify or guarantee compliance with any framework.
>
> Installing this package **does not make an application compliant**. Audit logging is one technical building block among many. Every control referenced below also depends on people, process, and other technology (retention policies, access controls, monitoring, incident response, time synchronization, secure storage) that are outside the scope of a logging library. Control identifiers and requirement summaries are provided for orientation only — always validate scope and applicability with your own auditor or assessor against the authoritative framework text.

## How to Read This Document

The package is a **log record generator and (optionally) a durable store**. It helps you *produce complete, consistent, attributable audit records* and *persist them to a queryable table*. It deliberately does **not** attempt to be a full audit-management platform.

Each framework section uses three columns:

| Column | Meaning |
|--------|---------|
| **Control** | The framework's control identifier and short requirement. |
| **How this package supports it** | The concrete feature(s) that contribute evidence toward the control. |
| **Your responsibility** | What you must still implement or operate — the package alone does not satisfy the control. |

## Scope & Shared Responsibility

### What this package provides

- **Standardized, structured audit records** with a consistent set of context keys, so events are uniformly indexable and searchable. See [Log Parameter Definitions](README.md#log-parameter-definitions).
- **Individual accountability / attribution** — every event can carry the actor's identity (`actor_id`, `actor_email`, `actor_name`, `actor_username`, `actor_provider_id`), authenticated model (`actor_type`), session (`actor_session_id`), network origin (`actor_ip_addr`, including proxy/CDN headers), and request channel (`actor_source`: `web` / `api` / `cli` / `system`). See [Actor Metadata](README.md#actor-metadata).
- **Event classification and outcome** — `event_type` (octet notation including result/reason), `level`, `message`, and originating `method`.
- **Time attribution** — `occurred_at` and a formatted `datetime` in ISO 8601 (Zulu) for ordering and correlation.
- **Change / state-transition capture** — `attribute_key`, `attribute_value_old`, `attribute_value_new`.
- **Affected-resource linkage** — `record_*`, `parent_*`, `related_*`, `subject_*`, and `tenant_*` fields tie an event to the objects and accounts it affected. See [Model References](README.md#model-references-and-automatic-types).
- **Execution context for automation** — `job_*` fields for background jobs, batches, and pipelines.
- **Optional durable persistence** — write events to a queryable `audit_logs` table for perpetual, SQL-searchable storage. See [Database Persistence](README.md#database-persistence).
- **Extensibility for org-specific evidence** — [custom fields](README.md#adding-custom-fields) let you persist tenant/organization/workspace identifiers as first-class, indexable columns.

### What remains your responsibility

Logging frameworks universally require more than *generating* records. The following are **out of scope for this package** and must be provided by your application, infrastructure, or operational processes:

| Responsibility | Why the package does not cover it | Representative controls |
|----------------|-----------------------------------|-------------------------|
| **Protecting log integrity** (tamper-evidence, append-only/WORM, hashing/signing) | Records written to the log channel or database can be modified or deleted by anyone with access; the shipped model uses reversible soft deletes. The package does not hash, sign, or lock records. | NIST AU-9, 800-171 3.3.8, ISO A.8.15, CIS 8.3, CISA 2.U |
| **Access control to audit records** | The package writes records; it does not restrict who can read or manage them. | NIST AU-9/AC-6(9), 800-171 3.3.9 |
| **Retention scheduling & enforcement** | Database rows are stored indefinitely (no auto-purge), and log-channel retention is configured outside this package. There is no built-in retention timer or legal-hold. | NIST AU-11, ISO A.8.10, 800-171 3.3.1 |
| **Review, analysis, alerting & monitoring (SIEM)** | The package generates records; it does not review them, detect anomalies, or alert. | NIST AU-6/SI-4, SOC 2 CC7.2, CIS 8.11, 800-171 3.3.3/3.3.5 |
| **Time synchronization** | Timestamps use the host clock; NTP/synchronization is an infrastructure concern. | NIST AU-8, ISO A.8.17, CIS 8.4, 800-171 3.3.7 |
| **Logging-failure alerting** | A model misconfiguration is logged as a warning, but there is no alert-on-failure mechanism. | 800-171 3.3.4 |
| **Completeness of coverage** | The package only records the events you instrument. Ensuring all security-relevant events are logged is a design responsibility. | NIST AU-2, CIS 8.2, SOC 2 CC7.2 |
| **Sensitive-data minimization / encryption** | The package stores whatever you pass it. Avoid logging secrets/PII; add encrypted casts on your model overlay if needed (see [Handling Sensitive Data](#handling-sensitive-data)). | ISO A.8.15, SOC 2 CC6.x, 800-53 SI-11 |

## Audit Record Content Mapping

Most frameworks (notably NIST 800-53 **AU-3**, CIS **8.5**, and ISO **A.8.15**) enumerate the elements an audit record should contain. The package's schema maps to these directly:

| Required element | Package field(s) |
|------------------|------------------|
| **What type of event occurred** | `event_type`, `level`, `message`, `method` |
| **When the event occurred** | `occurred_at`, `datetime`, plus `created_at` on the persisted row |
| **Where the event occurred / source** | `method` (code location), `actor_source` (channel), `job_*` (execution context) |
| **Source of the event (origin)** | `actor_ip_addr`, `actor_session_id`, `actor_source` |
| **Outcome of the event** | `event_type` result/reason segment (e.g. `…success.ok`, `…error.validation`), `level` |
| **Identity of the individuals/subjects** | `actor_id`, `actor_email`, `actor_name`, `actor_username`, `actor_provider_id`, `actor_type` |
| **Affected resources & before/after state** (AU-3(1) additional information) | `record_*`, `parent_*`, `related_*`, `subject_*`, `tenant_*`, `attribute_key`, `attribute_value_old`, `attribute_value_new` |

## Framework Mappings

### SOC 1 (SSAE 18 / ICFR)

SOC 1 evaluates entity-defined control objectives relevant to financial reporting; it has no fixed control catalog. Audit logging typically provides evidence for **IT General Controls (ITGCs)**.

| Control objective (typical) | How this package supports it | Your responsibility |
|-----------------------------|------------------------------|---------------------|
| Changes to financially relevant data are logged and traceable to an individual | Attributable records with actor identity + before/after values (`attribute_value_old/new`) | Instrument the relevant transactions; retain and protect the records |
| Logical access events are captured | Actor, session, IP, and `actor_source` fields on authentication/authorization events | Ensure access events are instrumented; review access reports |
| Evidence of control operation is retained | Durable `audit_logs` persistence, queryable for the audit period | Define and enforce a retention period covering the reporting window |

### SOC 2 (AICPA Trust Services Criteria)

| Control | How this package supports it | Your responsibility |
|---------|------------------------------|---------------------|
| **CC6.1 / CC6.2 / CC6.3** — Logical access is authorized, provisioned, and modified | Attributable records of access-relevant actions (actor + affected subject/record) | Enforce access control itself; review the records |
| **CC7.2** — Monitor system components for anomalies | Consistent, structured events suitable for ingestion into monitoring/SIEM | Operate the monitoring, detection, and alerting |
| **CC7.3 / CC7.4** — Evaluate and respond to security events | Detailed, correlatable records to support investigation | Triage, investigate, and respond |
| **CC8.1** — Change management | Change events with before/after values and actor attribution | Wire logging into your change workflows |
| **PI1.4 / PI1.5** — Processing integrity of records/outputs | Complete, standardized record schema and returned DTO array | Validate completeness/accuracy of processing |

### ISO/IEC 27001:2022 (Annex A)

| Control | How this package supports it | Your responsibility |
|---------|------------------------------|---------------------|
| **A.8.15 Logging** | Produces event logs recording user activities, exceptions, and events with standardized content | Protect logs; define retention; ensure coverage |
| **A.8.16 Monitoring activities** | Structured records suitable for monitoring/anomaly detection | Perform the monitoring and analysis |
| **A.8.17 Clock synchronization** | Consistent ISO 8601 (Zulu) timestamps on each record | Synchronize system clocks (NTP) |
| **A.5.28 Collection of evidence** | Durable, attributable records usable as evidence | Preserve chain of custody and integrity |
| **A.8.10 Information deletion** | Persistence uses reversible soft deletes (records are not silently hard-deleted) | Implement retention/secure-deletion policy |

### NIST SP 800-53 Rev 5 (AU — Audit & Accountability, and related)

| Control | How this package supports it | Your responsibility |
|---------|------------------------------|---------------------|
| **AU-2 Event Logging** | A reusable, consistent mechanism to log defined event types | Define which events are logged; review the list |
| **AU-3 Content of Audit Records** | Captures type/when/where/source/outcome/identity — see [content mapping](#audit-record-content-mapping) | Ensure each event supplies the fields |
| **AU-3(1) Additional Audit Information** | Affected-resource, subject, tenant, and before/after fields | — |
| **AU-8 Time Stamps** | ISO 8601 (Zulu) timestamps per record | System clock synchronization |
| **AU-10 Non-repudiation** *(partial)* | Binds actions to actor identity + session | Add integrity protection (signing) for strong non-repudiation |
| **AU-12 Audit Record Generation** | Generates records on demand across the application | Enable at all required components |
| **AC-6(9) Log Use of Privileged Functions** | Attributable records for privileged actions when instrumented | Instrument privileged operations |
| **CM-5(1) / CM-3 Configuration change logging** | Change events with actor + before/after values | Wire into configuration/change flows |
| **AU-9 Protection of Audit Information** *(gap)* | — | Restrict access; ensure tamper-evidence/integrity |
| **AU-11 Audit Record Retention** *(gap)* | Durable storage with no auto-purge | Enforce a retention schedule |
| **AU-6 Audit Review/Analysis** *(gap)* | Queryable records | Perform review, analysis, and reporting |

### NIST SP 800-63 (Digital Identity Guidelines)

Mapping here is **supportive/partial** — the package captures identity and session context on events but is not an identity provider.

| Area | How this package supports it | Your responsibility |
|------|------------------------------|---------------------|
| Authentication / session event records (800-63B) | `actor_id`, `actor_session_id`, `actor_source`, `actor_ip_addr` on auth-relevant events | Operate the authenticator/session lifecycle and record-keeping requirements |
| Identity/account activity records (800-63A) | Attributable, timestamped records of account actions | Identity proofing, enrollment records, and privacy controls |

### NIST SP 800-171 (Protecting CUI) — 3.3 Audit & Accountability

*(Rev 2 numbering shown; Rev 3 uses `03.03.xx`.)*

| Control | How this package supports it | Your responsibility |
|---------|------------------------------|---------------------|
| **3.3.1** Create/retain audit logs for monitoring, analysis, investigation, reporting | Standardized record generation + durable `audit_logs` store | Define retained event set; enforce retention |
| **3.3.2** Trace actions to individual users (accountability) | Full actor-identity attribution per event | Ensure users are authenticated and instrumented |
| **3.3.6** Audit reduction & report generation *(partial)* | SQL-queryable persisted records | Provide reduction/reporting tooling |
| **3.3.7** Synchronized time stamps | ISO 8601 (Zulu) timestamps | Clock synchronization |
| **3.3.4** Alert on audit logging failure *(gap)* | Warns on model misconfiguration | Alerting on failures |
| **3.3.8** Protect audit information/tools *(gap)* | — | Access control + integrity protection |
| **3.3.9** Limit audit-log management to privileged users *(gap)* | — | Restrict log administration |

### CISA

CISA does not publish a numbered control catalog like NIST; the most relevant references are the **Cross-Sector Cybersecurity Performance Goals (CPGs)** and federal event-logging guidance (**OMB M-21-31**, which CISA supports).

| Reference | How this package supports it | Your responsibility |
|-----------|------------------------------|---------------------|
| **CPG 2.T — Log Collection** | Generates security-relevant logs with standardized, detailed content | Collect/centralize logs from all in-scope assets |
| **CPG 2.U — Secure Log Storage** *(partial)* | Optional durable database persistence | Ensure storage is access-controlled and tamper-resistant |
| **OMB M-21-31 event-logging maturity (EL1–EL3)** | Detailed, attributable, timestamped records advance basic logging maturity | Centralization, retention tiers, integrity, and access controls required by higher maturity |

### CIS Controls v8 — Control 8 (Audit Log Management) and related

| Safeguard | How this package supports it | Your responsibility |
|-----------|------------------------------|---------------------|
| **8.2 Collect Audit Logs** | Generates audit logs across the application | Enable collection everywhere in scope |
| **8.5 Collect Detailed Audit Logs** | Records event source, timestamp, user/identity, source address, and outcome | Ensure each event supplies the detail |
| **8.1 Audit Log Management Process** *(partial)* | A consistent logging mechanism to standardize on | Documented process, ownership, scope |
| **8.3 Adequate Audit Log Storage** *(partial)* | Durable DB persistence option | Provision capacity; protect storage |
| **8.4 Standardize Time Synchronization** *(partial)* | Consistent ISO 8601 timestamps | Synchronize clocks (NTP) |
| **8.9 Centralize Audit Logs** *(gap)* | Structured records ready to ship | Forward/centralize to a log aggregator/SIEM |
| **8.11 Conduct Audit Log Reviews** *(gap)* | Queryable records | Perform periodic reviews |
| **5.x / 6.x Account & Access Management** | Attributable account/access events | Operate account and access-control processes |

## Handling Sensitive Data

Audit records frequently become an unintentional store of sensitive data. To keep the package's records compliant with data-minimization and confidentiality requirements:

- **Do not put secrets or PII in `message`.** The message is written in plain text to the system log channel, which may be forwarded to third-party services.
- **Be deliberate with `attribute_value_old` / `attribute_value_new` and `metadata`.** These can capture changed values. Redact or tokenize sensitive attributes before logging.
- **Encrypt at rest where required.** Extend the base [`AuditLog` model](README.md#database-persistence) with your own class — add `encrypted` / `encrypted:array` casts to sensitive columns, and secure the underlying database.
- **Apply retention.** Persisted rows are not auto-purged; implement a retention/erasure job consistent with your data-retention and privacy obligations (e.g., GDPR/CCPA).

## Framework Versions & References

| Framework | Version referenced |
|-----------|--------------------|
| SOC 1 | SSAE 18 (AT-C 320) |
| SOC 2 | AICPA Trust Services Criteria (2017, incl. 2022 points of focus) |
| ISO/IEC 27001 | 2022 (Annex A) |
| NIST SP 800-53 | Revision 5 |
| NIST SP 800-63 | 800-63-3 (63A/63B) |
| NIST SP 800-171 | Revision 2 (Rev 3 numbering noted) |
| CISA | Cross-Sector CPGs; OMB M-21-31 |
| CIS Controls | Version 8 |

Control identifiers and requirement summaries are paraphrased for orientation. Consult the authoritative publications for exact wording and scope.
