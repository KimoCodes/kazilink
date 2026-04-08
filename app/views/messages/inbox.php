<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$messageBodyValue = !empty($errors) ? old_value('message_body') : '';
$conversations = is_array($conversations ?? null) ? $conversations : [];
$selectedBookingId = (int) ($selectedBookingId ?? 0);
$messages = is_array($messages ?? null) ? $messages : [];
$booking = is_array($booking ?? null) ? $booking : null;
$lastMessage = $messages !== [] ? $messages[array_key_last($messages)] : null;
$activeChatName = '';

if ($booking !== null) {
    $activeChatName = Auth::role() === 'client'
        ? (string) ($booking['tasker_name'] ?? 'Conversation')
        : (string) ($booking['client_name'] ?? 'Conversation');
}
?>
<div class="container">
    <section class="inbox-app-shell">
        <header class="inbox-app-topbar">
            <div>
                <span class="eyebrow">Messages</span>
                <h1>Inbox</h1>
                <p class="page-intro">Choose a conversation and reply from one simple workspace.</p>
            </div>
            <a class="inbox-toolbar-link" href="<?= e(url_for('bookings/index')) ?>">View bookings</a>
        </header>

        <div class="inbox-app-layout">
            <aside class="inbox-app-sidebar">
                <div class="inbox-sidebar-toolbar">
                    <strong>Conversations</strong>
                    <span><?= count($conversations) ?></span>
                </div>

                <div class="inbox-search-wrap">
                    <label class="sr-only" for="inbox-search">Search conversations</label>
                    <input id="inbox-search" class="inbox-search-input" type="search" placeholder="Search conversations" data-inbox-search>
                </div>

                <?php if ($conversations === []): ?>
                    <div class="inbox-empty-panel">
                        <strong>No conversations yet</strong>
                        <p>When a booking starts exchanging messages, it will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="inbox-chat-list" data-inbox-list>
                        <?php foreach ($conversations as $conversation): ?>
                            <?php
                            $isSelected = (int) $conversation['booking_id'] === $selectedBookingId;
                            $conversationName = (string) ($conversation['counterpart_name'] ?: 'Conversation');
                            $conversationPreview = (string) ($conversation['body'] ?? '');
                            $conversationUnread = (int) ($conversation['unread_count'] ?? 0);
                            $taskTitle = (string) ($conversation['task_title'] ?? '');
                            ?>
                            <a
                                class="inbox-chat-row<?= $isSelected ? ' is-active' : '' ?>"
                                href="<?= e(url_for('messages/index', ['id' => (int) $conversation['booking_id']])) ?>"
                                data-inbox-item
                                data-filter-name="<?= e(mb_strtolower($conversationName . ' ' . $taskTitle . ' ' . $conversationPreview)) ?>"
                            >
                                <div class="inbox-chat-copy">
                                    <div class="inbox-chat-topline">
                                        <strong><?= e($conversationName) ?></strong>
                                        <span><?= e(date('M j, g:i A', strtotime((string) $conversation['created_at']))) ?></span>
                                    </div>
                                    <div class="inbox-chat-subline"><?= e($taskTitle) ?></div>
                                    <div class="inbox-chat-bottomline">
                                        <p><?= e(substr($conversationPreview, 0, 78)) ?><?= strlen($conversationPreview) > 78 ? '...' : '' ?></p>
                                        <?php if ($conversationUnread > 0): ?>
                                            <span class="inbox-unread-pill"><?= min($conversationUnread, 99) ?><?= $conversationUnread > 99 ? '+' : '' ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>

            <section class="inbox-chat-stage">
                <?php if ($booking === null): ?>
                    <div class="inbox-chat-empty">
                        <div class="inbox-chat-empty-mark">💬</div>
                        <h2>Select a chat</h2>
                        <p>Choose a conversation on the left to open the full thread.</p>
                    </div>
                <?php else: ?>
                    <header class="inbox-chat-header">
                        <div class="inbox-chat-header-main">
                            <div>
                                <strong><?= e($activeChatName) ?></strong>
                                <span><?= e((string) $booking['title']) ?></span>
                            </div>
                        </div>
                        <a class="inbox-toolbar-link" href="<?= e(url_for('bookings/show', ['id' => (int) $booking['id']])) ?>">Booking</a>
                    </header>

                    <div class="inbox-chat-meta">
                        <span><?= Auth::role() === 'client' ? 'Tasker' : 'Client' ?>: <strong><?= e($activeChatName) ?></strong></span>
                    </div>

                    <div class="inbox-message-canvas" id="message-list">
                        <div id="polling-indicator" class="chat-polling-indicator" hidden>Checking for new messages...</div>

                        <?php if ($messages === []): ?>
                            <div class="inbox-chat-empty-inline">
                                <strong>No messages yet</strong>
                                <p>Start the discussion below to coordinate timing, access, or task details.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <?php $isMine = (int) $message['sender_id'] === (int) Auth::id(); ?>
                                <article class="inbox-bubble-row<?= $isMine ? ' is-mine' : '' ?>">
                                    <div class="inbox-bubble<?= $isMine ? ' is-mine' : '' ?>">
                                        <p class="message-body"><?= nl2br(e((string) $message['body'])) ?></p>
                                        <div class="inbox-bubble-meta">
                                            <span><?= e((string) $message['sender_name']) ?></span>
                                            <span><?= e(date('g:i A', strtotime((string) $message['created_at']))) ?></span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="inbox-compose-bar">
                        <form method="post" action="<?= e(url_for('messages/thread')) ?>" class="inbox-compose-form" novalidate>
                            <?= Csrf::input() ?>
                            <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">

                            <div class="inbox-compose-input-wrap">
                                <label class="sr-only" for="body">Type a message</label>
                                <textarea
                                    id="body"
                                    name="body"
                                    class="inbox-compose-input<?= field_error($fieldErrors, 'body') ? ' is-invalid' : '' ?>"
                                    placeholder="Type a message"
                                    rows="1"
                                    maxlength="4000"
                                    required
                                ><?= e($messageBodyValue) ?></textarea>
                                <button type="submit" class="inbox-send-button">Send</button>
                            </div>
                            <?php if (field_error($fieldErrors, 'body')): ?>
                                <p class="form-error"><?= e((string) field_error($fieldErrors, 'body')) ?></p>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.querySelector('[data-inbox-search]');
        const inboxItems = document.querySelectorAll('[data-inbox-item]');

        function applyInboxFilters() {
            const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

            inboxItems.forEach((item) => {
                const haystack = item.getAttribute('data-filter-name') || '';
                item.hidden = !(query === '' || haystack.includes(query));
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyInboxFilters);
        }

        applyInboxFilters();
    });
</script>

<?php if ($booking !== null): ?>
<script>
    let lastMessageId = <?= $lastMessage !== null ? (int) $lastMessage['id'] : 0 ?>;
    let pollingInterval;

    function pollForMessages() {
        const indicator = document.getElementById('polling-indicator');
        indicator.hidden = false;

        fetch('<?= e(url_for('messages/poll')) ?>?booking_id=<?= e((string) $booking['id']) ?>&after_id=' + encodeURIComponent(lastMessageId))
            .then((response) => response.json())
            .then((data) => {
                indicator.hidden = true;

                if (data.has_new && data.messages.length > 0) {
                    const messageList = document.getElementById('message-list');
                    const emptyState = messageList.querySelector('.inbox-chat-empty-inline');

                    if (emptyState) {
                        emptyState.remove();
                    }

                    data.messages.forEach((message) => {
                        const isMine = Number(message.sender_id) === <?= (int) Auth::id() ?>;
                        const messageElement = createMessageElement(message, isMine);
                        messageList.appendChild(messageElement);

                        if (Number(message.id) > lastMessageId) {
                            lastMessageId = Number(message.id);
                        }
                    });

                    messageList.scrollTop = messageList.scrollHeight;
                }
            })
            .catch(() => {
                indicator.hidden = true;
            });
    }

    function createMessageElement(message, isMine) {
        const article = document.createElement('article');
        article.className = 'inbox-bubble-row' + (isMine ? ' is-mine' : '');

        const bubble = document.createElement('div');
        bubble.className = 'inbox-bubble' + (isMine ? ' is-mine' : '');

        const text = document.createElement('p');
        text.className = 'message-body';
        text.textContent = message.body;

        const meta = document.createElement('div');
        meta.className = 'inbox-bubble-meta';

        const sender = document.createElement('span');
        sender.textContent = message.sender_name;

        const time = document.createElement('span');
        time.textContent = formatTime(message.created_at);

        meta.appendChild(sender);
        meta.appendChild(time);
        bubble.appendChild(text);
        bubble.appendChild(meta);
        article.appendChild(bubble);

        return article;
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        pollingInterval = setInterval(pollForMessages, 5000);

        const messageList = document.getElementById('message-list');
        if (messageList) {
            messageList.scrollTop = messageList.scrollHeight;
        }
    });

    window.addEventListener('beforeunload', function () {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });
</script>
<?php endif; ?>
