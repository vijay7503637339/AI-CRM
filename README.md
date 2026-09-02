# WebStripe AI CRM

PHP 8.1+ / MySQL 8+ CRM foundation for WebStripe Technologies.

## CRM modules
- Secure session login
- Dashboard with sales KPIs, upcoming follow-ups and quick actions
- Lead CRUD with search, stage and AI-priority filters
- Lead detail, editing and activity timeline
- Sales pipeline: New → Contacted → Qualified → Proposal → Won/Lost
- Tasks & follow-ups with due dates, priorities, assignment and completion
- Sales analytics: win rate, pipeline value, won revenue and lead-source performance
- AI lead scoring and AI assistant with optional OpenAI integration
- Lead campaigns and web prospecting
- Automatic lead-capture webhook with duplicate-event protection

## Production deployment (cPanel)
1. Keep the application under a protected project folder and expose `public/` through your domain or the project `.htaccess`.
2. Configure the database connection through server environment variables or your local server-only configuration.
3. Do **not** commit database passwords, OpenAI keys or webhook secrets.
4. After pulling a version that introduces a migration, run that migration once against the existing CRM database.

For the current release, run from the project directory:
```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/migrations/005_crm_productivity.sql
```

The migration adds the `tasks` table required by the dashboard, Tasks page and Analytics page.

## Local setup (XAMPP)
1. Create a MySQL database by importing `database/schema.sql` in phpMyAdmin.
2. Configure database credentials in the server environment/local config.
3. Set `LEAD_CAPTURE_KEY` in the server environment.
4. From the project folder run: `php -S localhost:8000`
5. Create an admin: `php tools/create_admin.php "Admin" "admin@example.com" "ChangeMe123!"`
6. Open `http://localhost:8000`.

## AI
Set `OPENAI_API_KEY` and optionally `OPENAI_MODEL` in the server environment to enable LLM analysis. Without a key, the deterministic scoring fallback remains available.

## Automatic lead capture
The endpoint `POST /api/lead-capture.php` accepts JSON or form-encoded lead data from approved sources. It creates a new CRM lead and an activity record automatically. See `docs/lead-capture.md` for the integration example and webhook flow.

The system is designed to connect to official/authorized sources such as WebStripe website forms, ad-platform lead webhooks and approved business-data providers. Lead generation and outreach must follow the source's terms, applicable privacy requirements, consent rules and anti-spam requirements.

## Product direction
```text
Lead source → Capture → CRM → AI qualification → Lead score → Follow-up → Sales → Analytics → Won
```
