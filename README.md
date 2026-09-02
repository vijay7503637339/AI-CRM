# AI CRM

A PHP + MySQL AI-first SaaS CRM for capturing, qualifying, managing and following up with leads.

## Working MVP

- Secure session login/logout
- Dashboard with sales metrics
- Lead creation and searchable lead list
- Lead detail page
- Activity timeline with notes, calls, email, WhatsApp, meetings and tasks
- Pipeline stage changes with system activity history
- Explainable baseline AI lead scoring (AI API-ready service boundary)
- CSRF protection and PDO prepared statements
- Responsive UI

## Stack

- PHP 8.x
- MySQL 8.x / MariaDB-compatible SQL
- HTML + CSS
- PDO
- AI service layer for future LLM integration

## Local setup

1. Clone the repository.
2. Create the database:

```bash
mysql -u root -p < database/schema.sql
```

3. Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the server environment. Local XAMPP defaults are supported.
4. Create an admin:

```bash
php scripts/create-admin.php "Admin" admin@example.com "change-this-password"
```

5. Run:

```bash
php -S localhost:8000 -t public
```

6. Open `/login.php`.

## AI direction

The current scorer is deterministic and explainable so the CRM works without an AI API key. It can be replaced behind the same service boundary with an LLM provider. AI agents will receive bounded CRM context and explicit tools such as `get_lead`, `update_lead`, `create_task`, `create_note`, and `draft_followup` rather than unrestricted database access.

## Roadmap

1. CRM core: edit lead, tasks/reminders, contacts, filters
2. AI: LLM lead scoring, qualification and sales suggestions
3. Automation: email/WhatsApp drafts and approved sending workflows
4. SaaS: organizations, roles, usage metering, billing and tenant isolation
