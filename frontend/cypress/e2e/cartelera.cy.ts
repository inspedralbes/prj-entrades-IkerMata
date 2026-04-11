/**
 * Flux normal mínim: la cartellera carrega i mostra la marca del projecte.
 */
describe('Cartellera', () => {
  it('mostra TICKET-FAST', () => {
    cy.visit('/')
    cy.contains('TICKET-FAST', { matchCase: false })
  })

  it('enllaç cap a iniciar sessió o navegació principal', () => {
    cy.visit('/')
    cy.get('nav').should('exist')
    cy.contains('a', 'Cartellera', { matchCase: false })
  })
})
