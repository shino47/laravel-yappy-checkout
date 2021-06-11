(function () {
  var button = document.getElementById('Yappy_Checkout_Button');

  if (!button) {
    return;
  }

  document.getElementsByTagName('head')[0].insertAdjacentHTML(
    'beforeend',
    '<link rel="stylesheet" href="https://pagosbg.bgeneral.com/assets/css/styles.css" />'
  );

  var themes = {
    brand: 'yappy-logo-brand.svg',
    dark: 'yappy-logo-dark.svg'
  };

  var theme = button.dataset.color && themes.hasOwnProperty(button.dataset.color)
    ? button.dataset.color
    : 'brand';

  var logo = themes[theme];

  var image = '<img src="https://pagosbg.bgeneral.com/assets/img/' + logo + '" />';

  var text = button.dataset.hasOwnProperty('donacion') ? 'Donar' : 'Pagar';

  button.classList.add('ecommerce', 'yappy', theme);
  button.innerHTML = text + '&nbsp;con&nbsp;' + image;
})();
