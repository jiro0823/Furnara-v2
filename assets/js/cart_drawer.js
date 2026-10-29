(() => {
    const config = document.getElementById('furnara-cart-config');
    const layer = document.getElementById('furnara-cart-layer');
    if (!config || !layer) return;

    const endpoint = config.dataset.endpoint;
    const list = document.getElementById('furnara-cart-items');
    const total = document.getElementById('furnara-cart-total');
    const badge = document.getElementById('furnara-cart-count');
    const openDrawer = async () => {
        layer.hidden = false;
        document.body.classList.add('furnara-cart-open');
        requestAnimationFrame(() => layer.classList.add('is-open'));
        await loadCart();
        layer.querySelector('[data-cart-close]')?.focus();
    };
    const closeDrawer = () => {
        layer.classList.remove('is-open');
        document.body.classList.remove('furnara-cart-open');
        window.setTimeout(() => { if (!layer.classList.contains('is-open')) layer.hidden = true; }, 280);
    };
    const money = (amount) => '₱' + Number(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const post = async (payload) => {
        const requestPayload = new URLSearchParams({...payload, csrf_token: config.dataset.csrfToken || ''});
        const response = await fetch(endpoint, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:requestPayload, credentials:'same-origin' });
        const data = await response.json();
        if (!response.ok || !data.success) {
            const error = new Error(data.message || 'Cart update failed.');
            error.recommendations = data.recommendations || [];
            throw error;
        }
        return data;
    };
    async function loadCart() {
        list.innerHTML = '<p class="furnara-cart-loading">Loading your cart…</p>';
        try {
            const response = await fetch(endpoint, { credentials:'same-origin', headers:{'Accept':'application/json'} });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error('Could not load the cart.');
            render(data);
        } catch (error) {
            list.innerHTML = '<p class="furnara-cart-empty">Your cart could not load. Please try again.</p>';
        }
    }
    function render(data) {
        if (badge) badge.textContent = String(data.count || 0);
        document.querySelectorAll('#cart-count').forEach((item) => { item.textContent = String(data.count || 0); });
        total.textContent = money(data.total);
        list.replaceChildren();
        const checkout = document.getElementById('furnara-cart-checkout');
        checkout.classList.toggle('is-disabled', !data.items.length);
        if (!data.items.length) {
            const empty = document.createElement('p');
            empty.className = 'furnara-cart-empty';
            empty.textContent = config.dataset.loggedIn === '1'
                ? 'Your cart is waiting for something you love.'
                : 'Log in to view your personal cart.';
            list.append(empty);
            return;
        }
        data.items.forEach((item) => {
            const row = document.createElement('article');
            row.className = 'furnara-cart-item';
            const image = document.createElement('img');
            image.src = item.image_url;
            image.alt = item.name;
            const info = document.createElement('div');
            info.className = 'furnara-cart-item-info';
            const name = document.createElement('a');
            name.className = 'furnara-cart-item-name';
            name.href = `${window.FURNARA_APP_BASE || ''}/pages/public/product.php?id=${encodeURIComponent(item.furniture_id)}`;
            name.textContent = item.name;
            const price = document.createElement('span');
            price.className = 'furnara-cart-item-price';
            price.textContent = `${money(item.price)} each`;
            const quantity = document.createElement('div');
            quantity.className = 'furnara-cart-quantity';
            const decrease = document.createElement('button');
            decrease.type = 'button'; decrease.textContent = '−'; decrease.setAttribute('aria-label', `Decrease ${item.name}`);
            const value = document.createElement('span'); value.textContent = String(item.quantity);
            const increase = document.createElement('button');
            increase.type = 'button'; increase.textContent = '+'; increase.setAttribute('aria-label', `Increase ${item.name}`);
            // Cart quantity is already reserved; only current available stock limits another increase.
            increase.disabled = item.stock <= 0;
            decrease.addEventListener('click', () => changeQuantity(item, item.quantity - 1));
            increase.addEventListener('click', () => changeQuantity(item, item.quantity + 1));
            quantity.append(decrease, value, increase);
            info.append(name, price, quantity);
            const remove = document.createElement('button');
            remove.type = 'button'; remove.className = 'furnara-cart-remove'; remove.innerHTML = '<i class="bi bi-trash3" aria-hidden="true"></i>'; remove.setAttribute('aria-label', `Remove ${item.name}`);
            remove.addEventListener('click', async () => { try { await post({action:'remove', furniture_id:item.furniture_id}); await loadCart(); } catch (error) { window.alert(error.message); } });
            row.append(image, info, remove);
            list.append(row);
        });
    }
    async function changeQuantity(item, quantity) {
        try { await post({action:'set', furniture_id:item.furniture_id, quantity}); await loadCart(); }
        catch (error) { window.alert(error.message); }
    }

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('#cart-icon, [data-cart-open]');
        if (opener) { event.preventDefault(); event.stopImmediatePropagation(); openDrawer(); return; }
        if (event.target.closest('[data-cart-close]')) { closeDrawer(); return; }

        const addButton = event.target.closest('.add-to-cart, .add-to-cart-modal');
        if (addButton && addButton.dataset.id) {
            event.preventDefault(); event.stopImmediatePropagation();
            const add = async () => {
                if (config.dataset.loggedIn !== '1') {
                    window.alert('Please log in first to add products to your cart.');
                    window.location.href = config.dataset.loginUrl;
                    return;
                }
                try {
                    const selectedQuantity = addButton.id === 'product-add-to-cart' ? Number(document.getElementById('product-quantity')?.value || 1) : 1;
                    await post({action:'add', furniture_id:addButton.dataset.id, quantity:selectedQuantity});
                    await loadCart();
                    if (addButton.classList.contains('add-to-cart-modal') && window.bootstrap) {
                        bootstrap.Modal.getInstance(document.getElementById('productModal'))?.hide();
                    }
                    layer.hidden = false; document.body.classList.add('furnara-cart-open'); requestAnimationFrame(() => layer.classList.add('is-open'));
                    const feedback = document.getElementById('product-cart-feedback');
                    if (feedback) feedback.textContent = 'Added to your cart.';
                } catch (error) {
                    const suggestions = (error.recommendations || []).map((item) => item.name);
                    window.alert(error.message + (suggestions.length ? '\n\nSimilar in-stock products:\n• ' + suggestions.join('\n• ') : ''));
                }
            };
            add();
        }
    }, true);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !layer.hidden) closeDrawer(); });
    window.addEventListener('pageshow', loadCart);
    window.addEventListener('furnara:cart-updated', loadCart);
    loadCart();
})();
