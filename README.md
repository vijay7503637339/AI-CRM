# WebStripe AI CRM

PHP 8.1+ / MySQL 8+ CRM foundation for WebStripe Technologies.

## Current MVP
- Secure session login
- Lead CRUD (create/list/search)
- Sales pipeline: New → Contacted → Qualified → Proposal → Won/Lost
- Lead detail page and activity timeline
- AI lead scoring endpoint with optional OpenAI integration
- Responsive dashboard
- Automatic lead-capture webhook for website/forms/integrations
- Duplicate-event protection with external IDs

## Local setup (XAMPP)
1. Create a MySQL database by importing `database/schema.sql` in phpMyAdmin.
2. Copy `config.example.php` to `config.php` and set database credentials.
3. Set `LEAD_CAPTURE_KEY` in the server environment.
4. From the project folder run: `php -S localhost:8000`
5. Create an admin: `php tools/create_admin.php "Admin" "admin@example.com" "ChangeMe123!"`
6. Open `http://localhost:8000`.

For production, use environment variables for secrets and HTTPS. Never commit `config.php` or API keys.

## AI
Set `OPENAI_API_KEY` and optionally `OPENAI_MODEL` in the server environment to enable LLM analysis. Without a key, the deterministic scoring fallback remains available.

## Automatic lead capture
The endpoint `POST /api/lead-capture.php` accepts JSON or form-encoded lead data from approved sources. It creates a new CRM lead and an activity record automatically. See `docs/lead-capture.md` for the integration example and webhook flow.

The system is designed to connect to official/authorized sources such as WebStripe website forms, ad-platform lead webhooks and approved business-data providers. Lead generation and outreach must follow the source's terms, applicable privacy requirements, consent rules and anti-spam requirements.

## Product direction
```text
Lead source → Capture → CRM → AI qualification → Lead score → Follow-up → Sales → Won
```
