(() => {
    const widget = document.querySelector('[data-support-widget]');
    if (!widget) return;
    const launcher = widget.querySelector('[data-support-open]');
    const panel = widget.querySelector('[data-support-panel]');
    const close = widget.querySelector('[data-support-close]');
    const role = widget.dataset.chatRole || 'customer';
    const isAdmin = role === 'admin';
    const inbox = widget.querySelector('[data-support-inbox]');
    const threadList = widget.querySelector('[data-support-thread-list]');
    const search = widget.querySelector('[data-support-search]');
    const threadView = widget.querySelector('[data-support-thread-view]');
    const threadName = widget.querySelector('[data-support-thread-name]');
    const threadId = widget.querySelector('[data-support-thread-id]');
    const backButton = widget.querySelector('[data-support-back]');
    const messages = widget.querySelector('[data-support-messages]');
    const form = widget.querySelector('[data-support-form]');
    const status = widget.querySelector('[data-support-status]');
    if (!launcher || !panel) return;

    let isOpen = false;
    let lastSignature = '';
    let messageRequestActive = false;
    let inboxRequestActive = false;
    let threads = [];
    let selectedUserId = '';
    let selectedName = '';

    const formatDate = value => {
        if (!value) return '';
        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? '' : date.toLocaleString([], {month:'short',day:'numeric',hour:'numeric',minute:'2-digit'});
    };

    const setOpen = open => {
        isOpen = open;
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        launcher.classList.toggle('is-open', open);
        if (!open) return;
        if (isAdmin) loadInbox();
        else {
            loadMessages();
            form?.elements.message.focus();
        }
    };

    function renderThreads(items) {
        if (!threadList) return;
        const filter = (search?.value || '').trim().toLocaleLowerCase();
        const visibleThreads = items.filter(thread => `${thread.name} ${thread.last_message}`.toLocaleLowerCase().includes(filter));
        threadList.replaceChildren();
        if (!visibleThreads.length) {
            const empty = document.createElement('p');
            empty.className = 'support-widget-empty';
            empty.textContent = filter ? 'No conversations match your search.' : 'No customer accounts found.';
            threadList.append(empty);
            return;
        }
        for (const thread of visibleThreads) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'support-widget-thread';
            button.dataset.userId = String(thread.user_id);
            button.setAttribute('aria-label', `Open conversation with ${thread.name}`);
            if (String(thread.user_id) === selectedUserId) button.classList.add('is-selected');
            const avatar = document.createElement('span');
            avatar.className = 'support-widget-thread-avatar';
            avatar.textContent = String(thread.name || 'C').trim().charAt(0).toUpperCase();
            const details = document.createElement('span');
            details.className = 'support-widget-thread-details';
            const title = document.createElement('strong');
            title.textContent = thread.name || `Customer #${thread.user_id}`;
            const preview = document.createElement('small');
            preview.textContent = thread.last_message || 'No messages yet · Start a conversation';
            const meta = document.createElement('span');
            meta.className = 'support-widget-thread-meta';
            const when = document.createElement('time');
            when.textContent = formatDate(thread.last_time) || `Customer #${thread.user_id}`;
            meta.append(when);
            if (Number(thread.unread_count) > 0) {
                const unread = document.createElement('b');
                unread.className = 'support-widget-unread';
                unread.textContent = Number(thread.unread_count) > 99 ? '99+' : String(thread.unread_count);
                meta.append(unread);
            }
            details.append(title, preview, meta);
            button.append(avatar, details);
            button.addEventListener('click', () => openThread(thread));
            threadList.append(button);
        }
    }

    async function loadInbox() {
        if (!isAdmin || !threadList || !isOpen || inboxRequestActive) return;
        inboxRequestActive = true;
        try {
            const url = new URL(widget.dataset.chatEndpoint, window.location.href);
            url.searchParams.set('inbox', '1');
            const response = await fetch(url, {credentials:'same-origin',headers:{'Accept':'application/json'}});
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not load conversations.');
            threads = result.threads || [];
            renderThreads(threads);
        } catch (error) {
            threadList.textContent = error.message;
        } finally {
            inboxRequestActive = false;
        }
    }

    function openThread(thread) {
        selectedUserId = String(thread.user_id);
        selectedName = thread.name || `Customer #${thread.user_id}`;
        widget.dataset.chatUserId = selectedUserId;
        if (threadName) threadName.textContent = selectedName;
        if (threadId) threadId.textContent = `Customer ID ${selectedUserId}`;
        if (inbox) inbox.hidden = true;
        if (threadView) threadView.hidden = false;
        lastSignature = '';
        messages?.replaceChildren();
        if (messages) {
            const loading = document.createElement('p');
            loading.className = 'support-widget-empty';
            loading.textContent = 'Loading conversation…';
            messages.append(loading);
        }
        renderThreads(threads);
        loadMessages();
        form?.elements.message.focus();
    }

    function renderMessages(items, userId) {
        if (!messages || (isAdmin && userId !== selectedUserId)) return;
        const signature = items.map(item => item.id).join(',');
        if (signature === lastSignature) return;
        const atBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 70;
        lastSignature = signature;
        messages.replaceChildren();
        if (!items.length) {
            const empty = document.createElement('p');
            empty.className = 'support-widget-empty';
            empty.textContent = isAdmin ? 'No messages yet. Send a reply to start this conversation.' : 'No messages yet. Send us a message and our team will reply here.';
            messages.append(empty);
            return;
        }
        for (const item of items) {
            const bubble = document.createElement('article');
            bubble.className = `support-widget-message ${item.sender_role === role ? 'is-mine' : 'is-theirs'}`;
            const sender = document.createElement('strong');
            sender.textContent = item.sender_role === 'admin' ? 'Furnara support' : (isAdmin ? selectedName : (widget.dataset.chatCustomerName || 'You'));
            const body = document.createElement('p');
            body.textContent = item.message;
            const time = document.createElement('time');
            time.textContent = formatDate(item.created_at) || item.created_at;
            bubble.append(sender, body, time);
            messages.append(bubble);
        }
        if (atBottom) messages.scrollTop = messages.scrollHeight;
    }

    async function loadMessages() {
        if (!messages || !isOpen || messageRequestActive) return;
        const userId = isAdmin ? selectedUserId : '';
        if (isAdmin && (!userId || threadView?.hidden)) return;
        messageRequestActive = true;
        const url = new URL(widget.dataset.chatEndpoint, window.location.href);
        if (userId) url.searchParams.set('user_id', userId);
        try {
            const response = await fetch(url, {credentials:'same-origin',headers:{'Accept':'application/json'}});
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not load messages.');
            renderMessages(result.messages || [], userId);
            if (status) status.textContent = 'Private conversation · messages are saved automatically.';
        } catch (error) {
            if (!lastSignature && (!isAdmin || userId === selectedUserId)) messages.textContent = error.message;
            if (status) status.textContent = error.message;
        } finally {
            messageRequestActive = false;
        }
    }

    launcher.addEventListener('click', () => setOpen(!isOpen));
    close?.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && isOpen) setOpen(false); });
    search?.addEventListener('input', () => renderThreads(threads));
    backButton?.addEventListener('click', () => {
        if (threadView) threadView.hidden = true;
        if (inbox) inbox.hidden = false;
        selectedUserId = '';
        widget.dataset.chatUserId = '';
        lastSignature = '';
        loadInbox();
    });
    form?.addEventListener('submit', async event => {
        event.preventDefault();
        const input = form.elements.message;
        const message = input.value.trim();
        if (!message) return;
        const userId = isAdmin ? selectedUserId : '';
        if (isAdmin && !userId) return;
        const data = new URLSearchParams({message,csrf_token:widget.dataset.chatCsrf || ''});
        if (userId) data.set('user_id', userId);
        const submit = form.querySelector('button[type="submit"]');
        submit.disabled = true;
        try {
            const response = await fetch(widget.dataset.chatEndpoint, {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:data});
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Message could not be sent.');
            input.value = '';
            lastSignature = '';
            await loadMessages();
            if (isAdmin) loadInbox();
        } catch (error) {
            if (status) status.textContent = error.message;
        } finally {
            submit.disabled = false;
            input.focus();
        }
    });

    window.setInterval(() => {
        if (!isOpen) return;
        if (isAdmin) {
            loadInbox();
            loadMessages();
        } else loadMessages();
    }, 2500);
})();
