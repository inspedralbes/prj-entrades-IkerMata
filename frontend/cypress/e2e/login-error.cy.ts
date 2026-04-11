/**
 * Flux d’error: credencials invàlides mostren missatge (requereix API Laravel accessible).
 */
describe('Login — error', () => {
  it('mostra error amb correu i contrasenya incorrectes', () => {
    cy.visit('/login')
    cy.get('input[type="email"]').clear().type('no-existeix@example.invalid')
    cy.get('input[type="password"]').clear().type('ContrasenyaIncorrecta123!')
    cy.contains('button', 'Entrar').click()
    cy.get('[data-cy="login-error"]', { timeout: 20000 }).should('be.visible').and('not.be.empty')
  })
})
