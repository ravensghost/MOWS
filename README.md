<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/ravensghost/MOWS/actions"><img src="https://github.com/ravensghost/MOWS/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://packagist.org/packages/laravel/framework/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT"></a>
</p>

# MOWS Engine (Modular Online Writing System)

MOWS Engine is a full-stack, self-hosted web application built for managing collaborative text roleplaying platforms, custom publishing workflows, structured character rosters, and dynamic encyclopaedic content. Designed with a strict modular architecture, it bridges traditional Laravel backend capabilities with a responsive, asynchronous frontend ecosystem.

---

## Architecture & Technology Stack

### Backend Infrastructure
- **Framework:** Laravel (running on PHP with Laravel Herd/DBngin local development environments).
- **Authentication:** Laravel Breeze[cite: 3].
- **AI Integration:** Laravel AI SDK (`laravel/ai`) alongside custom modular services (`AiCore`, `AiTranslationService`, `AiLoreService`, `AiContentService`)[cite: 3].
- **Markdown & Sanitisation:** `league/commonmark` for Markdown parsing; `SteveBauman\Purify` for backend HTML filtering[cite: 3].

### Frontend Stack & DOM Handling
- **Core Engine:** **Alpine.js** for reactive component states and modals[cite: 3].
- **Rich Text & Forms:** **TipTap** (with StarterKit), **Tom Select** for enhanced dropdowns, and **Flatpickr** for date/time selection[cite: 3].
- **UI Feedback & Media:** **SweetAlert2** for notifications/modals, **Dropzone** for file ingestion, **GLightbox** for image viewing, and **Tippy.js** (`x-tooltip`) for contextual tooltips[cite: 3].
- **Utilities:** **SortableJS** for drag-and-drop reordering, **Chart.js** for analytics, **Fuse.js** for client-side fuzzy searching, **Clipboard.js**, and **DOMPurify**[cite: 3].

#### Global DOM & Sanitisation Helpers
- `window.updateDOM` / `window.refreshDOM`: Runs `DOMPurify.sanitize()` (strips Alpine attributes and scripts; strictly intended for user-generated content)[cite: 3].
- `window.updateDOM_US` / `window.refreshDOM_US`: Unsafe/raw methods that bypass sanitisation and preserve Alpine bindings (strictly for trusted internal admin UI elements and managers)[cite: 3].

---

## Core Architectural Constraints

- **CSS Architecture:** Tailwind CSS is strictly forbidden. All styles adhere to a modular three-file architecture (`visual.css`, `layout.css`, and `theme.css`) compiled via Vite, which also bundles CSS for third-party libraries[cite: 3].
- **Language & Spelling Conventions:** British English (UK) spelling conventions are enforced across all code comments and documentation (e.g., *optimise*, *categorise*, *colour*)[cite: 3].
- **Asynchronous CRUD:** All administrative create, update, and delete actions execute asynchronously via AJAX using Alpine modals and SweetAlert2 without full-page reloads[cite: 3].
- **Lexicon Key Auto-Discovery:** The lexicon database system automatically registers and caches semantic lexicon tokens at runtime via `lexicon('code_key', 'fallback_text')`, eliminating manual translation row seeding[cite: 3].

---

## Hosting Environment & Production Targets

Designed for constrained shared hosting environments (such as **InfinityFree**), the engine complies with strict resource boundaries:
- **File Size Caps:** Max **1 MB** per core script/source file; max **10 MB** per media asset; max **10 kB** for `.htaccess`[cite: 3].
- **Storage & Inodes:** Capped at **5 GB total storage** and **~30,000 Inodes** (node_modules and test suites must be excluded from production)[cite: 3].
- **Database Limits:** Max **50 MB** per database with a tight MySQL max allowed packet size (~3 MB). Migrations and payloads avoid massive single-query dumps[cite: 3].

---

## Custom Artisan CLI Command Reference

MOWS Engine includes a robust suite of custom maintenance and auditing commands:

| Command Group | Signature / Options | Description |
| :--- | :--- | :--- |
| **Logs & Maintenance** | `php artisan log:rotate` | Rotates application and browser log files[cite: 2]. |
| **Database & Schema** | `php artisan audit`<br>`php artisan audit --all-details`<br>`php artisan audit <table> --bundle` | Audits database schemas, Eloquent drift, and structure bundles (`audit_bundle.txt`)[cite: 2]. |
| **Naming & Jobs** | `php artisan audit:naming`<br>`php artisan audit:naming <domain> --bundle` | Audits vertical naming conventions and job architectures (`naming_audit_bundle.txt`)[cite: 2]. |
| **Alpine.js & Blade** | `php artisan audit:js`<br>`php artisan audit:js <manager> --bundle` | Audits Alpine.js managers and Blade synchronisation (`js_audit_bundle.txt`)[cite: 2]. |
| **CSS Architecture** | `php artisan audit:css`<br>`php artisan audit:css --unused` | Audits custom CSS rules and unused selectors (`css_audit_bundle.txt`)[cite: 2]. |
| **Lexicon Tokens** | `php artisan audit:lexicon`<br>`php artisan audit:lexicon <path>` | Audits semantic lexicon token references (`lexicon_audit.txt`)[cite: 2]. |
| **Codebase Mapping** | `php artisan audit:map`<br>`php artisan audit:map --tree-only` | Generates functional X-Rays, file layout trees, and documentation prompts[cite: 2]. |
| **Codebase Exporter** | `php artisan export:codebase all --compact-icons`<br>`php artisan export:codebase schema` | Exports master codebase snapshots, SQL dumps, or schema manifests[cite: 2]. |
| **Routes & Tests** | `php artisan audit:routes`<br>`php artisan audit:tests --missing` | Audits administrative routes, controller CRUD endpoints, and PHPUnit test coverage[cite: 2]. |

---

## Local Development Setup

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/ravensghost/MOWS.git](https://github.com/ravensghost/MOWS.git)
   cd MOWS
   Install PHP dependencies:

2. **Install PHP dependencies:**
    ```bash
    composer install
    Install JavaScript dependencies:

3. **Install JavaScript dependencies:**
    ```bash
    npm install
    Configure Environment:
4. **Configure Environment:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    Run Migrations & Seeders:
5. **Run Migrations & Seeders:**
    ```bash
    php artisan migrate --seed
    Compile Assets & Start Local Server:

6. **Compile Assets & Start Local Server:**
    ```bash
    npm run dev
    
## Contributing
Contributions are welcome! Please review the contribution guidelines and code of conduct before submitting pull requests.

## License
This project is open-sourced software licensed under the MIT license (chosen for maximum open-source accessibility and compatibility with AI-assisted software generation frameworks)