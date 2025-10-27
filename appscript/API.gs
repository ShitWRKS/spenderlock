function callAPI(endpoint, method, params) {
  method = method || 'GET';
  params = params || null;
  
  const token = getAccessToken();
  const url = CONFIG.API_BASE_URL + endpoint;
  
  const options = {
    method: method.toLowerCase(),
    headers: {
      'Authorization': 'Bearer ' + token,
      'Accept': 'application/json',
      'X-Tenant-Domain': CONFIG.TENANT_DOMAIN
    },
    muteHttpExceptions: true
  };
  
  if (params && method.toUpperCase() === 'POST') {
    options.contentType = 'application/json';
    options.payload = JSON.stringify(params);
  }
  
  for (let attempt = 1; attempt <= CONFIG.MAX_RETRIES; attempt++) {
    try {
      const response = UrlFetchApp.fetch(url, options);
      const statusCode = response.getResponseCode();
      
      if (statusCode === 401) {
        if (attempt === 1) {
          clearToken();
          continue;
        }
        throw new Error('Autenticazione fallita');
      }
      
      if (statusCode >= 200 && statusCode < 300) {
        return JSON.parse(response.getContentText());
      }
      
      if (statusCode >= 500 && attempt < CONFIG.MAX_RETRIES) {
        Utilities.sleep(CONFIG.RETRY_DELAY_MS * attempt);
        continue;
      }
      
      throw new Error('API error: ' + statusCode + ' - ' + response.getContentText());
      
    } catch (error) {
      if (attempt === CONFIG.MAX_RETRIES) {
        Logger.log('API call failed after ' + attempt + ' attempts: ' + error.toString());
        throw error;
      }
      Utilities.sleep(CONFIG.RETRY_DELAY_MS * attempt);
    }
  }
}

function getUpcomingContracts(days) {
  days = days || CONFIG.DEFAULT_UPCOMING_DAYS;
  return callAPI('/contracts/upcoming?days=' + days, 'GET');
}

function searchContracts(query) {
  if (!query || query.trim() === '') {
    throw new Error('Query ricerca vuota');
  }
  return callAPI('/contracts/search?q=' + encodeURIComponent(query), 'GET');
}

function getContract(contractId) {
  return callAPI('/contracts/' + contractId, 'GET');
}

function getSuppliers(query) {
  const endpoint = query ? '/suppliers?q=' + encodeURIComponent(query) : '/suppliers';
  return callAPI(endpoint, 'GET');
}

function getSupplier(supplierId) {
  return callAPI('/suppliers/' + supplierId, 'GET');
}

function getSupplierContacts(supplierId) {
  return callAPI('/suppliers/' + supplierId + '/contacts', 'GET');
}

/**
 * Create new supplier
 * @param {Object} supplierData - {name, email, phone, vat_number, address}
 */
function createSupplier(supplierData) {
  return callAPI('/suppliers', 'POST', supplierData);
}

/**
 * Create new contact
 * @param {Object} contactData - {supplier_id, name, email, role, phone}
 */
function createContact(contactData) {
  return callAPI('/contacts', 'POST', contactData);
}

/**
 * Get comments for resource
 * @param {string} type - contract|supplier|contact
 * @param {number} id - Resource ID
 */
function getComments(type, id) {
  return callAPI('/comments/' + type + '/' + id, 'GET');
}

/**
 * Add comment to resource
 * @param {string} type - contract|supplier|contact
 * @param {number} id - Resource ID
 * @param {string} comment - Comment text
 * @param {string} userName - Optional user name (default: Gmail user)
 */
function addComment(type, id, comment, userName) {
  let name = userName || 'Utente Gmail';
  
  try {
    const userEmail = Session.getActiveUser().getEmail();
    if (userEmail) {
      name = userEmail.split('@')[0] || userEmail;
    }
  } catch (e) {
    Logger.log('Impossibile ottenere email utente: ' + e.toString());
  }
  
  return callAPI('/comments/' + type + '/' + id, 'POST', { 
    comment: comment,
    user_name: name
  });
}

/**
 * Get contact details
 * @param {number} contactId - Contact ID
 */
function getContact(contactId) {
  return callAPI('/contacts/' + contactId, 'GET');
}

/**
 * Update existing contact
 * @param {number} contactId - Contact ID
 * @param {Object} contactData - {name, email, role, phone, notes, supplier_id}
 */
function updateContact(contactId, contactData) {
  return callAPI('/contacts/' + contactId, 'PUT', contactData);
}

/**
 * Search contact by email (for Gmail integration)
 * @param {string} email - Email address
 */
function searchContactByEmail(email) {
  return callAPI('/contacts/search/email?email=' + encodeURIComponent(email), 'GET');
}

// Test all APIs
