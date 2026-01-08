export const environment = {
  production: true,
  apiUrl: '/api', // In production, API is usually on same domain
  wsHost: window.location.hostname,
  wsPort: 443,
  reverbAppKey: 'app-key', // Should be replaced in deployment
};
