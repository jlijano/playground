# Deployment Notes

Hostinger deploys public files from `public_html`.

## Rules

- Keep deployable frontend assets in `public_html`.
- Keep PHP API endpoints under `public_html/api`.
- Configure database credentials only in Hostinger/server-side config.
- Do not deploy a Node.js production server.

## Future Build Flow

If React/Vite is used:

1. Build the frontend from `frontend`.
2. Emit or copy the static build output into `public_html`.
3. Commit and push the deployable `public_html` assets to the deployment branch.
