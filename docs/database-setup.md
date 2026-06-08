# Database Setup

The production database should be Hostinger MySQL or MariaDB.

## Requirements

- Use PDO from PHP.
- Use prepared statements for all queries.
- Use `utf8mb4`.
- Store credentials only in server-side configuration.
- Never expose credentials to browser JavaScript.

Migration SQL files will be added under `public_html/migrations`.
