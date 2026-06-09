# Contact Finder — Implementation Notes

## Pipeline (5 steps)

1. **Ingest** — Read `company_name` + `mailing_address` from the CSV or API request.
2. **Query mock providers** — Load `registry`, `listing`, and `enrichment` signals from `challenge/mocks/enrichment_responses.json` (no live APIs).
3. **Reconcile** — Normalize roles, fuzzy-match names across sources (initials, nicknames), and pick the highest-priority decision-maker persona.
4. **Score** — Combine independent source weights, cross-source agreement, phone corroboration, and penalties for conflicts, generic mailboxes, and registered-agent-only rows.
5. **Publish** — Return provenance-backed output; rows below confidence `70` keep name/role context but clear `contact_email_or_phone` and set `needs_human_review = true`.

## Why these sources

The three mocks mirror production sources we would combine in real life:

- **registry** — state business filings (authoritative owner/agent, often missing for micro-businesses)
- **listing** — maps/web listings (phone-rich, names often partial)
- **enrichment** — email/phone vendors (contactable channel, variable quality)

Each is independently fallible, which forces cross-checking instead of trusting a single guess.

## Next 30 minutes

- Persist findings + suppression list in DB with full provenance audit trail
- Queue batch processing with per-provider retry/backoff
- Add opt-out flag on company records and skip suppressed accounts
- Export reviewer queue for `needs_human_review` rows only
- Tune scoring weights from labeled reviewer feedback

## `confidence_score` formula

Starting at 0, add:

| Signal | Points |
|--------|--------|
| Registry returns a person name | +25 |
| Listing returns a phone | +15 |
| Enrichment `provider_confidence` | +35% of provider score |
| 2 agreeing sources | +15 |
| 3 agreeing sources | +25 |
| Listing phone matches enrichment phone | +10 |
| Enrichment email matches reconciled name | +5 |
| Role is a decision-maker persona | +5 |

Subtract / cap:

| Condition | Effect |
|-----------|--------|
| Registry vs listing name conflict | −25 |
| Role is only Registered Agent | −10 |
| Generic mailbox (`info@`, `office@`, `sales@`, …) | −15 |
| Enrichment-only with provider confidence < 50 | cap at 45 |
| No email or phone available | cap at 40 |
| No mock data at all | score = 0 |

Threshold: **70** (from `CLARIFICATIONS.md`). Below threshold → `contact_email_or_phone = ""`, `needs_human_review = true`.
