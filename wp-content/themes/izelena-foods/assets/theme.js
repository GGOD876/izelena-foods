(function () {
  var cart = [];

  function money(value) {
    return 'J$' + Number(value || 0).toLocaleString();
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, function (character) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character];
    });
  }

  function updateCount() {
    document.querySelectorAll('.cart span').forEach(function (count) {
      count.textContent = cart.length;
    });
  }

  function cartMarkup() {
    var lines = cart.map(function (item, index) {
      return '<div class="cart-line"><div class="mini ' + escapeHtml(item.tone) + '">' + escapeHtml(item.name.charAt(0)) + '</div><div><b>' + escapeHtml(item.name) + '</b><span>' + money(item.price) + '</span></div><button type="button" data-remove-cart="' + index + '">Remove</button></div>';
    }).join('');
    if (!cart.length) return '<div class="empty"><p>Your cart is waiting for a little island flavour.</p></div>';
    return lines + '<div class="cart-total"><span>Estimated total</span><strong>' + money(cart.reduce(function (total, item) { return total + Number(item.price || 0); }, 0)) + '</strong></div><button class="btn primary full" type="button" disabled title="Checkout will be enabled when payment is connected">Checkout coming soon</button>';
  }

  function renderCart() {
    var drawer = document.querySelector('.drawer');
    if (!drawer) return;
    drawer.querySelector('h2').innerHTML = 'Cart <em>(' + cart.length + ')</em>';
    var content = drawer.querySelector('[data-cart-content]');
    if (content) content.innerHTML = cartMarkup();
    updateCount();
  }

  function openCart() {
    if (document.querySelector('.overlay')) return;
    var overlay = document.createElement('div');
    overlay.className = 'overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Shopping cart');
    overlay.innerHTML = '<aside class="drawer"><button class="close" type="button" aria-label="Close shopping cart">&times;</button><p class="eyebrow">Your selection</p><h2>Cart <em>(' + cart.length + ')</em></h2><div data-cart-content>' + cartMarkup() + '</div></aside>';
    document.body.appendChild(overlay);
    overlay.querySelector('.close').focus();
  }

  function closeOverlay() {
    var overlay = document.querySelector('.overlay');
    if (overlay) overlay.remove();
  }

  function addProduct(card) {
    var button = card.querySelector('.add-btn');
    if (button && button.disabled) return;
    cart.push({
      name: card.getAttribute('data-product-name') || 'Izelena flavour',
      price: card.getAttribute('data-product-price') || 0,
      tone: card.getAttribute('data-product-tone') || 'red'
    });
    updateCount();
    if (document.querySelector('.overlay')) renderCart();
  }

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('.menu-toggle');
    if (toggle) {
      var nav = document.querySelector('.site-nav');
      if (nav) {
        var open = nav.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
      }
      return;
    }

    var cartLink = event.target.closest('[data-cart-trigger], .cart');
    if (cartLink) {
      event.preventDefault();
      openCart();
      return;
    }

    var remove = event.target.closest('[data-remove-cart]');
    if (remove) {
      cart.splice(Number(remove.getAttribute('data-remove-cart')), 1);
      renderCart();
      return;
    }

    var add = event.target.closest('.add-btn[data-demo-add]');
    if (add) {
      event.preventDefault();
      event.stopPropagation();
      addProduct(add.closest('.product-card'));
      return;
    }

    var detailAdd = event.target.closest('[data-detail-add]');
    if (detailAdd) {
      cart.push({
        name: detailAdd.getAttribute('data-product-name') || 'Izelena flavour',
        price: detailAdd.getAttribute('data-product-price') || 0,
        tone: detailAdd.getAttribute('data-product-tone') || 'red'
      });
      updateCount();
      openCart();
      return;
    }

    if (event.target.closest('.overlay') && !event.target.closest('.drawer')) closeOverlay();
    if (event.target.closest('.close')) closeOverlay();
    if (event.target.closest('.site-nav a') && !event.target.closest('.cart')) {
      var menu = document.querySelector('.site-nav');
      var menuToggle = document.querySelector('.menu-toggle');
      if (menu) menu.classList.remove('open');
      if (menuToggle) menuToggle.setAttribute('aria-expanded', 'false');
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeOverlay();
  });
}());
