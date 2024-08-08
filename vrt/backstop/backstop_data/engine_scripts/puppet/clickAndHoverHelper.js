module.exports = async (page, scenario) => {
  const hoverSelector = scenario.hoverSelectors || scenario.hoverSelector;
  const clickSelector = scenario.clickSelectors || scenario.clickSelector;
  const keyPressSelector = scenario.keyPressSelectors || scenario.keyPressSelector;
  const postInteractionWait = scenario.postInteractionWait; // selector [str] | ms [int]

  if (keyPressSelector) {
    for (const keyPressSelectorItem of [].concat(keyPressSelector)) {
      await page.waitForSelector(keyPressSelectorItem.selector);
      await page.type(keyPressSelectorItem.selector, keyPressSelectorItem.keyPress);
    }
  }

  if (hoverSelector) {
    for (const hoverSelectorIndex of [].concat(hoverSelector)) {
      await page.waitForSelector(hoverSelectorIndex);
      await page.hover(hoverSelectorIndex);
    }
  }

  if (clickSelector) {
    for (const clickSelectorIndex of [].concat(clickSelector)) {
      await page.waitForSelector(clickSelectorIndex);
      await page.click(clickSelectorIndex);
    }
  }

  // This is a custom implementation of postInteractionWait that allows for
  // an array, a number, or a string.
  //
  // If postInteractionWait is an array, it must contain exactly one string and one number.
  //
  // Example usage:
  // - "postInteractionWait": ['.my-class', 5000],
  // - "postInteractionWait": 5000,
  // - "postInteractionWait": '.my-class',
  if (postInteractionWait) {
    if (Array.isArray(postInteractionWait)) {
      // If postInteractionWait is an array, check if it contains exactly one string and one number.
      const stringCount = postInteractionWait.filter(value => typeof value === 'string').length;
      const numberCount = postInteractionWait.filter(value => typeof value === 'number').length;
      // If the array contains exactly one string and one number, use them as selector and delay.
      if (postInteractionWait.length === 2 && stringCount === 1 && numberCount === 1) {
        const selector = postInteractionWait.find(value => typeof value === 'string');
        const delay = postInteractionWait.find(value => typeof value === 'number');
        // Wait for the selector to appear first and then wait for the specified time.
        await page.waitForSelector(selector).then(() => {
          return new Promise(resolve => setTimeout(resolve, delay));
        });
      } else {
        const generalErrorText = 'When using an array for postInteractionWait, you must include one string (representing a CSS selector) and one number (representing delay time in milliseconds).';
        throw new Error(generalErrorText);
      }
  } else if (parseInt(postInteractionWait) > 0) {
      // If the postInteractionWait is a number, wait for that amount of time.
      await new Promise(resolve => setTimeout(resolve, postInteractionWait));
    } else {
      // If the postInteractionWait is a string, wait for the selector to appear.
      await page.waitForSelector(postInteractionWait);
    }
  }
};
