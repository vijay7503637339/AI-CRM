# Web Prospecting

The AI CRM now includes a first-party web prospecting MVP for discovering business records from public web pages that the user is permitted to crawl.

## Grocery-shop example

1. Open `public/web-prospecting.php` after logging in.
2. Enter a public business directory or other permitted source URL as the **Seed URL**.
3. Set category to `Grocery / Kirana` and location to the target area, for example `Delhi`.
4. Choose 5–25 pages.
5. Run the crawler.
6. Review extracted prospects.
7. Click **Import lead** to create a normal CRM lead.
8. Run the existing AI analysis on the imported lead for scoring and next action.

## What the crawler extracts

- Business name
- Category when exposed by the page
- Website
- Domain
- Public email when exposed
- Public phone when exposed
- Address/city when exposed through structured data
- Source URL
- Raw JSON-LD business data where available

## Safety and source rules

The crawler only accepts HTTP/HTTPS URLs, rejects private/local hosts, stays on the seed domain, and checks `robots.txt` before fetching pages. It does not bypass authentication, CAPTCHAs, paywalls, rate limits, or other access controls.

Only use sources where automated access and the intended use of the extracted information are permitted by the site's terms, applicable law, and privacy/marketing requirements.

## Architecture

`public/web-prospecting.php` → `app/AI/WebProspector.php` → `web_prospects` → `public/prospect-import.php` → `leads` → AI scoring/assistant.

The crawler is intentionally synchronous for the MVP. A later production version should move crawling into a queue/worker with per-domain rate limiting, crawl logs, retries, and scheduled jobs.
