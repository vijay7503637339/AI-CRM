# AI CRM

An AI-first SaaS CRM for capturing, qualifying, managing and following up with leads.

## MVP

- Lead management
- Sales pipeline
- Lead scoring
- AI sales assistant foundation
- Follow-up tasks and activity timeline
- Multi-tenant-ready architecture

## Product direction

The CRM is designed around controlled AI agents that use explicit tools rather than unrestricted database access.

Core agent roles planned for the MVP:

1. Lead Scoring Agent
2. Lead Qualification Agent
3. Follow-up Agent
4. Sales Assistant

## Architecture

```text
Web App
   |
   v
API Layer
   |
   +---- PostgreSQL
   |
   +---- AI Service / LLM
   |
   +---- Background Jobs
   |
   +---- External Integrations
          |--- Email
          |--- WhatsApp
          |--- Calendar
          |--- Lead Sources
```

## Development

The initial implementation is being built incrementally so each layer can be tested before integrations and autonomous workflows are added.
