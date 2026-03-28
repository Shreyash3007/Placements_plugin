# FinTree Placements — Institutional Moodle Plugin 🏛️

A high-performance, enterprise-grade placement management system for Moodle, designed specifically for FinTree Education’s internship and job recruitment workflows.

## 🚀 Key Enterprise Features

### 1. High-Performance Architecture
*   **Asynchronous Processing**: Bulk operations (like shortlisting 100+ candidates) are offloaded to Moodle's native Adhoc Task engine. The UI remains lightning-fast, while WhatsApp and Google Sheets sync happen in the background.
*   **MVC Mustache Templating**: UI logic is decoupled from backend business logic using Moodle's modern template engine, ensuring a sleek, consistent, and easily maintainable interface.

### 2. Institutional Compliance & Logging
*   **Native Event Tracking**: Every application submission and shortlist action is logged natively in Moodle's "Site Administration > Reports > Live Logs".
*   **Deep Security**: Bulletproof protection against CSRF and unauthorized access via `require_sesskey()` and granular Moodle Capability checks (`local/finplacements:admin` vs `local/finplacements:candidate`).

### 3. Smart Recruitment Logic
*   **Automated Fit Scoring**: Algorithmic scoring based on CFA/FRM levels, experience, and academic background.
*   **Reliability Tracking**: Dynamic candidate reliability scores that penalize no-shows and reward placements, preventing "ghosting" in the recruitment pipeline.

## 🛠️ Configuration

1.  **WhatsApp Integration**: Go to *Site Administration > Plugins > Local Plugins > FinTree Placements* to configure your WhatsApp Business API endpoint.
2.  **Google Sheets Sync**: Add your Apps Script Webhook URL in the settings to automatically pipe shortlisted candidates to your master recruiter spreadsheet.

## 📁 Structure
*   `classes/`: PSR-4 compliant logic (Scoring, Sync, Managers, Tasks).
*   `db/`: Database schema, capabilities, and task registration.
*   `templates/`: Mustache UI components.
*   `lang/en/`: Human-readable English strings for all interface elements.

---
**Institutional Grade. Structurally Unbreakable. Distribution Ready.**
