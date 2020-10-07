/**
 * Variant element class.
 */
class Variant extends HTMLElement {
  connectedCallback() {
    let variant = window.localStorage.getItem('variant-group');
    const type = window.localStorage.getItem('variant-type');

    // Check if a user is assigned to a variant.
    if (
      null === variant
      || type !== this.getAttribute('data-type')
    ) {
      const trafficPercentage = this.getAttribute('data-type') || 50;

      // Get an number between 0-100.
      const randNum = Math.floor(Math.random() * 100) + 1;

      // Get the variant based on whether the random number was
      // higher or lower than the desired traffic perecentage.
      variant = ( randNum > trafficPercentage ? 'a' : 'b');

      window.localStorage.setItem(
        'variant-group',
        variant
      );
      window.localStorage.setItem(
        'variant-type',
        this.getAttribute('data-type')
      );
    }

    const test = 'b' === variant ? 'variant' : 'control';

    // Replace the contents of our <variant> element.
    this.outerHTML = JSON.parse(this.getAttribute(test));
  }
}

window.customElements.define('variant-test', Variant);

// Remove local storage if there is no active test.
if (
  typeof variantsActiveTest !== 'undefined'
  && 0 === variantsActiveTest
  && (
    null !== window.localStorage.getItem('variant-group')
    || null !== window.localStorage.getItem('variant-type')
  )
) {
  window.localStorage.removeItem('variant-group');
  window.localStorage.removeItem('variant-type');
}
