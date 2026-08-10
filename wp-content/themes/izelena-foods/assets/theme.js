(function () {
  var demoCart = [];
  var serverCart = {enabled: false, items: [], count: 0, total_html: '', checkout_url: '', checkout_enabled: false};
  var returnFocus = null;
  var heatLabels = {mild: 'Mild', medium: 'Medium', hot: 'Hot'};

  function config() { return window.izelenaConfig || {}; }
  function isWooCommerce() { return !!config().woocommerce && !config().demoMode; }
  function escapeHtml(value) { return String(value == null ? '' : value).replace(/[&<>'"]/g, function (character) { return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]; }); }
  function safeImageUrl(value) {
    if (!value) return '';
    try { var parsed = new URL(String(value), document.baseURI); return ['http:', 'https:'].indexOf(parsed.protocol) >= 0 ? parsed.href : ''; } catch (error) { return ''; }
  }
  function money(value) { return 'J$' + Number(value || 0).toLocaleString(); }
  function normalizeQuantity(value) {
    var raw = String(value == null ? '' : value).trim();
    if (!/^\d+$/.test(raw)) return 1;
    return Math.max(1, Math.min(999999, Number(raw)));
  }
  function variationMaxQuantity(variation) {
    if (!variation || !variation.manages_stock || variation.stock_quantity === null || variation.stock_quantity === undefined) return 999999;
    return Math.max(0, Number(variation.stock_quantity) || 0);
  }
  function clampQuantity(value, maximum) {
    var quantity = normalizeQuantity(value);
    return maximum > 0 && maximum < 999999 ? Math.min(quantity, maximum) : quantity;
  }
  function toast(message, type) {
    var region = document.querySelector('.izelena-toast-region');
    if (!region) { region = document.createElement('div'); region.className = 'izelena-toast-region'; region.setAttribute('aria-live', 'polite'); document.body.appendChild(region); }
    var item = document.createElement('div'); item.className = 'izelena-toast izelena-toast-' + (type === 'error' ? 'error' : 'success'); item.setAttribute('role', 'status'); item.innerHTML = '<span aria-hidden="true">' + (type === 'error' ? '!' : 'OK') + '</span><p>' + escapeHtml(message) + '</p><button type="button" aria-label="Dismiss notification">&times;</button>'; region.appendChild(item);
    var timer = window.setTimeout(function () { item.remove(); }, 4200); item.querySelector('button').addEventListener('click', function () { window.clearTimeout(timer); item.remove(); });
  }
  function demoCount() { return demoCart.reduce(function (sum, item) { return sum + Number(item.quantity || 1); }, 0); }
  function totalCount() { return isWooCommerce() ? Number(serverCart.count || 0) : demoCount(); }
  function updateCount() { document.querySelectorAll('.cart span').forEach(function (node) { node.textContent = totalCount(); }); }
  function parseVariations(card) { try { return JSON.parse(card.getAttribute('data-product-variations') || '[]') || []; } catch (error) { return []; } }
  function productFromElement(element) {
    var variations = parseVariations(element);
    return {id: element.getAttribute('data-product-id') || '', name: element.getAttribute('data-product-name') || 'Izelena flavour', tag: element.getAttribute('data-product-tag') || 'Izelena flavour collection', description: element.getAttribute('data-product-description') || 'Jamaican flavour for every season.', price: Number(element.getAttribute('data-product-price') || 0), heat: element.getAttribute('data-product-heat') || '', tone: element.getAttribute('data-product-tone') || 'red', initials: element.getAttribute('data-product-initials') || '', image: safeImageUrl(element.getAttribute('data-product-image') || ''), url: element.getAttribute('data-product-url') || '', soon: element.getAttribute('data-product-soon') === '1', variations: variations, element: element};
  }
  function selectedVariation(product, overlay) {
    var values = {};
    if (overlay) overlay.querySelectorAll('[data-wc-attribute]').forEach(function (select) { values[select.getAttribute('data-wc-attribute')] = select.value; });
    return (product.variations || []).find(function (variation) { return Object.keys(values).every(function (key) { return String((variation.attributes || {})[key] || '') === String(values[key]); }); }) || null;
  }
  function variationFields(form, variation) {
    Object.keys((variation && variation.attributes) || {}).forEach(function (key) { form.append('variation[' + key + ']', variation.attributes[key]); });
  }
  function cartRequest(action, data) {
    if (!isWooCommerce() || !config().ajaxUrl || !config().cartNonce) return Promise.reject(new Error('WooCommerce cart is not available.'));
    var form = new FormData(); form.append('action', 'izelena_cart'); form.append('nonce', config().cartNonce); form.append('cart_action', action);
    Object.keys(data || {}).forEach(function (key) { if (data[key] !== undefined && data[key] !== null) form.append(key, data[key]); });
    return fetch(config().ajaxUrl, {method: 'POST', credentials: 'same-origin', body: form}).then(function (response) { return response.json().then(function (body) { if (!response.ok || !body.success) throw new Error(body.data && body.data.message ? body.data.message : 'The cart could not be updated.'); return body.data; }); });
  }
  function syncCart() {
    if (!isWooCommerce()) { updateCount(); return Promise.resolve(serverCart); }
    return cartRequest('get').then(function (payload) { serverCart = payload || serverCart; updateCount(); return serverCart; }).catch(function (error) { toast(error.message, 'error'); return serverCart; });
  }
  function cartMarkup() {
    if (!isWooCommerce()) {
      if (!demoCart.length) return '<div class="empty"><p>Your cart is waiting for a little island flavour.</p></div>';
      return demoCart.map(function (item, index) { var quantity = normalizeQuantity(item.quantity); return '<div class="cart-line"><div class="mini ' + escapeHtml(item.tone) + '">' + escapeHtml(item.name.charAt(0)) + '</div><div><b>' + escapeHtml(item.name) + '</b><span>Demo unit price ' + money(item.price) + '</span><span class="cart-quantity">Qty <b>&times; ' + quantity + '</b></span><strong class="cart-subtotal">Demo subtotal ' + money(Number(item.price) * quantity) + '</strong></div><div class="cart-actions"><input type="number" min="1" max="999999" value="' + quantity + '" data-demo-cart-quantity="' + index + '" aria-label="' + escapeHtml(item.name) + ' quantity"><button class="cart-remove" type="button" data-demo-remove="' + index + '">Remove</button></div></div>'; }).join('') + '<div class="cart-total"><span>Demo total</span><strong>' + money(demoCart.reduce(function (sum, item) { return sum + Number(item.price) * normalizeQuantity(item.quantity); }, 0)) + '</strong></div>';
    }
    if (!serverCart.items || !serverCart.items.length) return '<div class="empty"><p>Your cart is waiting for a little island flavour.</p></div>';
    var lines = serverCart.items.map(function (item) { var quantity = normalizeQuantity(item.quantity); return '<div class="cart-line"><div class="mini red">' + escapeHtml((item.name || 'I').charAt(0)) + '</div><div><b>' + escapeHtml(item.name) + '</b>' + (item.variation ? '<span>' + escapeHtml(item.variation) + '</span>' : '') + '<span>Unit price ' + (item.price_html || '') + '</span><span class="cart-quantity">Qty <b>&times; ' + quantity + '</b></span><strong class="cart-subtotal">Line subtotal ' + (item.subtotal_html || '') + '</strong></div><div class="cart-actions"><div class="quantity-controls" aria-label="' + escapeHtml(item.name) + ' quantity"><button type="button" data-cart-key="' + escapeHtml(item.key) + '" data-quantity-change="-1" aria-label="Decrease quantity">&minus;</button><input type="number" min="1" max="999999" value="' + quantity + '" data-cart-key="' + escapeHtml(item.key) + '" data-cart-quantity-input aria-label="' + escapeHtml(item.name) + ' quantity"><button type="button" data-cart-key="' + escapeHtml(item.key) + '" data-quantity-change="1" aria-label="Increase quantity">+</button></div><button class="cart-remove" type="button" data-remove-cart="' + escapeHtml(item.key) + '">Remove</button></div></div>'; }).join('');
    var checkout = serverCart.checkout_enabled && serverCart.checkout_url ? '<a class="btn primary full" href="' + escapeHtml(serverCart.checkout_url) + '">Checkout</a>' : '<button class="btn primary full" type="button" disabled title="Checkout will be enabled when payment is connected">Checkout coming soon</button>';
    return lines + '<div class="cart-total"><span>Current total</span><strong>' + (serverCart.total_html || '') + '</strong></div>' + checkout;
  }
  function renderCart(drawer) { updateCount(); drawer = drawer || document.querySelector('.drawer'); if (!drawer) return; var heading = drawer.querySelector('h2'); if (heading) heading.innerHTML = 'Cart <em>(' + totalCount() + ')</em>'; var content = drawer.querySelector('[data-cart-content]'); if (content) content.innerHTML = cartMarkup(); }
  function closeOverlay(overlay) { var target = overlay || document.querySelector('.overlay'); if (target) target.remove(); if (returnFocus && document.contains(returnFocus)) returnFocus.focus(); returnFocus = null; }
  function addDemo(product, quantity) { quantity = normalizeQuantity(quantity); var existing = demoCart.find(function (item) { return String(item.id) === String(product.id); }); if (existing) existing.quantity = normalizeQuantity(existing.quantity + quantity); else demoCart.push({id: product.id, name: product.name, price: product.price, tone: product.tone, quantity: quantity}); toast(product.name + ' added to your selection', 'success'); updateCount(); return true; }
  function addWoo(product, overlay, quantity, trigger) {
    var variation = selectedVariation(product, overlay); if ((product.variations || []).length && !variation) { toast('Choose an available size before adding this flavour.', 'error'); return; }
    if (variation && (!variation.purchasable || !variation.in_stock)) { toast('That size is unavailable. Please choose another variation.', 'error'); return; }
    quantity = clampQuantity(quantity, variationMaxQuantity(variation));
    var data = {product_id: product.id, variation_id: variation ? variation.id : 0, quantity: quantity};
    var request = variation ? cartRequestWithVariation('add', data, variation) : cartRequest('add', data);
    request.then(function (payload) { serverCart = payload; renderCart(); toast(product.name + ' added to your selection', 'success'); if (overlay) closeOverlay(overlay); else if (trigger && trigger.closest('.product-detail')) window.location.href = config().shopUrl || '/shop/'; else if (trigger) openCart(trigger); }).catch(function (error) { toast(error.message, 'error'); });
  }
  function cartRequestWithVariation(action, data, variation) { var form = new FormData(); form.append('action', 'izelena_cart'); form.append('nonce', config().cartNonce); form.append('cart_action', action); Object.keys(data).forEach(function (key) { form.append(key, data[key]); }); variationFields(form, variation); return fetch(config().ajaxUrl, {method: 'POST', credentials: 'same-origin', body: form}).then(function (response) { return response.json().then(function (body) { if (!response.ok || !body.success) throw new Error(body.data && body.data.message ? body.data.message : 'The cart could not be updated.'); return body.data; }); }); }
  function addProduct(product, overlay, quantity, trigger) { quantity = normalizeQuantity(quantity); if (!product || product.soon) { toast('This flavour is not available yet.', 'error'); return; } if (isWooCommerce()) addWoo(product, overlay, quantity, trigger); else addDemo(product, quantity); }
  function modalArtwork(product) { var image = product.image ? '<img class="modal-product-image" src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.name + ' product') + '">' : ''; var heat = heatLabels[product.heat] || 'Heat pending approval'; var heatClass = product.heat || 'pending'; var badge = '<span class="heat-pill heat-pill-' + escapeHtml(heatClass) + '">' + escapeHtml(heat) + '</span>'; return '<span class="modal-art-fallback" aria-hidden="true">' + escapeHtml(product.initials) + '</span>' + image + badge; }
  function renderModalPrice(price, unitPrice, quantity) {
    if (!price || !Number.isFinite(unitPrice) || unitPrice <= 0) return;
    var safeQuantity = normalizeQuantity(quantity);
    price.innerHTML = (safeQuantity === 1 ? 'Unit price ' : 'Subtotal ') + money(unitPrice * safeQuantity) + (safeQuantity > 1 ? ' <small>(' + safeQuantity.toLocaleString() + ' &times; ' + money(unitPrice) + ')</small>' : '');
  }
  function updateModal(target) {
    var product = target && target.izelenaProduct ? target.izelenaProduct : (target ? productFromElement(target) : null);
    if (!product || !target) return;
    var variation = selectedVariation(product, target);
    var price = target.querySelector('.modal-price') || target.querySelector('[data-detail-price]');
    var image = target.querySelector('.modal-art');
    var detailImage = target.querySelector('.product-detail-image');
    var stock = target.querySelector('[data-modal-stock]') || target.querySelector('[data-detail-stock]');
    var add = target.querySelector('[data-modal-add]') || target.querySelector('[data-detail-add]');
    var hasVariations = product.variations.length > 0;
    var quantityInput = target.querySelector('[data-modal-quantity]');
    var maximum = variationMaxQuantity(variation);
    var quantity = quantityInput ? clampQuantity(quantityInput.value, maximum) : 1;
    if (quantityInput) quantityInput.max = String(maximum);
    if (quantityInput) quantityInput.value = quantity;
    var available = hasVariations ? (variation && variation.in_stock && variation.purchasable) : true;
    if (variation) {
      renderModalPrice(price, Number(variation.price), quantity);
      if (variation.image && image) { image.classList.add('has-image'); image.innerHTML = modalArtwork({name: product.name, initials: product.initials, heat: product.heat, image: safeImageUrl(variation.image)}); }
      if (variation.image && detailImage) detailImage.src = safeImageUrl(variation.image);
      if (stock) stock.textContent = available ? 'Available' : 'Unavailable';
    } else if (hasVariations && price) price.textContent = 'Choose a size';
    else if (price) {
      renderModalPrice(price, Number(product.price), quantity);
    }
    if (add && hasVariations) add.disabled = !available;
    target.querySelectorAll('[data-wc-attribute] option').forEach(function (option) {
      var key = option.closest('[data-wc-attribute]').getAttribute('data-wc-attribute');
      var hasAvailable = (product.variations || []).some(function (record) { return String((record.attributes || {})[key] || '') === String(option.value) && record.in_stock && record.purchasable; });
      option.disabled = !hasAvailable;
    });
  }
  function addQuantity(overlay) { var button = overlay.querySelector('[data-modal-add]'); if (!button) return; var holder = document.createElement('div'); holder.className = 'modal-quantity'; holder.innerHTML = '<span>Quantity</span><div class="quantity-controls"><button type="button" data-modal-quantity-change="-1">-</button><input type="number" min="1" max="999999" value="1" data-modal-quantity aria-label="Quantity"><button type="button" data-modal-quantity-change="1">+</button></div>'; button.parentNode.insertBefore(holder, button); }
  function prepareOverlay(overlay, trigger) { closeOverlay(); returnFocus = trigger || document.activeElement; document.body.appendChild(overlay); var close = overlay.querySelector('.close'); if (close) close.focus(); addQuantity(overlay); updateModal(overlay); }
  function openCart(trigger) { var overlay = document.createElement('div'); overlay.className = 'overlay'; overlay.setAttribute('role', 'dialog'); overlay.setAttribute('aria-modal', 'true'); overlay.setAttribute('aria-label', 'Shopping cart'); overlay.innerHTML = '<aside class="drawer"><button class="close" type="button" aria-label="Close shopping cart">&times;</button><p class="eyebrow">Your selection</p><h2>Cart <em>(0)</em></h2><div data-cart-content><div class="empty"><p>Loading cart...</p></div></div></aside>'; prepareOverlay(overlay, trigger); syncCart().then(function () { renderCart(overlay.querySelector('.drawer')); }); }
  function openProductModal(trigger) { var card = trigger.querySelector('.product-card') || trigger; if (!card) return; var product = productFromElement(card); var heat = heatLabels[product.heat] || 'Heat pending approval'; var options = ''; var attributes = {}; (product.variations || []).forEach(function (variation) { Object.keys(variation.attributes || {}).forEach(function (key) { attributes[key] = attributes[key] || {}; attributes[key][variation.attributes[key]] = (variation.attribute_labels || {})[key] || variation.attributes[key]; }); }); Object.keys(attributes).forEach(function (key) { options += '<label class="modal-select">' + escapeHtml(key.replace(/^attribute_pa_/, '').replace(/[-_]/g, ' ')) + '<select data-wc-attribute="' + escapeHtml(key) + '">'; Object.keys(attributes[key]).forEach(function (value) { options += '<option value="' + escapeHtml(value) + '">' + escapeHtml(attributes[key][value]) + '</option>'; }); options += '</select></label>'; }); var overlay = document.createElement('div'); overlay.className = 'overlay'; overlay.setAttribute('role', 'dialog'); overlay.setAttribute('aria-modal', 'true'); overlay.setAttribute('aria-label', product.name + ' details'); overlay.izelenaProduct = product; overlay.innerHTML = '<div class="modal"><button class="close" type="button" aria-label="Close product details">&times;</button><div class="modal-art ' + escapeHtml(product.tone) + (product.image ? ' has-image' : '') + '">' + modalArtwork(product) + '</div><div class="modal-content"><p class="eyebrow">' + escapeHtml(product.tag) + ' - ' + escapeHtml(heat) + '</p><h2>' + escapeHtml(product.name) + '</h2><p>' + escapeHtml(product.description) + '</p>' + options + '<strong class="modal-price">Choose a size</strong><p data-modal-stock></p><button class="btn primary full" type="button" data-modal-add>Add to cart <span>+</span></button></div></div>'; prepareOverlay(overlay, trigger); }
  function initCarousel() { document.querySelectorAll('[data-hero-carousel]').forEach(function (carousel) { if (carousel.getAttribute('data-carousel-ready') === '1') return; carousel.setAttribute('data-carousel-ready', '1'); var track = carousel.querySelector('.hero-carousel-track'); var slides = Array.prototype.slice.call(carousel.querySelectorAll('.hero-slide')); var indicators = Array.prototype.slice.call(carousel.querySelectorAll('[data-carousel-indicator]')); var current = carousel.querySelector('[data-carousel-current]'); var previous = carousel.querySelector('[data-carousel-prev]'); var next = carousel.querySelector('[data-carousel-next]'); var toggle = carousel.querySelector('[data-carousel-toggle]'); var index = 0; var timer = null; var paused = false; var hovering = false; var focused = false; var controlOverride = false; var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null; if (!track || slides.length < 1) return; carousel.setAttribute('tabindex', '0'); carousel.setAttribute('data-carousel-state', 'playing'); function reducedMotion() { return !!(motionQuery && motionQuery.matches); } function stopAutoplay() { if (timer) window.clearInterval(timer); timer = null; } function startAutoplay() { stopAutoplay(); if (document.hidden || paused || reducedMotion() || slides.length < 2) return; if ((hovering || focused) && !controlOverride) return; timer = window.setInterval(function () { go(index + 1, false); }, 6000); } function go(nextIndex, restart) { index = (nextIndex + slides.length) % slides.length; track.style.transform = 'translate3d(-' + index * 100 + '%,0,0)'; slides.forEach(function (slide, i) { var active = i === index; slide.classList.toggle('is-active', active); slide.setAttribute('aria-hidden', String(!active)); }); indicators.forEach(function (indicator, i) { var active = i === index; indicator.setAttribute('aria-selected', String(active)); indicator.tabIndex = active ? 0 : -1; }); if (current) current.textContent = String(index + 1).padStart(2, '0'); if (restart) startAutoplay(); } if (previous) previous.addEventListener('click', function () { go(index - 1, true); }); if (next) next.addEventListener('click', function () { go(index + 1, true); }); indicators.forEach(function (indicator) { indicator.addEventListener('click', function () { go(Number(indicator.getAttribute('data-carousel-indicator')) || 0, true); }); }); if (toggle) toggle.addEventListener('click', function () { paused = !paused; controlOverride = !paused; toggle.textContent = paused ? 'Play' : 'Pause'; toggle.setAttribute('aria-pressed', String(paused)); carousel.setAttribute('data-carousel-state', paused ? 'paused' : 'playing'); if (paused) stopAutoplay(); else startAutoplay(); }); carousel.addEventListener('keydown', function (event) { if (event.key === 'ArrowLeft') { event.preventDefault(); go(index - 1, true); } else if (event.key === 'ArrowRight') { event.preventDefault(); go(index + 1, true); } }); carousel.addEventListener('mouseenter', function () { hovering = true; if (!controlOverride) stopAutoplay(); }); carousel.addEventListener('mouseleave', function () { hovering = false; controlOverride = false; startAutoplay(); }); carousel.addEventListener('focusin', function () { focused = true; controlOverride = false; stopAutoplay(); }); carousel.addEventListener('focusout', function (event) { if (!carousel.contains(event.relatedTarget)) { focused = false; controlOverride = false; startAutoplay(); } }); document.addEventListener('visibilitychange', function () { if (document.hidden) stopAutoplay(); else startAutoplay(); }); if (motionQuery) { var motionChanged = function () { if (reducedMotion()) stopAutoplay(); else startAutoplay(); }; if (motionQuery.addEventListener) motionQuery.addEventListener('change', motionChanged); else if (motionQuery.addListener) motionQuery.addListener(motionChanged); } go(0, false); startAutoplay(); }); }

  document.addEventListener('submit', function (event) { var form = event.target.closest('[data-contact-form]'); if (!form || !config().ajaxUrl) return; event.preventDefault(); var payload = new FormData(form); payload.set('action', 'izelena_contact_submit'); if (config().contactNonce) payload.set('izelena_contact_nonce', config().contactNonce); fetch(config().ajaxUrl, {method: 'POST', credentials: 'same-origin', body: payload}).then(function (response) { return response.json().then(function (body) { if (!response.ok || !body.success) throw new Error(body.data && body.data.message ? body.data.message : 'We could not submit your message.'); return body; }); }).then(function (body) { toast(body.data.message || 'Message received.', 'success'); form.reset(); }).catch(function (error) { toast(error.message, 'error'); }); });
  function updateWooCartQuantity(cartKey, quantity) { return cartRequest('update', {cart_key: cartKey, quantity: normalizeQuantity(quantity)}).then(function (payload) { serverCart = payload; renderCart(); }).catch(function (error) { toast(error.message, 'error'); return syncCart().then(renderCart); }); }
  document.addEventListener('input', function (event) { var modalInput = event.target.closest('[data-modal-quantity]'); if (modalInput) updateModal(modalInput.closest('.overlay') || modalInput.closest('[data-wc-product]')); });
  document.addEventListener('blur', function (event) { var modalInput = event.target.closest('[data-modal-quantity]'); if (modalInput) updateModal(modalInput.closest('.overlay') || modalInput.closest('[data-wc-product]')); }, true);
  document.addEventListener('change', function (event) { var attribute = event.target.closest('[data-wc-attribute]'); if (attribute) updateModal(attribute.closest('.overlay') || attribute.closest('[data-wc-product]')); var input = event.target.closest('[data-cart-quantity-input]'); if (input && isWooCommerce()) { input.value = normalizeQuantity(input.value); updateWooCartQuantity(input.getAttribute('data-cart-key'), input.value); } var demoInput = event.target.closest('[data-demo-cart-quantity]'); if (demoInput && !isWooCommerce()) { var demoItem = demoCart[Number(demoInput.getAttribute('data-demo-cart-quantity'))]; if (demoItem) demoItem.quantity = normalizeQuantity(demoInput.value); renderCart(); } });
  document.addEventListener('click', function (event) {
    var cartLink = event.target.closest('[data-cart-trigger], .cart'); if (cartLink) { event.preventDefault(); openCart(cartLink); return; }
    var close = event.target.closest('.close'); if (close) { closeOverlay(close.closest('.overlay')); return; }
    var remove = event.target.closest('[data-remove-cart]'); if (remove && isWooCommerce()) { cartRequest('remove', {cart_key: remove.getAttribute('data-remove-cart')}).then(function (payload) { serverCart = payload; renderCart(); }).catch(function (error) { toast(error.message, 'error'); }); return; }
    var demoRemove = event.target.closest('[data-demo-remove]'); if (demoRemove && !isWooCommerce()) { demoCart.splice(Number(demoRemove.getAttribute('data-demo-remove')), 1); renderCart(); return; }
    var qty = event.target.closest('[data-cart-key][data-quantity-change]'); if (qty && isWooCommerce()) { var line = serverCart.items.find(function (item) { return String(item.key) === String(qty.getAttribute('data-cart-key')); }); if (line) updateWooCartQuantity(line.key, normalizeQuantity(Number(line.quantity) + Number(qty.getAttribute('data-quantity-change')))); return; }
    var modalQty = event.target.closest('[data-modal-quantity-change]'); if (modalQty) { var input = modalQty.closest('.quantity-controls').querySelector('[data-modal-quantity]'); if (input) { input.value = normalizeQuantity(Number(input.value || 1) + Number(modalQty.getAttribute('data-modal-quantity-change') || 0)); updateModal(modalQty.closest('.overlay') || modalQty.closest('[data-wc-product]')); } return; }
    var modalAdd = event.target.closest('[data-modal-add]'); if (modalAdd) { var modal = modalAdd.closest('.overlay'); var product = modal && modal.izelenaProduct; addProduct(product, modal, modal && modal.querySelector('[data-modal-quantity]') ? modal.querySelector('[data-modal-quantity]').value : 1, modalAdd); return; }
    var simple = event.target.closest('.add-btn[data-wc-add], .add-btn[data-product_id], .add-btn[data-product-id]'); if (simple && isWooCommerce()) { event.preventDefault(); var simpleCard = simple.closest('.product-card'); addProduct({id: simple.getAttribute('data-product-id') || simple.getAttribute('data-product_id'), name: simpleCard ? simpleCard.getAttribute('data-product-name') : 'Izelena flavour', variations: [], price: 0}, null, 1, simple); return; }
    var demo = event.target.closest('.add-btn[data-demo-add]'); if (demo && !isWooCommerce()) { event.preventDefault(); addProduct(productFromElement(demo.closest('.product-card')), null, 1, demo); return; }
    var detail = event.target.closest('[data-detail-add]'); if (detail) { var detailCard = detail.closest('[data-wc-product]') || detail.closest('.product-detail'); var detailProduct = productFromElement(detailCard); addProduct(detailProduct, null, detailCard.querySelector('[data-modal-quantity]') ? detailCard.querySelector('[data-modal-quantity]').value : 1, detail); return; }
    var trigger = event.target.closest('.shop-product-trigger'); if (trigger && !event.target.closest('a')) { event.preventDefault(); openProductModal(trigger); return; }
    if (event.target.classList.contains('overlay')) closeOverlay(event.target);
  });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeOverlay(); var trigger = event.target.closest('.shop-product-trigger'); if (trigger && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); openProductModal(trigger); } });
  updateCount();
  if (isWooCommerce()) syncCart();
  document.querySelectorAll('[data-wc-product]').forEach(function (target) { updateModal(target); });
  initCarousel();
}());
