# AI CRM Technical Architecture

## Application layers

```text
[Next.js Web App]
        |
        v
[API / Application Services]
        |
   +----+----------------+
   |    |                |
   v    v                v
[DB] [AI Service]   [Job Worker]
   |    |                |
   |    +-----> LLM APIs |
   |                     |
   +-----------> Integrations
```

## Core entities

- Organization
- User
- Lead
- Contact
- Pipeline
- Pipeline Stage
- Activity
- Task
- AI Run
- Integration

Every tenant-owned business record should carry an organization/tenant identifier and be checked at the service boundary.

## AI execution model

1. Application creates an AI job with a bounded purpose.
2. AI service receives only the context needed for that job.
3. Model returns structured data.
4. Server validates the model output against a schema and business rules.
5. Tool calls are allow-listed and audited.
6. Potentially consequential outbound actions require explicit policy/approval checks.

## Reliability

- Idempotent background jobs
- Retry with backoff for transient integration failures
- AI usage accounting per organization
- Request and tool-call audit logging
- Rate limits on public lead-capture endpoints

## Security

- Passwords/auth handled by a dedicated auth layer
- Secrets stored only in environment/secret storage
- No API keys in client-side code
- Tenant isolation enforced server-side
- Webhook signature verification for integrations
