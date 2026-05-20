# Nuxt Minimal Starter

Look at the [Nuxt documentation](https://nuxt.com/docs/getting-started/introduction) to learn more.

## Setup

Make sure to install dependencies:

```bash
# npm
npm install

# pnpm
pnpm install

# yarn
yarn install

# bun
bun install
```

## Development Server

Start the development server on `http://localhost:3000`:

```bash
# npm
npm run dev

# pnpm
pnpm dev

# yarn
yarn dev

# bun
bun run dev
```

## Production

Build the application for production:

```bash
# npm
npm run build

# pnpm
pnpm build

# yarn
yarn build

# bun
bun run build
```

Locally preview production build:

```bash
# npm
npm run preview

# pnpm
pnpm preview

# yarn
yarn preview

# bun
bun run preview
```

Check out the [deployment documentation](https://nuxt.com/docs/getting-started/deployment) for more information.

## Tests E2E (Cypress)

Cal tenir el **frontend** i l’**API** (Laravel) en marxa perquè el login pugui fallar correctament.

```bash
npm install
# Terminal 1: Nuxt (per defecte Cypress apunta a http://localhost:3000)
npm run dev
# Terminal 2:
npm run cypress:run
# O interfície gràfica:
npm run cypress
```

Amb **Docker** el frontend sol estar a `http://localhost:3002`:

```bash
set CYPRESS_BASE_URL=http://localhost:3002
npm run cypress:run
```

Proves incloses:

- `cypress/e2e/cartelera.cy.ts` — pàgina principal i navegació.
- `cypress/e2e/login-error.cy.ts` — credencials invàlides i missatge d’error.
