# Bulk Email Dispatch System

A production-grade **Gmail-based bulk email dispatch system** designed for controlled, quota-aware email sending using multiple "Shooter" Gmail accounts. Built with **Laravel 12** and **Livewire v3**.

---

# Project Overview

This application manages the distribution of bulk emails by leveraging multiple Gmail accounts (referred to as "Shooters") instead of a traditional SMTP server or transactional email service (like SES or Mailgun).

**Real-world Use Case:**  
Organizations that need to reach a large audience (cold outreach, newsletters, updates) often face low deliverability or account bans when using standard mass-mailing tools. By rotating through multiple established Gmail accounts and adhering to strict daily limits, this system ensures high deliverability and account safety.

**Problems Solved:**
*   Avoids "Spam" folder placement by using high-reputation personal/business Gmail accounts.
*   Bypasses single-account sending limits by spreading the load across many accounts.
*   Automates the complexity of OAuth token management and quota tracking.

# Business Motivation

### Why Multiple "Shooters"?
Gmail accounts have strict daily sending limits (e.g., 500-2000 emails/day). To send 10,000 emails a day, you cannot use a single account. This system orchestrates a "fleet" of Gmail accounts to achieve scale without triggering abuse detection.

### Why Quotas & Date-based Assignments?
Sending too many emails too quickly triggers Google's anti-abuse filters.
*   **Quotas:** We enforce a hard "Daily Quota" per shooter (e.g., 50 emails/day) to keep activity "human-like."
*   **Assigned Dates:** Mappings are pre-calculated for specific dates, ensuring we know exactly how many emails will go out on any given day, preventing unexpected spikes.

### Why Gmail API over SMTP?
SMTP authentication is less secure and increasingly blocked by Google (requires "Less Secure Apps" which is being deprecated). The **Gmail API** uses OAuth2.0, which is the modern, secure, and officially supported method for programmatic access, allowing detailed error handling and token management.

# Core Concepts & Terminology

*   **Shooter**: A sender account (Gmail). It has a refresh token, a daily quota, and a status (Active/Disabled).
*   **Target**: A recipient (email address, name, metadata).
*   **Mapping**: The link between a Shooter and a Target for a specific date. A mapping row means: *"Shooter A will email Target B on [Date] using Template C."*
*   **Assigned Date**: The specific calendar date an email is scheduled to be sent.
*   **Email Template**: The content (Subject/Body) used for the email.
*   **Quota**: The maximum number of emails a Shooter is allowed to send in 24 hours.
*   **Mapping Status Lifecycle**:
    1.  `assigned`: Ready to be processed.
    2.  `sent`: Successfully dispatched via Gmail API.
    3.  `failed`: Error occurred (token expired, quota exceeded, etc.).

# System Architecture (High Level)

The system is designed with a clear separation between the **Management UI** and the **Sending Engine**.

1.  **Preparation (UI/Logic):** Targets are imported. A background process (or user action) creates **Mappings** by distributing Targets across available Shooters based on their quotas.
2.  **Scheduling:** Mappings are stored in the database with an `assigned_date`.
3.  **Dispatch (Cron):**
    *   A scheduled job runs every minute/hour (`emails:send-mapped`).
    *   It fetches `assigned` mappings for **today**.
    *   It renders the email using the assigned `Template`.
    *   It pushes the email to Google via the Gmail API.
4.  **Feedback Loop:**
    *   If successful, the Mapping and Target are marked `sent`.
    *   If failed (e.g., token error), the system logs the error and marks the Mapping `failed`.

**Role of Livewire UI:** Used for managing Shooters, Targets, Templates, and viewing Reports. It provides a reactive, single-page-app experience without full page reloads.

# Tech Stack

*   **Framework:** Laravel 12.x
*   **UI Framework:** Livewire v3.x (Tailwind CSS)
*   **Database:** MySQL / SQLite (configurable)
*   **APIs:** Google Gmail API (via `google/apiclient`)
*   **Excel/CSV Processing:** `spatie/simple-excel`

# Local Setup Guide

Follow these steps to get the project running locally.

1.  **Clone Project**
    ```bash
    git clone https://github.com/your-username/bulk-mailer.git
    cd bulk-mailer
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    npm install
    ```

3.  **Environment Configuration**
    Copy the example env file and update your database credentials.
    ```bash
    cp .env.example .env
    ```
    *Ensure your DB_CONNECTION matches your local setup.*

4.  **Generate App Key**
    ```bash
    php artisan key:generate
    ```

5.  **Run Migrations**
    ```bash
    php artisan migrate
    ```

6.  **Storage Link** (Optional, for public assets)
    ```bash
    php artisan storage:link
    ```

7.  **Serve Application**
    Run the dev server (Vite + Laravel):
    ```bash
    npm run dev
    ```

    In a separate terminal, run the queue worker (if using queues):
    ```bash
    php artisan queue:listen
    ```

8.  **First Time Login**
    After running the migrations (which includes the setup seeder), you can login with the following credentials:
    *   **Email:** `test@example.com`
    *   **Password:** `password`
    *   **Role:** `super_admin`

### Example .env Keys
(Ensure these are present in your `.env`)

```ini
APP_NAME="Bulk Mailer"
APP_URL=http://localhost:8000

#DB_CONNECTION=sqlite
# OR
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bulk_mailer
DB_USERNAME=root
DB_PASSWORD=

# Google Project Credentials
GOOGLE_GMAIL_CLIENT_ID=
GOOGLE_GMAIL_CLIENT_SECRET=
GOOGLE_GMAIL_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

# User Roles & Access Control

*   **super_admin**: Full access to all modules. Can manage other users and system settings.
*   **admin**: Can manage Shooters, Targets, and view Reports. Restricted from critical system configuration.

# Module Overview

### Users Management
Manage dashboard access. Supports creating/editing admins and assigning roles.

### Shooters (Gmail Accounts)
Add and monitor Gmail accounts.
*   **Status Indicators**: See at a glance if a token is Expired or Disconnected.
*   **OAuth Connection**: "Connect" button redirects to Google to authorize the app.

### Targets
The recipient list. Supports CSV upload. Targets are the "inventory" needed to be mapped.

### Shooter-Target Mapping Engine
The core logic that pairs available Shooters with Targets for specific dates. It respects the `daily_quota` of each Shooter to prevent over-sending.

### Email Templates
CRUD for email content. Supports basic variable substitution (e.g., `{{ name }}`) if implemented in the render service.

### Reports & Dashboard
Visualizes:
*   Emails Sent Today vs Total
*   Shooter Health (how many are active/broken)
*   Mapping Progress

### Cron-based Email Sending
The silent worker. Executes `php artisan emails:send-mapped` to process the queue.

---

# Google Gmail API Integration Guide

This application relies on a Google Cloud Project to communicate with Gmail servers.

### 1. Create Google Cloud Project
1.  Go to [Google Cloud Console](https://console.cloud.google.com/).
2.  Create a **New Project** (e.g., "Bulk Mailer System").

### 2. Enable Gmail API
1.  In the project dashboard, go to **APIs & Services > Library**.
2.  Search for **Gmail API**.
3.  Click **Enable**.

### 3. OAuth Consent Screen Setup
1.  Go to **APIs & Services > OAuth consent screen**.
2.  **User Type**: Select **External** (unless you are in a G-Suite workspace organization).
3.  Fill in app details (Name, Support Email).
4.  **Scopes**: Add the scope `https://www.googleapis.com/auth/gmail.send`.
    *   *Note: This specific scope allows sending emails on behalf of the user.*
5.  **Test Users**: Add the Gmail addresses of your "Shooters" here so they can authorize the app while it's in "Testing" mode.

### 4. Create OAuth Client ID
1.  Go to **APIs & Services > Credentials**.
2.  Click **Create Credentials** -> **OAuth client ID**.
3.  **Application Type**: **Web application**.
4.  **Name**: e.g., "Bulk Mailer App".
5.  **Authorized Redirect URIs**:
    *   Local: `http://localhost:8000/auth/google/callback`
    *   Prod: `https://your-domain.com/auth/google/callback`
6.  Click **Create**.
7.  Copy the **Client ID** and **Client Secret**.

### 5. Environment Configuration
Paste the credentials into your `.env` file:
```ini
GOOGLE_GMAIL_CLIENT_ID=your_client_id_here
GOOGLE_GMAIL_CLIENT_SECRET=your_client_secret_here
GOOGLE_GMAIL_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### 6. Connecting a Shooter
1.  Login to the Bulk Mailer Dashboard.
2.  Go to **Shooters** -> **Add New**.
3.  Enter the name/description.
4.  Click **Connect Gmail**.
5.  You will be redirected to Google. adhere to the consent screen steps.
6.  Once approved, you'll be redirected back, and the Shooter will show as **Connected**.

### 7. Token Storage & Lifecycle
*   **Storage**: We store `gmail_access_token` and `gmail_refresh_token` in the `shooters` table.
*   **Encryption**: The `refresh_token` is encrypted at rest (handled by Laravel Eloquent casts).
*   **Refresh Logic**: The Google Client automatically refreshes the access token using the refresh token when it detects expiration during the send process.
*   **Revocation**: If a user changes their password or revokes access in Google Security settings, the token becomes invalid. The system marks the Shooter as `disconnected` upon the next failed send attempt.

---

# Cron & Email Sending Flow

The command `php artisan emails:send-mapped` drives the system.

1.  **Selection**: Selects up to 50 `assigned` mappings scheduled for `today`.
2.  **Validation**:
    *   Checks if Shooter `gmail_refresh_token` exists.
    *   Checks if Shooter `daily_quota` has been reached *today*.
3.  **Process**:
    *   Renders email content.
    *   Initializes Google Client with the Shooter's credentials.
    *   Sends email.
4.  **Success**:
    *   `shooter_target_mappings.status` -> `sent`
    *   `shooters.sent_today` incremented.
5.  **Failure Handling**:
    *   If token is invalid: Log warning, mark Mapping `failed`, and potential logic to disable Shooter.
    *   If quota exceeded: Skip this shooter for the rest of the run.
    *   General error: Log error, mark Mapping `failed`.

**Logging**: All activities are logged to `laravel.log`.

---

# Security Notes

*   **Token Encryption**: Critical OAuth tokens are stored using Laravel's encryption. Even with DB access, they cannot be used without the `APP_KEY`.
*   **Safe Attachments**: Attachments should be stored on a private disk (e.g., `local` or private S3 bucket) and streamed to the Gmail API, ensuring they aren't publicly accessible via URL.
*   **Role-Based Access**: Strict separation ensures only authorized admins can export data or modify configuration.
*   **API vs SMTP**: By using Gmail API, we avoid storing the user's actual Gmail password. We only hold a revocable token.

---

# Known Limitations & Scaling Notes

*   **Gmail Daily Limits**: Personal Gmail accounts usually cap at 500 emails/day. Workspace accounts higher (2,000). Always set your system `daily_quota` conservatively (e.g., 50-100) to "warm up" accounts.
*   **Token Expiry**: Refresh tokens can expire if not used for 6 months, or if the user status changes. The system requires manual re-connection in these cases.
*   **Batching**: Complex high-volume sending should be batched. The current cron processes 50 emails per minute/execution to avoid timeouts.
*   **Database**: For > 100k Mappings, ensure proper indexing on `shooter_target_mappings` (`status`, `assigned_date`, `shooter_id`).
