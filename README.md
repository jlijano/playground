# Playground

Agency project management app for `playground.optivex.solutions`.

## Deployment Model

- Hostinger deploys the public web root from `public_html`.
- Static frontend files must be served from `public_html`.
- PHP REST API endpoints live under `public_html/api`.
- Production database access must use Hostinger MySQL/MariaDB through PHP PDO.
- Do not use a Node.js production backend.

## Current Phase

Phase 0: project folder structure and deployment guardrails.

The dashboard UI will be built after the structure is confirmed.
