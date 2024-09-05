module.exports = async (page, scenario, vp) => {
  console.log('SCENARIO > ' + scenario.label);
  const scrollToSelector = scenario.scrollToSelector;
  await require('./clickAndHoverHelper')(page, scenario);

  if (!scenario.suppressToolbar) {
    await page.evaluate(() => {
      document.getElementsByTagName('body')[0].classList.remove('toolbar-fixed');
    });
  }

  if (scrollToSelector) {
    await page.waitForSelector(scrollToSelector);
    await page.evaluate(scrollToSelector => {
      document.querySelector(scrollToSelector).scrollIntoView();
    }, scrollToSelector);
  }
};
