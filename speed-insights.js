/**
 * Vercel Speed Insights initialization for vanilla JavaScript
 * This script initializes Vercel Speed Insights by loading the tracking script
 * Documentation: https://vercel.com/docs/speed-insights/quickstart
 */

(function() {
  'use strict';

  // Only run in production (when deployed to Vercel)
  // Speed Insights will not track data in development mode
  const isDevelopment = window.location.hostname === 'localhost' || 
                        window.location.hostname === '127.0.0.1' ||
                        window.location.hostname.includes('.local');

  // Initialize the queue for Speed Insights
  window.si = window.si || function() {
    (window.siq = window.siq || []).push(arguments);
  };

  // Determine the script source based on environment
  const scriptSrc = isDevelopment
    ? 'https://va.vercel-scripts.com/v1/speed-insights/script.debug.js'
    : 'https://va.vercel-scripts.com/v1/speed-insights/script.js';

  // Create and inject the Speed Insights script
  const script = document.createElement('script');
  script.src = scriptSrc;
  script.defer = true;
  script.setAttribute('data-sdkn', '@vercel/speed-insights');
  script.setAttribute('data-sdkv', '2.0.0');

  // Add error handling
  script.onerror = function() {
    if (isDevelopment) {
      console.warn('Vercel Speed Insights: Failed to load tracking script');
    }
  };

  // Inject the script into the document head
  if (document.head) {
    document.head.appendChild(script);
  } else {
    // Fallback if head is not available yet
    document.addEventListener('DOMContentLoaded', function() {
      document.head.appendChild(script);
    });
  }

  if (isDevelopment) {
    console.log('Vercel Speed Insights initialized (debug mode)');
  }
})();
