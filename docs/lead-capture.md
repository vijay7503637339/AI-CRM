# Automatic Lead Capture

The CRM can receive leads automatically from WebStripe website forms and other approved integrations.

## 1. Configure the capture key

Set a strong server-side `LEAD_CAPTURE_KEY`. Generate one with:

```bash
php tools/generate_capture_key.php
```

Do not expose the key in browser JavaScript or commit it to Git.

## 2. Send a lead

Endpoint:

```text
POST /api/lead-capture.php
```

Recommended server-to-server request:

```bash
curl -X POST https://YOUR-CRM-DOMAIN/api/lead-capture.php \
  -H 'Content-Type: application/json' \
  -H 'X-Lead-Capture-Key: YOUR_SECRET_KEY' \
  -d '{
    "external_id": "website-12345",
    "name": "Rahul Sharma",
    "email": "rahul@example.com",
    "phone": "+91 9876543210",
    "company": "ABC Coaching",
    "source": "website",
    "message": "Need a new website",
    "value": 75000
  }'
```

A successful request creates a `new` lead and an activity entry automatically.
`external_id` makes webhook retries idempotent, so the same external event does not create duplicate leads.

## 3. Recommended WebStripe flow

```text
WebStripe website/contact form
            |
            v
     Your server-side form handler
            |
            v
     /api/lead-capture.php
            |
            v
        MySQL leads
            |
            +--> Activity timeline
            |
            +--> AI qualification/scoring
            |
            +--> Sales follow-up
```

For third-party sources such as ad platforms, use their official webhook/API mechanisms and their applicable consent, privacy, rate-limit and platform requirements. Do not scrape or bulk-message people without authorization.
