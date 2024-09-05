const { VIRTUAL_HOST } = process.env;
const BASE_URL = 'https://' + VIRTUAL_HOST;

// Define viewports outside of the module.exports object so scenarios can use
// only relevant viewports.
const viewports = [
  // {
  //   "label": "mobile",
  //   "width": 320,
  //   "height": 480
  // },
  // {
  //   "label": "mobile-lg",
  //   "width": 480,
  //   "height": 360
  // },
  // {
  //   "label": "tablet",
  //   "width": 640,
  //   "height": 850
  // },
  // {
  //   "label": "tablet-lg",
  //   "width": 800,
  //   "height": 600
  // },
  // {
  //   "label": "desktop",
  //   "width": 1024,
  //   "height": 768
  // },
  {
    "label": "desktop-lg",
    "width": 1200,
    "height": 900
  },
  {
    "label": "widescreen",
    "width": 1400,
    "height": 1050
  }
];

const mobileAndTabletViewports = [
  "mobile",
  "mobile-lg",
  "tablet",
  "tablet-lg"
];

/**
 * Get viewports by labels.
 * @param {string} labels - The labels of the viewports to retrieve.
 * @returns {Object} - A list of viewports matching the provided labels.
 */
const getViewports = (labels) => {
  return viewports.filter(viewport => labels.includes(viewport.label));
};

module.exports = {
  "id": "backstop_default",
  "viewports": viewports,
  "onBeforeScript": "puppet/onBefore.js",
  "onReadyScript": "puppet/onReady.js",
  "scenarioDefaults": {
    "cookiePath": "backstop_data/engine_scripts/cookies.json",
    "referenceUrl": "",
    "readySelector": "",
    "delay": 5000,
    "hideSelectors": [],
    "removeSelectors": [],
    "hoverSelector": "",
    "clickSelector": "",
    "postInteractionWait": 0,
    "selectors": [],
    "selectorExpansion": true,
    "expect": 0,
    "misMatchThreshold" : 0.1,
    "requireSameDimensions": false,
  },
  "scenarios": [
    {
      label: 'Homepage',
      url: BASE_URL
    }
  ],
  "paths": {
    "bitmaps_reference": "backstop_data/bitmaps_reference",
    "bitmaps_test": "backstop_data/bitmaps_test",
    "engine_scripts": "backstop_data/engine_scripts",
    "html_report": "backstop_data/html_report",
    "ci_report": "backstop_data/ci_report"
  },
  "report": ["browser"],
  "engine": "puppeteer",
  "engineOptions": {
    "args": [
      // "--enable-logging",
      "--no-sandbox",
      "--disable-setuid-sandbox",
      // "--disable-dev-shm-usage",
      // "--ignore-certificate-errors",
      "--disable-web-security",
      // "--enable-features=NetworkService",
      "--disable-gpu",
    ],
    "headless": "new",
    "ignoreHTTPSErrors": true,
    "gotoParameters": { "waitUntil": "networkidle0" }
  },
  "asyncCaptureLimit": 5,
  "asyncCompareLimit": 25,
  "debug": false,
  "debugWindow": false,
  "scenarioLogsInReports": true
}

