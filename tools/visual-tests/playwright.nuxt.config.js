const { defineConfig, devices } = require('@playwright/test');

const baseURL = process.env.PTS_NUXT_BASE_URL || 'http://127.0.0.1:3100';
// The cart tests change session state, so this suite never runs against a live site.
if (!/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?\/?$/.test(baseURL)) throw new Error(`Nuxt smoke tests run locally only: ${baseURL}`);

module.exports = defineConfig({
  testDir: './nuxt-tests',
  testMatch: '**/*.spec.js',
  outputDir: './test-results/nuxt',
  workers: 1,
  retries: 0,
  timeout: 60000,
  reporter: [['list']],
  use: { baseURL, locale: 'vi-VN', screenshot: 'only-on-failure' },
  projects: [
    { name: 'phone', use: { ...devices['Pixel 7'] } },
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
  ],
});
