# AI CRM

An AI-first SaaS CRM for capturing, qualifying, managing and following up with leads.

## Current MVP

The first working PHP + MySQL milestone now includes:

- Secure session login
- Dashboard with lead and pipeline metrics
- Lead creation and searchable lead list
- Sales pipeline board
- Follow-up date and notes fields
- AI score field ready for the scoring service
- CSRF protection on write/login forms
- PDO prepared statements

## Stack

- PHP 8.x
- MySQL 8.x / MariaDB-compatible SQL
- HTML + CSS
- PDO
- AI API integration planned as a service layer

## Architecture

```text
Browser
   |
   v
PHP CRM
   |
   +---- MySQL
   |
   +---- AI Service / LLM (next milestone)
   |
   +---- Email / WhatsApp / Calendar (later)
```

AI agents will use explicit application tools instead of unrestricted database access.

## Local setup

1. Clone the repository.
2. Create the database and tables:

```bash
mysql -u root -p < database/schema.sql
```

3. Configure database credentials with environment variables. Defaults are suitable for many local XAMPP setups:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ai_crm
DB_USER=root
DB_PASS=
```

4. Create the first admin user:

```bash
php scripts/create-admin.php "Admin" admin@example.com "change-this-password"
```

5. Point your web server document root to the `public/` directory, or run PHP's local server:

```bash
php -S localhost:8000 -t public
```

6. Open `http://localhost:8000/login.php` and sign in with the admin account.

## Database model

Core tables currently included:

- `users`
- `pipeline_stages`
- `leads`
- `activities`

## Next milestone

- Lead detail page and activity timeline
- Edit lead / change pipeline stage
- Tasks and reminders
- AI lead scoring service
- AI-generated sales suggestions
- Multi-tenant organization layer before SaaS launch

## Security notes

Never commit production database passwords or AI API keys. Keep secrets in server environment variables. The `public/` directory should be the web root so application/configuration files are not directly exposed.
