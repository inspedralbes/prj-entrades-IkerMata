import { defineConfig } from 'cypress'

/**
 * E2E contra el Nuxt en execució.
 * Per defecte `http://localhost:3000` (`npm run dev`).
 * Amb Docker (port 3002): `CYPRESS_BASE_URL=http://localhost:3002 npm run cypress:run`
 */
export default defineConfig({
  e2e: {
    baseUrl: process.env.CYPRESS_BASE_URL || 'http://localhost:3000',
    supportFile: 'cypress/support/e2e.ts',
    specPattern: 'cypress/e2e/**/*.cy.ts',
    video: false,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 15000
  }
})
