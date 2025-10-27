/**
 * Entry points per Gmail e Calendar add-on
 * IMPORTANTE: Entry points DEVONO restituire Card[], NON ActionResponse
 */

/**
 * Gmail: Contextual trigger (quando apri email)
 * Mostra card per registrare contatto da email mittente
 */
function buildAddOn(e) {
  try {
    Logger.log('buildAddOn called with event: ' + JSON.stringify(e));
    return buildGmailContextCard(e);
  } catch (error) {
    Logger.log('ERROR in buildAddOn: ' + error.toString());
    Logger.log('Stack: ' + error.stack);
    // Fallback to manual form
    return buildManualContactForm();
  }
}

/**
 * Gmail: Homepage (prima card visualizzata)
 */
function buildHomepage(e) {
  const card = buildUpcomingContractsCard();
  return [card];
}

/**
 * Calendar: Contextual trigger (quando apri evento)
 */
function buildCalendarAddOn(e) {
  const card = buildUpcomingContractsCard();
  return [card];
}
