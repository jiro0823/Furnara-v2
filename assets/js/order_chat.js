(() => {
    const chat = document.querySelector('[data-chat-endpoint]');
    const list = document.getElementById('order-chat-messages');
    const form = document.getElementById('order-chat-form');
    if (!chat || !list || !form) return;
    const endpoint = chat.dataset.chatEndpoint;
    const role = chat.dataset.chatRole || 'customer';
    const userId = chat.dataset.chatUserId || '';
    const csrfToken = chat.dataset.csrf || '';
    const status = document.getElementById('order-chat-status');
    let lastSignature = '';

    function render(messages) {
        const signature = messages.map(item => item.id).join(',');
        if (signature === lastSignature) return;
        const wasAtBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
        lastSignature = signature;
        list.replaceChildren();
        if (!messages.length) {
            const empty = document.createElement('p');
            empty.className = 'order-chat-empty';
            empty.textContent = 'No messages yet. Start a conversation with the Furnara team.';
            list.append(empty);
            return;
        }
        messages.forEach(item => {
            const bubble = document.createElement('article');
            bubble.className = 'order-chat-bubble ' + (item.sender_role === role ? 'is-mine' : 'is-theirs');
            const label = document.createElement('strong');
            label.textContent = item.sender_role === 'admin' ? 'Furnara support' : (chat.dataset.customerName || 'You');
            const body = document.createElement('p');
            body.textContent = item.message;
            const time = document.createElement('time');
            const date = new Date(String(item.created_at).replace(' ', 'T'));
            time.textContent = Number.isNaN(date.getTime()) ? item.created_at : date.toLocaleString([], {month:'short',day:'numeric',hour:'numeric',minute:'2-digit'});
            bubble.append(label, body, time);
            list.append(bubble);
        });
        if (wasAtBottom || !lastSignature) list.scrollTop = list.scrollHeight;
    }

    async function load() {
        const url = new URL(endpoint, window.location.href);
        if (userId) url.searchParams.set('user_id', userId);
        try {
            const response = await fetch(url, {credentials:'same-origin',headers:{'Accept':'application/json'}});
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load conversation.');
            render(data.messages || []);
            if (status) status.textContent = 'Messages are saved and refresh automatically.';
        } catch (error) {
            if (!lastSignature) list.textContent = error.message;
            if (status) status.textContent = error.message;
        }
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const input = form.elements.message;
        const message = input.value.trim();
        if (!message) return;
        const send = form.querySelector('button[type="submit"]');
        send.disabled = true;
        const data = new URLSearchParams({message,csrf_token:csrfToken});
        if (userId) data.set('user_id', userId);
        try {
            const response = await fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:data});
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Message could not be sent.');
            input.value = '';
            await load();
        } catch (error) {
            if (status) status.textContent = error.message;
        } finally {
            send.disabled = false;
            input.focus();
        }
    });
    load();
    window.setInterval(load, 2500);
})();
