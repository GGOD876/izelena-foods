document.addEventListener('DOMContentLoaded', function () {
  const menu = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.site-nav');
  if (menu && nav) {
    menu.addEventListener('click', function () {
      const open = nav.classList.toggle('is-open');
      menu.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.addEventListener('click', function (event) {
    const cart = event.target.closest('.cart-link a');
    if (!cart) return;
    event.preventDefault();
    event.stopPropagation();
    if (document.querySelector('.overlay[aria-label="Shopping cart"]')) return;
    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Shopping cart');
    overlay.innerHTML = '<aside class="drawer"><button class="close" aria-label="Close shopping cart">×</button><p class="eyebrow">Your selection</p><h2>Cart <em>(0)</em></h2><div class="empty"><p>Your cart is waiting for a little island flavour.</p></div></aside>';
    document.body.appendChild(overlay);
    const close = function () { overlay.remove(); };
    overlay.querySelector('.close').addEventListener('click', close);
    overlay.addEventListener('click', function (event) { if (event.target === overlay) close(); });
  }, true);
});
