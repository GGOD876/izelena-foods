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
      count.textContent = totalCartItems();
    });
  }

  function totalCartItems() {
    return cart.reduce(function (total, item) {
      return total + Number(item.quantity || 1);
    }, 0);
  }

  function showToast(message, type) {
    var region = document.querySelector('.izelena-toast-region');
    if (!region) {
      region = document.createElement('div');
      region.className = 'izelena-toast-region';
      region.setAttribute('aria-live', 'polite');
      region.setAttribute('aria-atomic', 'true');
      document.body.appendChild(region);
    }
    var toast = document.createElement('div');
    toast.className = 'izelena-toast izelena-toast-' + (type === 'error' ? 'error' : 'success');
    toast.setAttribute('role', 'status');
    toast.innerHTML = '<span aria-hidden="true">' + (type === 'error' ? '!' : '✓') + '</span><p>' + escapeHtml(message) + '</p><button type="button" aria-label="Dismiss notification">&times;</button>';
    region.appendChild(toast);
    var dismiss = toast.querySelector('button');
    var timer = window.setTimeout(function () { toast.remove(); }, 4200);
    if (dismiss) dismiss.addEventListener('click', function () { window.clearTimeout(timer); toast.remove(); });
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

  function addProduct(product, quantity) {
    if (!product || product.soon) {
      showToast('This flavour is not available yet.', 'error');
      return false;
    }
    var raw = Number(quantity === undefined ? 1 : quantity);
    if (!Number.isFinite(raw) || raw < 1) {
      showToast('Choose a quantity of at least 1.', 'error');
      return false;
    }
    var amount = Math.min(999999, Math.floor(raw));
    var next = {
      id: product.id || '',
      name: product.name || 'Izelena flavour',
      price: Number(product.price || 0),
      tone: product.tone || 'red',
      quantity: amount
    };
    var existing = cart.find(function (item) {
      if (next.id && item.id) return String(item.id) === String(next.id);
      return item.name === next.name && Number(item.price || 0) === next.price && item.tone === next.tone;
    });
    if (existing) {
      existing.quantity = Math.min(999999, Number(existing.quantity || 1) + amount);
    } else {
      cart.push(next);
    }
    updateCount();
    if (document.querySelector('.drawer')) renderCart();
    showToast(next.name + ' added to your selection' + (amount > 1 ? ' · quantity ' + amount.toLocaleString() : '') + '.', 'success');
    return true;
  }

  function addProductCard(card) {
    if (!card) {
      showToast('We could not add that flavour. Please try again.', 'error');
      return false;
    }
    var button = card.querySelector('.add-btn');
    if (button && button.disabled) {
      showToast('This flavour is not available yet.', 'error');
      return false;
    }
    return addProduct(productFromCard(card));
  }

  function cartMarkup() {
    var lines = cart.map(function (item, index) {
      var quantity = Number(item.quantity || 1);
      var subtotal = Number(item.price || 0) * quantity;
      return '<div class="cart-line"><div class="mini ' + escapeHtml(item.tone) + '">' + escapeHtml(item.name.charAt(0)) + '</div><div><b>' + escapeHtml(item.name) + '</b><span>Unit price ' + money(item.price) + '</span><span class="cart-quantity">Quantity</span><strong class="cart-subtotal">Line subtotal ' + money(subtotal) + '</strong></div><div class="cart-actions"><div class="quantity-controls" aria-label="' + escapeHtml(item.name) + ' quantity"><button type="button" data-cart-quantity="' + index + '" data-quantity-change="-1" aria-label="Decrease ' + escapeHtml(item.name) + ' quantity">−</button><input type="number" min="1" max="999999" value="' + quantity + '" data-cart-quantity-input="' + index + '" aria-label="' + escapeHtml(item.name) + ' quantity"><button type="button" data-cart-quantity="' + index + '" data-quantity-change="1" aria-label="Increase ' + escapeHtml(item.name) + ' quantity">+</button></div><button class="cart-remove" type="button" data-remove-cart="' + index + '">Remove</button></div></div>';
    }).join('');
    if (!cart.length) return '<div class="empty"><p>Your cart is waiting for a little island flavour.</p></div>';
    return lines + '<div class="cart-total"><span>Estimated total</span><strong>' + money(cart.reduce(function (total, item) { return total + Number(item.price || 0) * Number(item.quantity || 1); }, 0)) + '</strong></div><button class="btn primary full" type="button" disabled title="Checkout will be enabled when payment is connected">Checkout coming soon</button>';
  }

  function renderCart() {
    var drawer = document.querySelector('.drawer');
    if (!drawer) return;
    drawer.querySelector('h2').innerHTML = 'Cart <em>(' + totalCartItems() + ')</em>';
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

  function addModalQuantityControls(overlay, product) {
    var modalAdd = overlay.querySelector('[data-modal-add]');
    var modalQuantity = document.createElement('div');
    modalQuantity.className = 'modal-quantity';
    modalQuantity.innerHTML = '<span>Quantity</span><div class="quantity-controls" aria-label="' + escapeHtml(product.name) + ' quantity"><button type="button" data-modal-quantity-change="-1" aria-label="Decrease ' + escapeHtml(product.name) + ' quantity">-</button><input type="number" min="1" max="999999" value="1" data-modal-quantity aria-label="' + escapeHtml(product.name) + ' quantity"><button type="button" data-modal-quantity-change="1" aria-label="Increase ' + escapeHtml(product.name) + ' quantity">+</button></div>';
    if (modalAdd) modalAdd.parentNode.insertBefore(modalQuantity, modalAdd);
    updateModalPrice(overlay);
  }

  function updateModalPrice(overlay) {
    if (!overlay || !overlay.izelenaProduct) return;
    var input = overlay.querySelector('[data-modal-quantity]');
    var price = overlay.querySelector('.modal-price');
    if (!input || !price) return;
    var quantity = Math.max(1, Math.min(999999, Number(input.value) || 1));
    var unit = Number(overlay.izelenaProduct.price || 0);
    price.textContent = (quantity === 1 ? 'Unit price ' : 'Subtotal ') + money(unit * quantity) + (quantity > 1 ? ' (' + quantity + ' × ' + money(unit) + ')' : '');
  }

  function prepareOverlay(overlay, trigger) {
    closeOverlay();
    returnFocus = trigger || document.activeElement;
    document.body.appendChild(overlay);
    var close = overlay.querySelector('.close');
    if (close) close.focus();
    if (overlay.izelenaProduct) addModalQuantityControls(overlay, overlay.izelenaProduct);
  }

  function resetContactForm(form) {
    form.reset();
    form.classList.remove('is-success');
    form.querySelectorAll('label, form > button').forEach(function (field) { field.hidden = false; });
    var feedback = form.querySelector('[data-contact-feedback]');
    if (feedback) {
      feedback.hidden = true;
      feedback.className = 'contact-feedback';
      feedback.textContent = '';
    }
  }

  function showContactFeedback(form, message, success) {
    var feedback = form.querySelector('[data-contact-feedback]');
    if (!feedback) return;
    feedback.hidden = false;
    if (success) {
      form.classList.add('is-success');
      form.querySelectorAll('label, form > button').forEach(function (field) { field.hidden = true; });
      feedback.className = 'success contact-feedback';
      feedback.innerHTML = '<span aria-hidden="true">✓</span><h2>Message received.</h2><p>' + escapeHtml(message) + '</p><button type="button" class="text-btn" data-contact-reset>Send another <span aria-hidden="true">↗</span></button>';
    } else {
      feedback.className = 'contact-feedback contact-feedback-error';
      feedback.textContent = message;
    }
  }

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-contact-form]');
    if (!form || !window.izelenaConfig || !window.izelenaConfig.ajaxUrl) return;
    event.preventDefault();
    var submit = form.querySelector('button[type="submit"]');
    if (submit) submit.disabled = true;
    var payload = new FormData(form);
    payload.set('action', 'izelena_contact_submit');
    if (window.izelenaConfig.contactNonce) payload.set('izelena_contact_nonce', window.izelenaConfig.contactNonce);
    fetch(window.izelenaConfig.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: payload })
      .then(function (response) {
        return response.json().then(function (body) {
          if (!response.ok || !body.success) throw new Error(body.data && body.data.message ? body.data.message : 'We could not submit your message. Please try again.');
          return body;
        });
      })
      .then(function (body) { showContactFeedback(form, body.data.message, true); })
      .catch(function (error) {
        showContactFeedback(form, error.message, false);
        if (submit) submit.disabled = false;
      });
  });

  function openCart(trigger) {
    var overlay = document.createElement('div');
    overlay.className = 'overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Shopping cart');
    overlay.innerHTML = '<aside class="drawer"><button class="close" type="button" aria-label="Close shopping cart">&times;</button><p class="eyebrow">Your selection</p><h2>Cart <em>(' + totalCartItems() + ')</em></h2><div data-cart-content>' + cartMarkup() + '</div></aside>';
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

  document.addEventListener('input', function (event) {
    var modalInput = event.target.closest('[data-modal-quantity]');
    if (modalInput) updateModalPrice(modalInput.closest('.overlay'));
  });

  document.addEventListener('change', function (event) {
    var cartInput = event.target.closest('[data-cart-quantity-input]');
    if (cartInput) {
      var cartIndex = Number(cartInput.getAttribute('data-cart-quantity-input'));
      var cartItem = cart[cartIndex];
      if (cartItem) cartItem.quantity = Math.max(1, Math.min(999999, Number(cartInput.value) || 1));
      renderCart();
      return;
    }
    var modalInput = event.target.closest('[data-modal-quantity]');
    if (modalInput) {
      modalInput.value = Math.max(1, Math.min(999999, Number(modalInput.value) || 1));
      updateModalPrice(modalInput.closest('.overlay'));
    }
  });

  document.addEventListener('blur', function (event) {
    var cartInput = event.target.closest('[data-cart-quantity-input]');
    if (cartInput) {
      var cartIndex = Number(cartInput.getAttribute('data-cart-quantity-input'));
      var cartItem = cart[cartIndex];
      if (cartItem) cartItem.quantity = Math.max(1, Math.min(999999, Number(cartInput.value) || 1));
      renderCart();
      return;
    }
    var modalInput = event.target.closest('[data-modal-quantity]');
    if (modalInput) {
      modalInput.value = Math.max(1, Math.min(999999, Number(modalInput.value) || 1));
      updateModalPrice(modalInput.closest('.overlay'));
    }
  }, true);

  document.addEventListener('click', function (event) {
    var contactReset = event.target.closest('[data-contact-reset]');
    if (contactReset) {
      resetContactForm(contactReset.closest('[data-contact-form]'));
      return;
    }

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

    var cartQuantityButton = event.target.closest('[data-cart-quantity][data-quantity-change]');
    if (cartQuantityButton) {
      var cartIndex = Number(cartQuantityButton.getAttribute('data-cart-quantity'));
      var cartItem = cart[cartIndex];
      if (cartItem) cartItem.quantity = Math.max(1, Math.min(999999, Number(cartItem.quantity || 1) + Number(cartQuantityButton.getAttribute('data-quantity-change') || 0)));
      renderCart();
      return;
    }

    var modalQuantityButton = event.target.closest('[data-modal-quantity-change]');
    if (modalQuantityButton) {
      var modalQuantityInput = modalQuantityButton.closest('.quantity-controls').querySelector('[data-modal-quantity]');
      if (modalQuantityInput) {
        modalQuantityInput.value = Math.max(1, Math.min(999999, Number(modalQuantityInput.value || 1) + Number(modalQuantityButton.getAttribute('data-modal-quantity-change') || 0)));
        updateModalPrice(modalQuantityButton.closest('.overlay'));
      }
      return;
    }

    var modalAdd = event.target.closest('[data-modal-add]');
    if (modalAdd) {
      var modalOverlay = modalAdd.closest('.overlay');
      var added = false;
      if (modalOverlay && modalOverlay.izelenaProduct) {
        var quantityInput = modalOverlay.querySelector('[data-modal-quantity]');
        added = addProduct(modalOverlay.izelenaProduct, quantityInput ? Math.max(1, Math.min(999999, Number(quantityInput.value) || 1)) : 1);
      }
      if (added) closeOverlay(modalOverlay);
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
      var detailAdded = addProduct({
        id: detailAdd.getAttribute('data-product-id') || '',
        name: detailAdd.getAttribute('data-product-name') || 'Izelena flavour',
        price: detailAdd.getAttribute('data-product-price') || 0,
        tone: detailAdd.getAttribute('data-product-tone') || 'red'
      });
      if (detailAdded) openCart(detailAdd);
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
    var cartInput = event.target.closest('[data-cart-quantity-input]');
    if (cartInput && event.key === 'Enter') {
      event.preventDefault();
      cartInput.blur();
      return;
    }
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
