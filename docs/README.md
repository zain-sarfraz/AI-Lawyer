# AI Legal Receptionist + CRM — Documentation

This folder contains the complete, production-oriented implementation and software requirements documentation for the **AI Legal Receptionist + CRM** system.

## Document

| Document | Description |
| --- | --- |
| [AI_Legal_Receptionist_CRM_Complete_Implementation_Specification.md](./AI_Legal_Receptionist_CRM_Complete_Implementation_Specification.md) | Full implementation specification: product vision, scope, architecture, database design, scheduling engine, AI receptionist design, CRM workflows, APIs, security, testing, deployment phases, and appendices. |

## Project Snapshot

| Item | Value |
| --- | --- |
| Scope | Single law firm / single organization (NOT SaaS) |
| Database | MySQL 8.x |
| Backend | Laravel + PHP 8.3+ |
| Frontend | Blade + Bootstrap + JavaScript / Alpine.js |
| AI Voice | OpenAI Realtime API |
| Telephony | Twilio Voice |
| Calendar | Internal CRM first; Google Calendar after MVP |
| Document version | 1.0 |

## Core Principle

**AI is the conversational interface. Laravel is the business engine. MySQL is the system of record.**

The AI must never be the authority for prices, availability, appointment state, permissions, or other transactional facts. It asks the application. The application verifies. The AI communicates the verified result.
