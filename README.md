# WebStripe AI CRM

PHP 8.1+ / MySQL 8+ CRM foundation for WebStripe Technologies.

## Current MVP
- Secure session login
- Lead CRUD (create/list/search)
- Sales pipeline: New → Contacted → Qualified → Proposal → Won/Lost
- Lead detail page and activity timeline
- AI lead scoring endpoint with optional OpenAI integration
- Responsive dashboard

## Local setup (XAMPP)
1. Create a MySQL database by importing `database/schema.sql` in phpMyAdmin.
2. Copy `config.example.php` to `config.php` and set database credentials.
3. From the project folder run: `php -S localhost:8000`
4. Create an admin: `php tools/create_admin.php "Admin" "admin@example.com" "ChangeMe123!"`
5. Open `http://localhost:8000`.

For production, use environment variables for secrets and HTTPS. Never commit `config.php` or API keys.

## AI
Set `OPENAI_API_KEY` and optionally `OPENAI_MODEL` in the server environment to enable LLM analysis. Without a key, the deterministic scoring fallback remains available.

## Lead generation roadmap
The MVP currently captures leads manually/through CRM integrations. The next lead-generation layer can add compliant connectors (website forms, ad platforms, inbound webhooks, approved business-data providers) and an AI qualification/follow-up workflow.
