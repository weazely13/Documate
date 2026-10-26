# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# First-time setup
composer run setup

# Development (starts Laravel server, queue listener, log streaming, and Vite concurrently)
composer run dev

# Run tests (clears config cache first, then PHPUnit)
composer run test

# Run a single test file
php artisan test --filter=TestClassName
# Or directly:
./vendor/bin/phpunit tests/path/to/TestFile.php

# Build frontend assets for production
npm run build

# Database migrations
php artisan migrate

# Queue worker (for processing document analysis jobs)
php artisan queue:listen --tries=1 --timeout=0

# Real-time log streaming
php artisan pail

# Code formatting (Laravel Pint)
./vendor/bin/pint
```

**Docker:**
```bash
docker build -t documate .
docker run -p 8080:8080 documate
# Automatically runs migrations, queue workers, and schedules on startup
```

## Architecture Overview

DocuMate is a **Laravel 13 + Livewire 3** document management and workflow system for student document clearance, verification, and approval. The UI is fully server-driven via Livewire components (no separate frontend API).

### Request Flow

```
Route (routes/web.php) → Livewire Component or Controller
  → Service Layer (app/Services/)
  → Eloquent Model (app/Models/)
  → Database (MySQL prod / SQLite dev)
  → Job Queue (if async AI analysis needed)
```

### Role-Based Structure

The app has three user roles (Student, Officer, Admin), each with its own Livewire component namespace:
- `app/Livewire/Student/` — document submission, appointments, verification
- `app/Livewire/Admin/` — dashboards, template management, user management, reports
- `app/Livewire/Officer/` — clearance tagging
- `app/Livewire/Signatory/` — approval/signing workflows
- `app/Livewire/External/` — public-facing signing page (no auth required)
- `app/Livewire/Auth/` — login, register, password reset

Route protection uses `role:student,officer` middleware and a custom `VerifiedStudent` middleware.

### Core Document Workflow

1. **Student submits** a form based on a `Template` → creates a `StudentDocumentWorkspace`
2. **AI verification**: A `ProcessDocumentAnalysis` job (async) sends the uploaded supporting photo to Gemini API (OCR + field extraction + validation)
3. **Approval chain**: The template defines `TemplateSignatoryStep` records. Each step has an assigned `SignatoryRole` → `SignatoryAssignment` (a user). Each signer gets an email invite.
4. **Signing**: Signers submit digital signatures → `TransactionApproval` records are created and **sealed** using HMAC-SHA256 (`DOCUMATE_SEAL_KEY`)
5. **Completion**: Once all steps approved, workspace status → `COMPLETED` (immutable — the `HasTransactionWorkflow` trait enforces this)

### Document Integrity (Sealing)

Every approval is cryptographically sealed:
- `Seal::contentHash($workspace)` — SHA hash of form data
- `Seal::sealApproval($approval)` — HMAC of approval data using `$DOCUMATE_SEAL_KEY`
- Once `COMPLETED`, a transaction **cannot be edited**. The only allowed transition is `COMPLETED → VOIDED`.

### AI Processing

- **Gemini API** (configured in `config/services.php`): handles document OCR, e-slip analysis, and field matching
- **Groq API**: LLM fallback
- The `ProcessDocumentAnalysis` job uses `WithoutOverlapping` middleware — only one AI analysis job runs at a time to respect API rate limits; others are queued and retried after 8 seconds
- Job results are tracked via `DocumentScanEvent` (statuses: scanning, failed, rejected, approved)

### Key Service Classes

| Service | Responsibility |
|---|---|
| `DocumentAnalysisService` | Orchestrates AI doc verification |
| `GeminiOCRService` | Google Gemini API integration |
| `ChatbotService` | Chat-based student assistance |
| Services in `Services/Workflow/` | Signing, sealing, approval progression |

### Configuration

Custom app config lives in `config/documate.php`:
- `seal_key` — HMAC key for document sealing (`DOCUMATE_SEAL_KEY` env)
- `external_expiry_days` — validity of external signer email invite links (default: 7)
- `workflow.require_signature` — toggle signature requirement

Third-party API keys are in `config/services.php` (Gemini, Groq, Brevo).

### Queue & Jobs

- Queue driver: **database** (no Redis required)
- Failed jobs stored in `failed_jobs` table
- Key jobs: `ProcessDocumentAnalysis`, `ReviewAppointmentSubmission`
- The `docker/entrypoint.sh` starts queue workers automatically in Docker

### Database

- Production: MySQL (`DB_CONNECTION=mysql`)
- Development: SQLite (`database/database.sqlite`)
- 30+ migrations in `database/migrations/`
- Key enums: `ApprovalStatus` (Pending/Approved/Rejected), `TransactionStatus` (Draft/UnderReview/Submitted/Approved/Completed/Voided)

### PDF & Document Generation

- PDF generation: `barryvdh/laravel-dompdf` — templates in `resources/views/pdf/`
- Spreadsheet exports: `phpoffice/phpspreadsheet` — templates in `resources/views/exports/`
- QR codes: `endroid/qr-code`
