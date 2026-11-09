# Full internal-page UX scope — NextFix Business

User goal: improve the internal pages across Admin and Company/Provider/Handyman apps using a consistent colorful, photo-rich, fewer-taps experience. Theme switching must retain the existing backend. This is an implementation backlog, not a statement that all modules below are finished.

## Interaction rules

One clear primary action per screen, secondary actions in context, status always paired with text, no hidden critical charge, no surprise navigation or silent mutation. Keep the selected unit and service as the user advances. Preserve drafts/errors. Scope all records and actions to existing role policies. Use at least 44px touch controls, visible keyboard focus, semantic forms, labelled upload controls, reduced-motion support and responsive layouts. Never reduce financial authorisation to an unconfirmed tap.

| Area | Smart Tap UX target | Preserve |
|---|---|---|
| Company dashboard | Cleaning/Maintenance photo tiles, manual/swipe slider, current unit context, next booking and approvals | Actual counts and existing routes |
| Company units | Search, building/type filters, photos, persistent selected unit, service shortcut | Unit/company isolation |
| Company booking | Unit → service photo → date/time → review/payment, inline price summary, back without losing data | Agreed rate, availability, server totals |
| Company quotations | Evidence, issue list and itemised cost, approve/pay or ask for changes, explicit inspection credit | Existing quoted version and credit logic |
| Company rates | Clear pending/accepted state, comparison, explicit acknowledgement | Existing rate-card acceptance |
| Company profile | Grouped fields, completeness hints, validation and save feedback | Business/legal details and permissions |
| Booking lists/details | Status chips, search and date filters, per-record next action, timeline | Status transition rules |
| Chat | Booking context, attachment progress, retry, unread state and clear send feedback | Participant authorisation |
| Handyman home | Next job first, availability, route/access context, new assignment alerts | Existing professional role |
| Handyman job | Arrive → before photos → checklist/findings → after photos → report; recoverable uploads | Assigned-job access and actual approval |
| Evidence/camera | Clear permission states, retake/retry, upload progress, required-photo indication | Private evidence endpoint |
| Reports | Before/after viewer, completed scope, exceptions, feedback and rework | Report/PDF accuracy |
| Admin dashboard | Operational metrics, needs-attention queue and focused shortcuts | Accurate server data |
| Admin operations | Skill/time-aware dispatch, filters, assignment drawer, estimate review | Availability and booking policies |
| Admin companies | Verification queue, profiles, grouped editing and rates | Company isolation and finance authority |
| Admin workforce | Staff role/skills/status, availability and assignment context | Existing professional model |
| Admin finance | Itemised invoices, payment/reconciliation state, controlled refunds | Nomod verification and audit history |
| Admin settings | Theme preview/publish/restore, integrations and system update status | Secret handling and admin guards |
| Notifications | Meaningful groups, unread state, actionable authorised deep links | Existing delivery preferences/access |
| Auth/recovery | Accessible sign-in/reset states, no accidental session loss | Existing security/session recovery |
| Provider app | Separate verified-provider workspace, capacity/dispatch/quote/quality/settlement flow | Requires backend scope definition; do not fake a role |

## Delivery gates

1. Preserve a working Classic appearance and test the additive Theme Manager (draft code included).
2. Finish shared visual components and local photo assets; preview representative Company booking, Worker job and Admin operations screens.
3. Apply those components page by page to the full inventory above; verify forms and edge states rather than copying static SVGs as functional UI.
4. Test role isolation, actual bookings/quotes/payment failures, stale approvals, uploads, keyboard/touch, dark-mode readability and installed PWA refresh.
5. Publish only after a reviewed staging run. Keep a reversible release/database backup. Never replace live records with illustrative design data.
