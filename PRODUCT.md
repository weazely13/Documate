# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Inferred from the codebase (not yet confirmed by interview):

- **Students** submit document requests and clearance forms, upload supporting photos, book appointments, and track where each document sits in its approval chain.
- **Student officers** tag clearance for their organization.
- **Signatories** (school offices, guidance counselor, VPSD) review and digitally sign documents from a signing inbox; external signers sign through an emailed link without an account.
- **Admins** (OSDAS staff) run the workflow review queue, templates, users, semesters, reports, feedback, and the audit trail.

## Product Purpose

DocuMate replaces paper routing of student clearance and document requests at the university (LNU, OSDAS office). A request moves from submission through AI-assisted verification and a defined chain of signatories to a sealed, completed record.

## Positioning

Every approval is cryptographically sealed (HMAC), and a completed transaction is immutable; the only transition after completion is void. Verification of uploaded photos is done by AI (Gemini) before people sign.

## Operating Context

Daily, task-focused use on school and personal computers and on phones (students). Office staff work long sessions in queues and tables; students visit in short bursts to check status. Outputs include printable PDFs, spreadsheet exports, and QR-verifiable documents.

## Capabilities and Constraints

- Laravel 13 + Livewire 3, Blade views, Tailwind CSS 3 compiled by Vite, Alpine.js, Boxicons, Chart.js.
- Roles: Student, Officer, Admin, Signatory (by assignment), External signer.
- Terminology: transaction, workspace, template, signatory step, approval, clearance, slip, appointment.
- Transaction statuses: Draft, Under Review, Submitted, Approved, Completed, Voided. Approval statuses: Pending, Approved, Rejected.

## Brand Commitments

- Name **DocuMate**, its logo (`public/images/DocumateLogo.png`) and the blue brand color `#2A57B4` (confirmed by the user to be kept).
- Institution marks shown in the app: LNU logo and seal, OSDAS logo (`public/images/`).
- The user chose a visual direction combining the Notion and Stripe design languages from awesome-design-md: Notion as the base, Stripe for the details, DocuMate blue as primary.

## Evidence on Hand

Real institution logos and the DocuMate logo in `public/images/`. No testimonials, statistics, or usage claims exist; none may be invented.

## Product Principles

- Status is always legible: a student should know where a document is and what is needed next without asking an office.
- Trust comes from integrity: sealed, immutable records are shown plainly, not decorated.
- Office work is dense and repetitive; screens favor scanability over ornament.
