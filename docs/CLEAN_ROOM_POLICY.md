# HTSMS Clean-Room Development Policy

**Effective date:** 21 July 2026  
**Applies to:** Every employee, contractor, contributor, reviewer, and AI-assisted development workflow working on HTSMS

## Purpose

HTSMS must remain proprietary. It therefore cannot be a fork, modification, translation, derivative work, or concealed reuse of httpSMS/httpsms or another copyleft implementation.

## Permitted inputs

- HTSMS requirements and designs created for CM-EA.
- Publicly observable product behaviour.
- Public API documentation and interoperability requirements.
- Android, Kotlin, PHP, Laravel, PostgreSQL, Redis, Firebase, HTTP, cryptography, and telecommunications standards/documentation.
- Dependencies whose licences have been reviewed and accepted for proprietary distribution.
- Independently created algorithms, schemas, protocols, UI designs, tests, and source code.

## Prohibited actions

- Copying or modifying source from the `NdoleStudio/httpsms` repository.
- Referring to that source while implementing equivalent HTSMS components.
- Decompiling, disassembling, extracting, or inspecting the httpSMS APK or hosted application assets.
- Translating code between programming languages or frameworks.
- Copying private identifiers, comments, tests, database schemas, internal protocols, UI assets, or implementation-specific error text.
- Introducing an AGPL/GPL dependency without written licence approval.
- Asking a contributor or automated tool to recreate code after providing it with prohibited source.

## Allowed compatibility work

HTSMS may offer familiar SMS concepts and conventional REST operations. Any compatibility requirement must be written as an implementation-neutral specification before coding. HTSMS uses its own resource names, schemas, device protocol, security design, and source code.

## Contributor declaration

Every contributor must confirm that their contribution:

1. Is their original work or uses an approved dependency.
2. Was not produced by copying or adapting prohibited source.
3. Can be licensed to CM-EA for proprietary use.
4. Does not knowingly introduce licence obligations incompatible with the proprietary product decision.

## Dependency review

Before adoption, record the dependency name, version, source, licence, runtime/distribution role, known obligations, and approval. Composer, npm, Gradle, and Android dependency inventories must be retained with every release.

## Evidence retained

- Dated product requirements and architecture decisions.
- Original wireframes, API contracts, and protocol specifications.
- Pull-request history and contributor attestations.
- Dependency lock files and software bills of materials.
- Test specifications and release artefacts.

Suspected contamination stops affected development until the technical and legal owners complete a review.

