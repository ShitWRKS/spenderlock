/**
 * UI Builder - CardService per Gmail e Calendar
 * Version: 1.3 (Material Icons + Paging + Compact UI)
 */

// ============================================================================
// HOMEPAGE & UPCOMING CONTRACTS
// ============================================================================

function buildUpcomingContractsCard() {
  const header = CardService.newCardHeader()
    .setTitle('Contratti in Scadenza')
    .setSubtitle('Spenderlock')
    .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/event_note_black_24dp.png');
  
  const card = CardService.newCardBuilder().setHeader(header);
  
  try {
    const response = getUpcomingContracts(30);
    const contracts = response.data || [];
    
    if (contracts.length === 0) {
      card.addSection(CardService.newCardSection()
        .addWidget(CardService.newTextParagraph()
          .setText('Nessun contratto in scadenza nei prossimi 30 giorni')));
    } else {
      const contractsSection = CardService.newCardSection()
        .setHeader('Trovati ' + contracts.length + ' contratti');
      
      contracts.slice(0, CONFIG.MAX_CONTRACTS_DISPLAY).forEach(function(contract) {
        const widget = buildContractWidget(contract);
        contractsSection.addWidget(widget);
      });
      
      card.addSection(contractsSection);
    }
    
  } catch (error) {
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newTextParagraph()
        .setText('Errore caricamento: ' + error.toString())));
  }
  
  card.addSection(buildNavigationSection());
  return card.build();
}

function buildContractWidget(contract) {
  const daysRemaining = contract.days_remaining || 0;
  
  // Status icon
  let statusIcon = 'https://www.gstatic.com/images/icons/material/system/1x/check_circle_black_20dp.png';
  let statusText = daysRemaining + ' giorni';
  
  if (daysRemaining < 0) {
    statusIcon = 'https://www.gstatic.com/images/icons/material/system/1x/error_black_20dp.png';
    statusText = 'Scaduto';
  } else if (daysRemaining <= 15) {
    statusIcon = 'https://www.gstatic.com/images/icons/material/system/1x/warning_black_20dp.png';
  } else if (daysRemaining <= 30) {
    statusIcon = 'https://www.gstatic.com/images/icons/material/system/1x/info_black_20dp.png';
  }
  
  const supplierName = contract.supplier ? contract.supplier.name : 'N/A';
  
  const widget = CardService.newDecoratedText()
    .setTopLabel(supplierName)
    .setText(contract.title || 'Senza titolo')
    .setBottomLabel('Scadenza: ' + (contract.end_date || 'N/A') + ' · ' + statusText)
    .setStartIcon(CardService.newIconImage().setIconUrl(statusIcon))
    .setButton(CardService.newImageButton()
      .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/info_outline_black_24dp.png')
      .setAltText('Dettagli')
      .setOnClickAction(CardService.newAction()
        .setFunctionName('onViewContract')
        .setParameters({contract_id: contract.id.toString()})));
  
  return widget;
}


// ============================================================================
// NAVIGATION
// ============================================================================

function buildNavigationSection() {
  return CardService.newCardSection()
    .addWidget(CardService.newButtonSet()
      .addButton(CardService.newImageButton()
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/event_note_black_24dp.png')
        .setAltText('Scadenze')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onNavigateToUpcoming')))
      .addButton(CardService.newImageButton()
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/search_black_24dp.png')
        .setAltText('Cerca')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onNavigateToSearch')))
      .addButton(CardService.newImageButton()
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/business_black_24dp.png')
        .setAltText('Fornitori')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onNavigateToSuppliers')))
      .addButton(CardService.newImageButton()
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/settings_black_24dp.png')
        .setAltText('Impostazioni')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onNavigateToSettings'))));
}

function onNavigateToUpcoming(e) {
  const navigation = CardService.newNavigation().updateCard(buildUpcomingContractsCard());
  return CardService.newActionResponseBuilder().setNavigation(navigation).build();
}

function onNavigateToSearch(e) {
  const navigation = CardService.newNavigation().updateCard(buildSearchCard());
  return CardService.newActionResponseBuilder().setNavigation(navigation).build();
}

function onNavigateToSuppliers(e) {
  const navigation = CardService.newNavigation().updateCard(buildSuppliersCard(0));
  return CardService.newActionResponseBuilder().setNavigation(navigation).build();
}

function onNavigateToSettings(e) {
  const navigation = CardService.newNavigation().updateCard(buildSettingsCard());
  return CardService.newActionResponseBuilder().setNavigation(navigation).build();
}

// ============================================================================
// SEARCH CONTRACTS
// ============================================================================

function buildSearchCard() {
  const header = CardService.newCardHeader()
    .setTitle('Cerca Contratti')
    .setSubtitle('Spenderlock')
    .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/search_black_24dp.png');
  
  const card = CardService.newCardBuilder().setHeader(header);
  
  const searchSection = CardService.newCardSection()
    .addWidget(CardService.newTextInput()
      .setFieldName('search_query')
      .setTitle('Cerca per titolo o fornitore')
      .setHint('Es: Software, Microsoft, Hosting...'))
    .addWidget(CardService.newButtonSet()
      .addButton(CardService.newTextButton()
        .setText('Cerca')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onSearchContracts'))));
  
  card.addSection(searchSection);
  card.addSection(buildNavigationSection());
  return card.build();
}

function onSearchContracts(e) {
  const query = e.formInput.search_query || '';
  if (query.trim() === '') {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification().setText('Inserisci un termine di ricerca'))
      .build();
  }
  
  try {
    const response = searchContracts(query);
    const contracts = response.data || [];
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle('Risultati Ricerca')
        .setSubtitle('Query: "' + query + '"')
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/search_black_24dp.png'));
    
    if (contracts.length === 0) {
      card.addSection(CardService.newCardSection()
        .addWidget(CardService.newTextParagraph()
          .setText('Nessun contratto trovato')));
    } else {
      const section = CardService.newCardSection()
        .setHeader('Trovati ' + contracts.length + ' contratti');
      contracts.slice(0, CONFIG.MAX_CONTRACTS_DISPLAY).forEach(function(contract) {
        section.addWidget(buildContractWidget(contract));
      });
      card.addSection(section);
    }
    
    card.addSection(buildNavigationSection());
    const navigation = CardService.newNavigation().updateCard(card.build());
    return CardService.newActionResponseBuilder().setNavigation(navigation).build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore ricerca: ' + error.toString()))
      .build();
  }
}

// ============================================================================
// SUPPLIERS WITH PAGING
// ============================================================================

function buildSuppliersCard(page) {
  page = page || 0;
  const pageSize = 15;
  
  const header = CardService.newCardHeader()
    .setTitle('Fornitori')
    .setSubtitle('Spenderlock')
    .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/business_black_24dp.png');
  
  const card = CardService.newCardBuilder().setHeader(header);
  
  const searchSection = CardService.newCardSection()
    .addWidget(CardService.newTextInput()
      .setFieldName('supplier_query')
      .setTitle('Cerca fornitore')
      .setHint('Nome o email...'))
    .addWidget(CardService.newButtonSet()
      .addButton(CardService.newTextButton()
        .setText('Cerca')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onSearchSuppliers'))));
  
  card.addSection(searchSection);
  
  try {
    const response = getSuppliers();
    const allSuppliers = response.data || [];
    
    if (allSuppliers.length === 0) {
      card.addSection(CardService.newCardSection()
        .addWidget(CardService.newTextParagraph()
          .setText('Nessun fornitore trovato')));
    } else {
      const startIndex = page * pageSize;
      const endIndex = Math.min(startIndex + pageSize, allSuppliers.length);
      const suppliers = allSuppliers.slice(startIndex, endIndex);
      const totalPages = Math.ceil(allSuppliers.length / pageSize);
      
      const suppliersSection = CardService.newCardSection()
        .setHeader('Totale: ' + allSuppliers.length + ' fornitori (pagina ' + (page + 1) + '/' + totalPages + ')');
      
      suppliers.forEach(function(supplier) {
        const widget = buildSupplierWidget(supplier);
        suppliersSection.addWidget(widget);
      });
      
      card.addSection(suppliersSection);
      
      if (totalPages > 1) {
        const pagingSection = CardService.newCardSection();
        const buttonSet = CardService.newButtonSet();
        
        if (page > 0) {
          buttonSet.addButton(CardService.newTextButton()
            .setText('Precedente')
            .setOnClickAction(CardService.newAction()
              .setFunctionName('onSuppliersPageChange')
              .setParameters({page: (page - 1).toString()})));
        }
        
        if (page < totalPages - 1) {
          buttonSet.addButton(CardService.newTextButton()
            .setText('Successiva')
            .setOnClickAction(CardService.newAction()
              .setFunctionName('onSuppliersPageChange')
              .setParameters({page: (page + 1).toString()})));
        }
        
        pagingSection.addWidget(buttonSet);
        card.addSection(pagingSection);
      }
    }
    
  } catch (error) {
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newTextParagraph()
        .setText('Errore caricamento: ' + error.toString())));
  }
  
  card.addSection(buildNavigationSection());
  return card.build();
}

function onSuppliersPageChange(e) {
  const page = parseInt(e.parameters.page) || 0;
  const navigation = CardService.newNavigation().updateCard(buildSuppliersCard(page));
  return CardService.newActionResponseBuilder().setNavigation(navigation).build();
}

function buildSupplierWidget(supplier) {
  const widget = CardService.newDecoratedText()
    .setText(supplier.name || 'N/A')
    .setBottomLabel((supplier.email || '') + ' · ' + (supplier.phone || ''))
    .setStartIcon(CardService.newIconImage()
      .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/business_black_20dp.png'))
    .setButton(CardService.newImageButton()
      .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/contacts_black_24dp.png')
      .setAltText('Contatti')
      .setOnClickAction(CardService.newAction()
        .setFunctionName('onViewSupplierContacts')
        .setParameters({supplier_id: supplier.id.toString()})));
  
  return widget;
}

function onSearchSuppliers(e) {
  const query = e.formInput.supplier_query || '';
  
  if (query.trim() === '') {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification().setText('Inserisci un termine di ricerca'))
      .build();
  }
  
  try {
    const response = getSuppliers(query);
    const suppliers = response.data || [];
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle('Risultati Ricerca')
        .setSubtitle('Query: "' + query + '"')
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/search_black_24dp.png'));
    
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newTextInput()
        .setFieldName('supplier_query')
        .setTitle('Cerca altro fornitore')
        .setValue(query))
      .addWidget(CardService.newButtonSet()
        .addButton(CardService.newTextButton()
          .setText('Cerca')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onSearchSuppliers')))));
    
    if (suppliers.length === 0) {
      card.addSection(CardService.newCardSection()
        .addWidget(CardService.newTextParagraph()
          .setText('Nessun fornitore trovato per "' + query + '"')));
    } else {
      const section = CardService.newCardSection()
        .setHeader('Trovati ' + suppliers.length + ' fornitori');
      suppliers.forEach(function(supplier) {
        section.addWidget(buildSupplierWidget(supplier));
      });
      card.addSection(section);
    }
    
    card.addSection(buildNavigationSection());
    const navigation = CardService.newNavigation().updateCard(card.build());
    return CardService.newActionResponseBuilder().setNavigation(navigation).build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore ricerca: ' + error.toString()))
      .build();
  }
}

// ============================================================================
// CONTRACT DETAILS
// ============================================================================

function onViewContract(e) {
  const contractId = e.parameters.contract_id;
  try {
    const response = getContract(contractId);
    const contract = response.data || response;
    
    const supplierName = contract.supplier ? contract.supplier.name : 'N/A';
    const categoryName = contract.category ? contract.category.name : 'N/A';
    
    let totalAmount = contract.amount_total || 0;
    let amountNote = '';
    
    if (totalAmount === 0 && contract.amount_recurring && contract.frequency_months) {
      const startDate = new Date(contract.start_date);
      const endDate = new Date(contract.end_date);
      const monthsDuration = (endDate.getFullYear() - startDate.getFullYear()) * 12 + 
                            (endDate.getMonth() - startDate.getMonth());
      const numberOfPayments = Math.ceil(monthsDuration / contract.frequency_months);
      totalAmount = contract.amount_recurring * numberOfPayments;
      amountNote = ' (calcolato: ' + numberOfPayments + ' rate x €' + contract.amount_recurring + ')';
    }
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle(contract.title || 'Contratto')
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/description_black_24dp.png'))
      .addSection(CardService.newCardSection()
        .setHeader('Informazioni Generali')
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Fornitore')
          .setContent(supplierName)
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/business_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Categoria')
          .setContent(categoryName)
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/category_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Data Inizio')
          .setContent(contract.start_date || 'N/A')
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/event_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Data Scadenza')
          .setContent(contract.end_date || 'N/A')
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/event_available_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Rinnovo')
          .setContent(contract.renewal_mode === 'automatic' ? 'Automatico' : 'Manuale')
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/refresh_black_20dp.png')))
      .addSection(CardService.newCardSection()
        .setHeader('Dettagli Economici')
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Importo Totale')
          .setContent('€ ' + totalAmount.toFixed(2) + amountNote)
          .setMultiline(true)
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/euro_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Importo Ricorrente')
          .setContent(contract.amount_recurring ? '€ ' + contract.amount_recurring : 'N/A')
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/payments_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Frequenza')
          .setContent(contract.frequency_months ? 'Ogni ' + contract.frequency_months + ' mesi' : 'N/A')
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/schedule_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Tipo Pagamento')
          .setContent(contract.payment_type || 'N/A')
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/payment_black_20dp.png')))
      .addSection(CardService.newCardSection()
        .addWidget(CardService.newButtonSet()
          .addButton(CardService.newTextButton()
            .setText('Apri in Spenderlock')
            .setOpenLink(CardService.newOpenLink()
              .setUrl(CONFIG.API_BASE_URL.replace('/api', '') + '/admin/contracts/' + contractId)))
          .addButton(CardService.newTextButton()
            .setText('Commenti')
            .setOnClickAction(CardService.newAction()
              .setFunctionName('onViewComments')
              .setParameters({type: 'contract', id: contractId.toString(), title: contract.title})))
          .addButton(CardService.newTextButton()
            .setText('Indietro')
            .setOnClickAction(CardService.newAction()
              .setFunctionName('onNavigateToUpcoming')))));
    
    const navigation = CardService.newNavigation().pushCard(card.build());
    return CardService.newActionResponseBuilder().setNavigation(navigation).build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore: ' + error.toString()))
      .build();
  }
}

// ============================================================================
// SUPPLIER DETAILS
// ============================================================================

function onViewSupplierContacts(e) {
  const supplierId = e.parameters.supplier_id;
  try {
    const supplierResponse = getSupplier(supplierId);
    const supplier = supplierResponse.data || supplierResponse;
    
    const contactsResponse = getSupplierContacts(supplierId);
    const contacts = contactsResponse.data || [];
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle(supplier.name || 'Fornitore')
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/business_black_24dp.png'));
    
    card.addSection(CardService.newCardSection()
      .setHeader('Informazioni')
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Email')
        .setContent(supplier.email || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/email_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Telefono')
        .setContent(supplier.phone || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/phone_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('P.IVA')
        .setContent(supplier.vat_number || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/receipt_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Indirizzo')
        .setContent(supplier.address || 'N/A')
        .setMultiline(true)
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/location_on_black_20dp.png')));
    
    // Supplier action buttons (email, phone)
    const supplierActionsSection = CardService.newCardSection()
      .setHeader('Azioni Rapide');
    
    const supplierButtons = CardService.newButtonSet();
    
    if (supplier.email) {
      supplierButtons.addButton(CardService.newTextButton()
        .setText('Email Fornitore')
        .setOpenLink(CardService.newOpenLink()
          .setUrl('mailto:' + supplier.email)));
    }
    
    if (supplier.phone) {
      supplierButtons.addButton(CardService.newTextButton()
        .setText('Chiama Fornitore')
        .setOpenLink(CardService.newOpenLink()
          .setUrl('tel:' + supplier.phone)));
    }
    
    supplierActionsSection.addWidget(supplierButtons);
    card.addSection(supplierActionsSection);
    
    // Contacts list with action buttons
    if (contacts.length > 0) {
      const contactsSection = CardService.newCardSection()
        .setHeader('Contatti (' + contacts.length + ')');
      
      contacts.forEach(function(contact) {
        const contactWidget = CardService.newDecoratedText()
          .setTopLabel(contact.name || 'N/A')
          .setText((contact.role || 'N/A') + ' · ' + (contact.email || 'N/A'))
          .setBottomLabel(contact.phone || '')
          .setStartIcon(CardService.newIconImage()
            .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/person_black_20dp.png'))
          .setButton(CardService.newImageButton()
            .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/info_outline_black_24dp.png')
            .setAltText('Dettagli')
            .setOnClickAction(CardService.newAction()
              .setFunctionName('onViewContactDetails')
              .setParameters({
                contact_id: contact.id.toString(),
                supplier_id: supplierId.toString()
              })));
        
        contactsSection.addWidget(contactWidget);
      });
      
      card.addSection(contactsSection);
    } else {
      card.addSection(CardService.newCardSection()
        .addWidget(CardService.newTextParagraph()
          .setText('Nessun contatto registrato')));
    }
    
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newButtonSet()
        .addButton(CardService.newTextButton()
          .setText('Commenti')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onViewComments')
            .setParameters({type: 'supplier', id: supplierId.toString(), title: supplier.name})))
        .addButton(CardService.newTextButton()
          .setText('Indietro')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onNavigateToSuppliers')))));
    
    const navigation = CardService.newNavigation().pushCard(card.build());
    return CardService.newActionResponseBuilder().setNavigation(navigation).build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore: ' + error.toString()))
      .build();
  }
}

// ============================================================================
// CONTACT DETAILS & EDIT
// ============================================================================

function onViewContactDetails(e) {
  const contactId = e.parameters.contact_id;
  const supplierId = e.parameters.supplier_id;
  
  try {
    const response = getContact(contactId);
    const contact = response.data || response;
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle(contact.name || 'Contatto')
        .setSubtitle(contact.supplier ? contact.supplier.name : '')
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/person_black_24dp.png'));
    
    // Contact info
    card.addSection(CardService.newCardSection()
      .setHeader('Informazioni')
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Nome')
        .setContent(contact.name || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/person_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Ruolo')
        .setContent(contact.role || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/work_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Email')
        .setContent(contact.email || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/email_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Telefono')
        .setContent(contact.phone || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/phone_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Note')
        .setContent(contact.notes || 'N/A')
        .setMultiline(true)
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/notes_black_20dp.png')));
    
    // Action buttons
    const actionsSection = CardService.newCardSection()
      .setHeader('Azioni Rapide');
    
    const actionButtons = CardService.newButtonSet();
    
    if (contact.email) {
      actionButtons.addButton(CardService.newTextButton()
        .setText('Email')
        .setOpenLink(CardService.newOpenLink()
          .setUrl('mailto:' + contact.email)));
    }
    
    if (contact.phone) {
      actionButtons.addButton(CardService.newTextButton()
        .setText('Chiama')
        .setOpenLink(CardService.newOpenLink()
          .setUrl('tel:' + contact.phone)));
    }
    
    actionsSection.addWidget(actionButtons);
    card.addSection(actionsSection);
    
    // Edit button
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newButtonSet()
        .addButton(CardService.newTextButton()
          .setText('Modifica')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onEditContact')
            .setParameters({
              contact_id: contactId.toString(),
              supplier_id: supplierId.toString()
            })))
        .addButton(CardService.newTextButton()
          .setText('Commenti')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onViewComments')
            .setParameters({type: 'contact', id: contactId.toString(), title: contact.name})))
        .addButton(CardService.newTextButton()
          .setText('Indietro')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onViewSupplierContacts')
            .setParameters({supplier_id: supplierId.toString()})))));
    
    const navigation = CardService.newNavigation().pushCard(card.build());
    return CardService.newActionResponseBuilder().setNavigation(navigation).build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore: ' + error.toString()))
      .build();
  }
}

function onEditContact(e) {
  const contactId = e.parameters.contact_id;
  const supplierId = e.parameters.supplier_id;
  
  try {
    const response = getContact(contactId);
    const contact = response.data || response;
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle('Modifica Contatto')
        .setSubtitle(contact.name)
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/edit_black_24dp.png'));
    
    // Edit form
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newTextInput()
        .setFieldName('name')
        .setTitle('Nome')
        .setValue(contact.name || ''))
      .addWidget(CardService.newTextInput()
        .setFieldName('role')
        .setTitle('Ruolo')
        .setValue(contact.role || ''))
      .addWidget(CardService.newTextInput()
        .setFieldName('email')
        .setTitle('Email')
        .setValue(contact.email || ''))
      .addWidget(CardService.newTextInput()
        .setFieldName('phone')
        .setTitle('Telefono')
        .setValue(contact.phone || ''))
      .addWidget(CardService.newTextInput()
        .setFieldName('notes')
        .setTitle('Note')
        .setMultiline(true)
        .setValue(contact.notes || ''))
      .addWidget(CardService.newButtonSet()
        .addButton(CardService.newTextButton()
          .setText('Salva')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onSaveContact')
            .setParameters({
              contact_id: contactId.toString(),
              supplier_id: supplierId.toString()
            })))
        .addButton(CardService.newTextButton()
          .setText('Annulla')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onViewContactDetails')
            .setParameters({
              contact_id: contactId.toString(),
              supplier_id: supplierId.toString()
            })))));
    
    const navigation = CardService.newNavigation().pushCard(card.build());
    return CardService.newActionResponseBuilder().setNavigation(navigation).build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore: ' + error.toString()))
      .build();
  }
}

function onSaveContact(e) {
  const contactId = e.parameters.contact_id;
  const supplierId = e.parameters.supplier_id;
  
  const contactData = {
    name: e.formInput.name || '',
    role: e.formInput.role || '',
    email: e.formInput.email || '',
    phone: e.formInput.phone || '',
    notes: e.formInput.notes || ''
  };
  
  if (!contactData.name || !contactData.email) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Nome e Email obbligatori'))
      .build();
  }
  
  try {
    updateContact(contactId, contactData);
    
    // Redirect to contact details
    const response = getContact(contactId);
    const contact = response.data || response;
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle(contact.name || 'Contatto')
        .setSubtitle(contact.supplier ? contact.supplier.name : '')
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/person_black_24dp.png'));
    
    card.addSection(CardService.newCardSection()
      .setHeader('Informazioni')
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Nome')
        .setContent(contact.name || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/person_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Ruolo')
        .setContent(contact.role || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/work_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Email')
        .setContent(contact.email || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/email_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Telefono')
        .setContent(contact.phone || 'N/A')
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/phone_black_20dp.png'))
      .addWidget(CardService.newKeyValue()
        .setTopLabel('Note')
        .setContent(contact.notes || 'N/A')
        .setMultiline(true)
        .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/notes_black_20dp.png')));
    
    const actionsSection = CardService.newCardSection()
      .setHeader('Azioni Rapide');
    const actionButtons = CardService.newButtonSet();
    
    if (contact.email) {
      actionButtons.addButton(CardService.newTextButton()
        .setText('Email')
        .setOpenLink(CardService.newOpenLink()
          .setUrl('mailto:' + contact.email)));
    }
    
    if (contact.phone) {
      actionButtons.addButton(CardService.newTextButton()
        .setText('Chiama')
        .setOpenLink(CardService.newOpenLink()
          .setUrl('tel:' + contact.phone)));
    }
    
    actionsSection.addWidget(actionButtons);
    card.addSection(actionsSection);
    
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newButtonSet()
        .addButton(CardService.newTextButton()
          .setText('Modifica')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onEditContact')
            .setParameters({
              contact_id: contactId.toString(),
              supplier_id: supplierId.toString()
            })))
        .addButton(CardService.newTextButton()
          .setText('Commenti')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onViewComments')
            .setParameters({type: 'contact', id: contactId.toString(), title: contact.name})))
        .addButton(CardService.newTextButton()
          .setText('Indietro')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onViewSupplierContacts')
            .setParameters({supplier_id: supplierId.toString()})))));
    
    const navigation = CardService.newNavigation().updateCard(card.build());
    
    return CardService.newActionResponseBuilder()
      .setNavigation(navigation)
      .setNotification(CardService.newNotification()
        .setText('Contatto aggiornato'))
      .build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore: ' + error.toString()))
      .build();
  }
}

// ============================================================================
// GMAIL CONTEXT - CREATE CONTACT FROM EMAIL
// ============================================================================

function buildGmailContextCard(e) {
  // Log event structure for debugging
  Logger.log('Gmail event object: ' + JSON.stringify(e));
  
  // Get sender email from Gmail using the correct API
  let fromEmail = '';
  let senderName = '';
  let hasEmailData = false;
  
  try {
    // CORRECT METHOD: Use e.gmail.messageId + e.gmail.accessToken
    if (e && e.gmail && e.gmail.messageId && e.gmail.accessToken) {
      // Set access token to allow reading current message
      GmailApp.setCurrentMessageAccessToken(e.gmail.accessToken);
      
      // Get message by ID
      const message = GmailApp.getMessageById(e.gmail.messageId);
      
      if (message) {
        // Get From header
        const fromHeader = message.getFrom();
        Logger.log('From header: ' + fromHeader);
        
        if (fromHeader) {
          // Extract email from "Name Surname <email@domain.com>" format
          const emailMatch = fromHeader.match(/<(.+?)>/);
          fromEmail = emailMatch ? emailMatch[1] : fromHeader;
          
          // Extract name
          const nameMatch = fromHeader.match(/^(.+?)\s*</);
          senderName = nameMatch ? nameMatch[1].trim().replace(/"/g, '') : fromEmail.split('@')[0];
          hasEmailData = true;
        }
      }
    }
  } catch (error) {
    Logger.log('Error accessing Gmail message: ' + error.toString());
    Logger.log('Error stack: ' + error.stack);
  }
  
  const email = fromEmail;
  
  // If no email found, show manual entry form
  if (!hasEmailData || !email) {
    Logger.log('No email data found, showing manual form');
    return buildManualContactForm();
  }
  
  const card = CardService.newCardBuilder()
    .setHeader(CardService.newCardHeader()
      .setTitle('Registra Contatto')
      .setSubtitle('Da email aperta')
      .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/person_add_black_24dp.png'));
  
  // Check if contact exists
  try {
    const searchResponse = searchContactByEmail(email);
    const existingContact = searchResponse.data;
    
    // Contact exists
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newTextParagraph()
        .setText('Contatto già registrato')))
      .addSection(CardService.newCardSection()
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Nome')
          .setContent(existingContact.name)
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/person_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Fornitore')
          .setContent(existingContact.supplier ? existingContact.supplier.name : 'N/A')
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/business_black_20dp.png'))
        .addWidget(CardService.newKeyValue()
          .setTopLabel('Email')
          .setContent(existingContact.email)
          .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/email_black_20dp.png'))
        .addWidget(CardService.newButtonSet()
          .addButton(CardService.newTextButton()
            .setText('Visualizza')
            .setOnClickAction(CardService.newAction()
              .setFunctionName('onViewContactDetails')
              .setParameters({
                contact_id: existingContact.id.toString(),
                supplier_id: existingContact.supplier_id.toString()
              })))));
    
  } catch (error) {
    // Contact not found - show creation form with supplier selection
    const domain = email.split('@')[1] || '';
    const guessedCompany = domain.split('.')[0] || domain;
    
    // Load suppliers list
    let suppliers = [];
    let suppliersSelection = null;
    
    try {
      const suppliersResponse = getSuppliers();
      suppliers = suppliersResponse.data || [];
      
      if (suppliers.length > 0) {
        suppliersSelection = CardService.newSelectionInput()
          .setType(CardService.SelectionInputType.DROPDOWN)
          .setTitle('Seleziona Fornitore')
          .setFieldName('supplier_id');
        
        // Add "create new" option
        suppliersSelection.addItem('+ Crea nuovo fornitore', 'new', false);
        
        // Add existing suppliers
        suppliers.forEach(function(supplier) {
          const isSelected = supplier.name.toLowerCase().includes(guessedCompany.toLowerCase());
          suppliersSelection.addItem(supplier.name, supplier.id.toString(), isSelected);
        });
      }
    } catch (err) {
      Logger.log('Error loading suppliers: ' + err.toString());
    }
    
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newTextParagraph()
        .setText('✓ Dati estratti automaticamente da email')))
      .addSection(CardService.newCardSection()
        .setHeader('Dati Contatto')
        .addWidget(CardService.newTextInput()
          .setFieldName('contact_name')
          .setTitle('Nome')
          .setValue(senderName))
        .addWidget(CardService.newTextInput()
          .setFieldName('contact_email')
          .setTitle('Email')
          .setValue(email))
        .addWidget(CardService.newTextInput()
          .setFieldName('contact_role')
          .setTitle('Ruolo')
          .setHint('Es: Responsabile Vendite'))
        .addWidget(CardService.newTextInput()
          .setFieldName('contact_phone')
          .setTitle('Telefono (opzionale)')));
    
    // Supplier selection section
    const supplierSection = CardService.newCardSection()
      .setHeader('Associa a Fornitore');
    
    if (suppliersSelection) {
      supplierSection.addWidget(suppliersSelection);
    }
    
    // New supplier name field (shown when "create new" selected)
    supplierSection.addWidget(CardService.newTextInput()
      .setFieldName('new_supplier_name')
      .setTitle('Nome Nuovo Fornitore')
      .setValue(guessedCompany.charAt(0).toUpperCase() + guessedCompany.slice(1))
      .setHint('Solo se selezioni "+ Crea nuovo fornitore"'));
    
    card.addSection(supplierSection)
      .addSection(CardService.newCardSection()
        .addWidget(CardService.newButtonSet()
          .addButton(CardService.newTextButton()
            .setText('Salva Contatto')
            .setOnClickAction(CardService.newAction()
              .setFunctionName('onCreateContactFromGmail')))
          .addButton(CardService.newTextButton()
            .setText('Annulla')
            .setOnClickAction(CardService.newAction()
              .setFunctionName('onNavigateToUpcoming')))));
  }
  
  return [card.build()];
}

function buildManualContactForm() {
  const card = CardService.newCardBuilder()
    .setHeader(CardService.newCardHeader()
      .setTitle('Registra Contatto')
      .setSubtitle('Inserimento manuale')
      .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/person_add_black_24dp.png'));
  
  // Load suppliers list
  let suppliers = [];
  let suppliersSelection = null;
  
  try {
    const suppliersResponse = getSuppliers();
    suppliers = suppliersResponse.data || [];
    
    if (suppliers.length > 0) {
      suppliersSelection = CardService.newSelectionInput()
        .setType(CardService.SelectionInputType.DROPDOWN)
        .setTitle('Seleziona Fornitore')
        .setFieldName('supplier_id');
      
      suppliersSelection.addItem('+ Crea nuovo fornitore', 'new', true);
      
      suppliers.forEach(function(supplier) {
        suppliersSelection.addItem(supplier.name, supplier.id.toString(), false);
      });
    }
  } catch (err) {
    Logger.log('Error loading suppliers: ' + err.toString());
  }
  
  card.addSection(CardService.newCardSection()
    .addWidget(CardService.newTextParagraph()
      .setText('Inserisci dati contatto manualmente')))
    .addSection(CardService.newCardSection()
      .setHeader('Dati Contatto')
      .addWidget(CardService.newTextInput()
        .setFieldName('contact_name')
        .setTitle('Nome')
        .setHint('Nome completo'))
      .addWidget(CardService.newTextInput()
        .setFieldName('contact_email')
        .setTitle('Email')
        .setHint('email@example.com'))
      .addWidget(CardService.newTextInput()
        .setFieldName('contact_role')
        .setTitle('Ruolo')
        .setHint('Es: Responsabile Vendite'))
      .addWidget(CardService.newTextInput()
        .setFieldName('contact_phone')
        .setTitle('Telefono (opzionale)')));
  
  const supplierSection = CardService.newCardSection()
    .setHeader('Associa a Fornitore');
  
  if (suppliersSelection) {
    supplierSection.addWidget(suppliersSelection);
  }
  
  supplierSection.addWidget(CardService.newTextInput()
    .setFieldName('new_supplier_name')
    .setTitle('Nome Nuovo Fornitore')
    .setHint('Solo se selezioni "+ Crea nuovo fornitore"'));
  
  card.addSection(supplierSection)
    .addSection(CardService.newCardSection()
      .addWidget(CardService.newButtonSet()
        .addButton(CardService.newTextButton()
          .setText('Salva Contatto')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onCreateContactFromGmail')))
        .addButton(CardService.newTextButton()
          .setText('Annulla')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onNavigateToUpcoming')))));
  
  return [card.build()];
}

function onCreateContactFromGmail(e) {
  const contactName = e.formInput.contact_name || '';
  const contactEmail = e.formInput.contact_email || '';
  const contactRole = e.formInput.contact_role || '';
  const contactPhone = e.formInput.contact_phone || '';
  const supplierId = e.formInput.supplier_id || '';
  const newSupplierName = e.formInput.new_supplier_name || '';
  
  if (!contactName || !contactEmail) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Nome e Email obbligatori'))
      .build();
  }
  
  try {
    let supplier;
    
    // Check if creating new supplier or using existing
    if (supplierId === 'new') {
      // Create new supplier
      if (!newSupplierName) {
        return CardService.newActionResponseBuilder()
          .setNotification(CardService.newNotification()
            .setText('Inserisci nome nuovo fornitore'))
          .build();
      }
      
      const supplierData = {
        name: newSupplierName,
        email: contactEmail,
        phone: contactPhone
      };
      const createSupplierResponse = createSupplier(supplierData);
      supplier = createSupplierResponse.data;
      
    } else if (supplierId) {
      // Use existing supplier
      const supplierResponse = getSupplier(supplierId);
      supplier = supplierResponse.data;
      
    } else {
      return CardService.newActionResponseBuilder()
        .setNotification(CardService.newNotification()
          .setText('Seleziona un fornitore'))
        .build();
    }
    
    // Create contact
    const contactData = {
      supplier_id: supplier.id,
      name: contactName,
      email: contactEmail,
      role: contactRole,
      phone: contactPhone
    };
    
    const createContactResponse = createContact(contactData);
    const contact = createContactResponse.data;
    
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('✓ Contatto creato: ' + contact.name))
      .setNavigation(CardService.newNavigation()
        .updateCard(buildUpcomingContractsCard()))
      .build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore: ' + error.toString()))
      .build();
  }
}

// ============================================================================
// SETTINGS
// ============================================================================

function buildSettingsCard() {
  const header = CardService.newCardHeader()
    .setTitle('Impostazioni')
    .setSubtitle('Spenderlock')
    .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/settings_black_24dp.png');
  
  const card = CardService.newCardBuilder().setHeader(header);
  
  const settingsSection = CardService.newCardSection()
    .addWidget(CardService.newKeyValue()
      .setTopLabel('API Base URL')
      .setContent(CONFIG.API_BASE_URL)
      .setMultiline(true)
      .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/cloud_black_20dp.png'))
    .addWidget(CardService.newKeyValue()
      .setTopLabel('Token Cache')
      .setContent('Attivo (1 ora)')
      .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/vpn_key_black_20dp.png'))
    .addWidget(CardService.newButtonSet()
      .addButton(CardService.newTextButton()
        .setText('Test Connessione')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onTestConnection')))
      .addButton(CardService.newTextButton()
        .setText('Pulisci Cache')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onClearCache'))));
  
  card.addSection(settingsSection);
  card.addSection(buildNavigationSection());
  return card.build();
}

function onTestConnection(e) {
  try {
    testAuth();
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification().setText('Connessione OK'))
      .build();
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification().setText('Errore: ' + error.toString()))
      .build();
  }
}

function onClearCache(e) {
  clearToken();
  return CardService.newActionResponseBuilder()
    .setNotification(CardService.newNotification().setText('Cache pulita'))
    .build();
}

// ============================================================================
// COMMENTS
// ============================================================================

function onViewComments(e) {
  const type = e.parameters.type;
  const id = e.parameters.id;
  const title = e.parameters.title || 'Risorsa';
  
  try {
    const response = getComments(type, id);
    const comments = response.data || [];
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle('Commenti')
        .setSubtitle(title)
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/comment_black_24dp.png'));
    
    // Form nuovo commento
    const formSection = CardService.newCardSection()
      .addWidget(CardService.newTextInput()
        .setFieldName('new_comment')
        .setTitle('Nuovo commento')
        .setHint('Scrivi un commento...')
        .setMultiline(true))
      .addWidget(CardService.newButtonSet()
        .addButton(CardService.newTextButton()
          .setText('Invia')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onAddComment')
            .setParameters({type: type, id: id, title: title}))));
    
    card.addSection(formSection);
    
    // Lista commenti esistenti
    if (comments.length > 0) {
      const commentsSection = CardService.newCardSection()
        .setHeader('Commenti (' + comments.length + ')');
      
      comments.forEach(function(comment) {
        const date = new Date(comment.created_at);
        const dateStr = date.toLocaleDateString('it-IT') + ' ' + date.toLocaleTimeString('it-IT', {hour: '2-digit', minute: '2-digit'});
        
        commentsSection.addWidget(CardService.newDecoratedText()
          .setTopLabel(comment.user_name + ' · ' + dateStr)
          .setText(comment.comment)
          .setWrapText(true)
          .setStartIcon(CardService.newIconImage()
            .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/person_black_20dp.png')));
      });
      
      card.addSection(commentsSection);
    } else {
      card.addSection(CardService.newCardSection()
        .addWidget(CardService.newTextParagraph()
          .setText('Nessun commento presente')));
    }
    
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newTextButton()
        .setText('Indietro')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onNavigateToUpcoming'))));
    
    const navigation = CardService.newNavigation().pushCard(card.build());
    return CardService.newActionResponseBuilder().setNavigation(navigation).build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore caricamento commenti: ' + error.toString()))
      .build();
  }
}

function onAddComment(e) {
  const type = e.parameters.type;
  const id = e.parameters.id;
  const title = e.parameters.title;
  const comment = e.formInput.new_comment || '';
  
  if (comment.trim() === '') {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Inserisci un commento'))
      .build();
  }
  
  try {
    addComment(type, id, comment);
    
    // Ricarica pagina commenti - richiama direttamente la funzione
    const response = getComments(type, id);
    const comments = response.data || [];
    
    const card = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader()
        .setTitle('Commenti')
        .setSubtitle(title)
        .setImageUrl('https://www.gstatic.com/images/icons/material/system/1x/comment_black_24dp.png'));
    
    const formSection = CardService.newCardSection()
      .addWidget(CardService.newTextInput()
        .setFieldName('new_comment')
        .setTitle('Nuovo commento')
        .setHint('Scrivi un commento...')
        .setMultiline(true))
      .addWidget(CardService.newButtonSet()
        .addButton(CardService.newTextButton()
          .setText('Invia')
          .setOnClickAction(CardService.newAction()
            .setFunctionName('onAddComment')
            .setParameters({type: type, id: id, title: title}))));
    
    card.addSection(formSection);
    
    if (comments.length > 0) {
      const commentsSection = CardService.newCardSection()
        .setHeader('Commenti (' + comments.length + ')');
      
      comments.forEach(function(comment) {
        const date = new Date(comment.created_at);
        const dateStr = date.toLocaleDateString('it-IT') + ' ' + date.toLocaleTimeString('it-IT', {hour: '2-digit', minute: '2-digit'});
        
        commentsSection.addWidget(CardService.newDecoratedText()
          .setTopLabel(comment.user_name + ' · ' + dateStr)
          .setText(comment.comment)
          .setWrapText(true)
          .setStartIcon(CardService.newIconImage()
            .setIconUrl('https://www.gstatic.com/images/icons/material/system/1x/person_black_20dp.png')));
      });
      
      card.addSection(commentsSection);
    } else {
      card.addSection(CardService.newCardSection()
        .addWidget(CardService.newTextParagraph()
          .setText('Nessun commento presente')));
    }
    
    card.addSection(CardService.newCardSection()
      .addWidget(CardService.newTextButton()
        .setText('Indietro')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('onNavigateToUpcoming'))));
    
    const navigation = CardService.newNavigation().updateCard(card.build());
    
    return CardService.newActionResponseBuilder()
      .setNavigation(navigation)
      .setNotification(CardService.newNotification()
        .setText('Commento aggiunto'))
      .build();
    
  } catch (error) {
    return CardService.newActionResponseBuilder()
      .setNotification(CardService.newNotification()
        .setText('Errore: ' + error.toString()))
      .build();
  }
}
