module.exports = async (page, scenario, vp) => {
  // Login user.
  let extraHeaders = {
    'X-Backstop-Test': '1',
  };
  if (scenario.loginAs) {
    extraHeaders['X-Automated-Test-User'] = scenario.loginAs;
  }
  if (scenario.suppressToolbar) {
    extraHeaders['X-Automated-Test-Suppress-Toolbar'] = '1';
  }
  if (Object.keys(extraHeaders).length > 0) {
    await page.setExtraHTTPHeaders(extraHeaders);
  }

  page
    .on('console', message =>
      console.log(`${message.type().substr(0, 3).toUpperCase()} ${message.text()}`));
};
