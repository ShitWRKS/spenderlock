function getAccessToken() {
  const cache = PropertiesService.getUserProperties();
  const cachedToken = cache.getProperty(CONFIG.TOKEN_CACHE_KEY);
  const expiryTime = cache.getProperty(CONFIG.TOKEN_CACHE_EXPIRY_KEY);
  
  if (cachedToken && expiryTime) {
    const now = new Date().getTime();
    if (now < parseInt(expiryTime)) {
      Logger.log('Using cached token');
      return cachedToken;
    }
  }
  
  Logger.log('Fetching new token');
  return fetchNewToken();
}

function fetchNewToken() {
  const payload = {
    grant_type: 'client_credentials',
    client_id: CONFIG.OAUTH_CLIENT_ID,
    client_secret: CONFIG.OAUTH_CLIENT_SECRET
  };
  
  const options = {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  };
  
  try {
    Logger.log('Requesting token from: ' + CONFIG.OAUTH_TOKEN_URL);
    const response = UrlFetchApp.fetch(CONFIG.OAUTH_TOKEN_URL, options);
    const statusCode = response.getResponseCode();
    const responseBody = response.getContentText();
    
    Logger.log('Status Code: ' + statusCode);
    Logger.log('Response Body: ' + responseBody);
    
    if (statusCode !== 200) {
      throw new Error('OAuth token request failed: ' + statusCode + ' - ' + responseBody);
    }
    
    const data = JSON.parse(responseBody);
    const accessToken = data.access_token;
    const expiresIn = data.expires_in || 3600;
    
    cacheToken(accessToken, expiresIn);
    return accessToken;
    
  } catch (error) {
    Logger.log('Error fetching token: ' + error.toString());
    throw error;
  }
}

function cacheToken(token, expiresInSeconds) {
  const cache = PropertiesService.getUserProperties();
  const expiryTime = new Date().getTime() + (expiresInSeconds * 1000) - 60000;
  cache.setProperty(CONFIG.TOKEN_CACHE_KEY, token);
  cache.setProperty(CONFIG.TOKEN_CACHE_EXPIRY_KEY, expiryTime.toString());
  Logger.log('Token cached until: ' + new Date(expiryTime));
}

function clearToken() {
  const cache = PropertiesService.getUserProperties();
  cache.deleteProperty(CONFIG.TOKEN_CACHE_KEY);
  cache.deleteProperty(CONFIG.TOKEN_CACHE_EXPIRY_KEY);
  Logger.log('Token cleared');
}

function testAuth() {
  try {
    const token = getAccessToken();
    Logger.log('Auth test successful. Token: ' + token.substring(0, 20) + '...');
    return true;
  } catch (error) {
    Logger.log('Auth test failed: ' + error.toString());
    return false;
  }
}
