# AI CRM Product Plan

## Goal

Build a sellable AI-first CRM SaaS that helps small and mid-sized sales teams capture leads, understand lead quality, manage pipeline and automate follow-ups.

## Phase 1 — CRM Core

- Organizations and users
- Leads and contacts
- Lead status and source
- Sales pipeline and stages
- Notes and activity timeline
- Tasks and follow-up dates
- Dashboard metrics

## Phase 2 — AI Layer

### Lead Scoring Agent

Input: lead profile, source, activity and business rules.
Output: score, reasons and recommended next action.

### Lead Qualification Agent

Input: lead details and conversation context.
Output: qualification summary, missing information and suggested questions.

### Follow-up Agent

Input: lead status, last activity, next action and approved communication channel.
Output: draft follow-up and recommended timing.

### Sales Assistant

Natural-language assistant that can read CRM context and perform approved actions through tools such as:

- get_lead
- update_lead
- create_task
- get_pipeline
- create_note
- draft_followup

Agents must never receive unrestricted database credentials or unrestricted write access.

## Current implementation

The PHP + MySQL MVP now includes lead management, pipeline, lead details, activity timeline and an explainable baseline scoring service. The scoring service is API-ready so an LLM can be added without changing the CRM data model or UI contract.

## Phase 3 — Integrations

- Website lead capture
- Email
- WhatsApp Business
- Google Calendar
- Authorized lead/data providers

## SaaS Requirements

- Tenant isolation
- Role-based access control
- Audit log
- Usage limits and AI spend controls
- Secure secrets and webhook verification
- Consent-aware messaging

## Suggested initial niche

Start with one niche where lead volume and follow-up pain are high. A vertical-first approach makes the product easier to position, onboard and sell than a generic CRM.
