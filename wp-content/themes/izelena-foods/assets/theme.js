(function () {
  var cart = [];
  var returnFocus = null;
  var heatLabels = {mild: 'Tups', medium: 'Nuh 2 Much', hot: 'Whole Heap'};

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

  function productFromCard(card) {
    return {
      id: card.getAttribute('data-product-id') || '',
      name: card.getAttribute('data-product-name') || 'Izelena flavour',
      tag: card.getAttribute('data-product-tag') || 'Izelena flavour collection',
      description: card.getAttribute('data-product-description') || 'Jamaican flavour for every season.',
      price: Number(card.getAttribute('data-product-price') || 0),
      heat: card.getAttribute('data-product-heat') || 'medium',
      tone: card.getAttribute('data-product-tone') || 'red',
      initials: card.getAttribute('data-product-initials') || '',
      url: card.getAttribute('data-product-url') || '',
      soon: card.getAttribute('data-product-soon') === '1'
    };
  }

  function addProduct(product) {
    cart.push({
      name: product.name || 'Izelena flavour',
      price: Number(product.price || 0),
      tone: product.tone || 'red'
    });
    updateCount();
    if (document.querySelector('.drawer')) renderCart();
  }

  function addProductCard(card) {
    var button = card.querySelector('.add-btn');
    if (button && button.disabled) return;
    addProduct(productFromCard(card));
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

  function closeOverlay(overlay) {
    var target = overlay || document.querySelector('.overlay');
    if (target) target.remove();
    if (returnFocus && document.contains(returnFocus)) returnFocus.focus();
    returnFocus = null;
  }

  function prepareOverlay(overlay, trigger) {
    closeOverlay();
    returnFocus = trigger || document.activeElement;
    document.body.appendChild(overlay);
    var close = overlay.querySelector('.close');
    if (close) close.focus();
  }

  function openCart(trigger) {
    var overlay = document.createElement('div');
    overlay.className = 'overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Shopping cart');
    overlay.innerHTML = '<aside class="drawer"><button class="close" type="button" aria-label="Close shopping cart">&times;</button><p class="eyebrow">Your selection</p><h2>Cart <em>(' + cart.length + ')</em></h2><div data-cart-content>' + cartMarkup() + '</div></aside>';
    prepareOverlay(overlay, trigger);
  }

  function openProductModal(trigger) {
    var card = trigger.querySelector('.product-card');
    if (!card) return;
    var product = productFromCard(card);
    var heat = heatLabels[product.heat] || product.heat;
    var overlay = document.createElement('div');
    overlay.className = 'overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', product.name + ' details');
    overlay.izelenaProduct = product;
    overlay.innerHTML = '<div class="modal"><button class="close" type="button" aria-label="Close product details">&times;</button><div class="modal-art ' + escapeHtml(product.tone) + '"><span>' + escapeHtml(product.initials) + '</span></div><div><p class="eyebrow">' + escapeHtml(product.tag) + ' · ' + escapeHtml(heat) + ' heat</p><h2>' + escapeHtml(product.name) + '</h2><p>' + escapeHtml(product.description) + '</p><label class="modal-select">Size / packaging<select><option>Retail jar · 10 oz</option><option>Family bottle · 18 oz</option><option>Food service · 1 gallon</option></select></label><strong class="modal-price">From ' + money(product.price) + '</strong><button class="btn primary full" type="button" data-modal-add>Add to cart <span>+</span></button></div></div>';
    prepareOverlay(overlay, trigger);
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
      openCart(cartLink);
      return;
    }

    var close = event.target.closest('.close');
    if (close) {
      closeOverlay(close.closest('.overlay'));
      return;
    }

    var remove = event.target.closest('[data-remove-cart]');
    if (remove) {
      cart.splice(Number(remove.getAttribute('data-remove-cart')), 1);
      renderCart();
      return;
    }

    var modalAdd = event.target.closest('[data-modal-add]');
    if (modalAdd) {
      var modalOverlay = modalAdd.closest('.overlay');
      if (modalOverlay && modalOverlay.izelenaProduct) addProduct(modalOverlay.izelenaProduct);
      closeOverlay(modalOverlay);
      return;
    }

    var add = event.target.closest('.add-btn[data-demo-add]');
    if (add) {
      event.preventDefault();
      event.stopPropagation();
      var shopCardTrigger = add.closest('.shop-product-trigger');
      if (shopCardTrigger) {
        openProductModal(shopCardTrigger);
        return;
      }
      addProductCard(add.closest('.product-card'));
      return;
    }

    var detailAdd = event.target.closest('[data-detail-add]');
    if (detailAdd) {
      addProduct({
        name: detailAdd.getAttribute('data-product-name') || 'Izelena flavour',
        price: detailAdd.getAttribute('data-product-price') || 0,
        tone: detailAdd.getAttribute('data-product-tone') || 'red'
      });
      openCart(detailAdd);
      return;
    }

    var shopTrigger = event.target.closest('.shop-product-trigger');
    if (shopTrigger && !event.target.closest('.add-btn')) {
      var productLink = event.target.closest('a');
      if (productLink && (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey)) return;
      event.preventDefault();
      openProductModal(shopTrigger);
      return;
    }

    if (event.target.classList.contains('overlay')) {
      closeOverlay(event.target);
      return;
    }

    if (event.target.closest('.site-nav a') && !event.target.closest('.cart')) {
      var menu = document.querySelector('.site-nav');
      var menuToggle = document.querySelector('.menu-toggle');
      if (menu) menu.classList.remove('open');
      if (menuToggle) menuToggle.setAttribute('aria-expanded', 'false');
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeOverlay();
      return;
    }
    var trigger = event.target.closest('.shop-product-trigger');
    if (trigger && (event.key === 'Enter' || event.key === ' ')) {
      event.preventDefault();
      openProductModal(trigger);
    }
  });
}());
