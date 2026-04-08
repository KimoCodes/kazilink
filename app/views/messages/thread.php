<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
$messageBodyValue = !empty($errors) ? old_value('message_body') : '';
$lastMessage = $messages !== [] ? $messages[array_key_last($messages)] : null;
?>
<div class="container narrow">
    <section class="panel chat-shell">
        <div class="chat-header">
            <?php
            $title = (string) $booking['title'];
            $eyebrow = 'Conversation';
            $intro = 'Coordinate timing, access, and task details in one shared booking thread.';
            $secondaryLink = ['label' => 'Back to booking', 'href' => url_for('bookings/show', ['id' => (int) $booking['id']])];
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <div class="info-card chat-context">
                <p class="muted chat-context-meta">
                    <span>Client: <strong><?= e((string) $booking['client_name']) ?></strong></span>
                    <span>Tasker: <strong><?= e((string) $booking['tasker_name']) ?></strong></span>
                </p>
            </div>
        </div>

        <div class="message-list" id="message-list">
            <div id="polling-indicator" class="chat-polling-indicator" hidden>Checking for new messages...</div>

            <?php if ($messages === []): ?>
                <?php
                $emptyIcon = '💬';
                $emptyTitle = 'No messages yet';
                $emptyMessage = 'This is the beginning of your conversation. Start the discussion below to coordinate timing, access, or task details.';
                $emptyClass = 'empty-state-centered';
                require BASE_PATH . '/app/views/partials/empty_state.php';
                ?>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <?php $isMine = (int) $message['sender_id'] === (int) Auth::id(); ?>
                    <article class="message-thread-item<?= $isMine ? ' message-thread-item-mine' : '' ?>">
                        <div class="message-item<?= $isMine ? ' message-item-mine' : '' ?>">
                            <p class="message-body"><?= nl2br(e((string) $message['body'])) ?></p>
                        </div>
                        <div class="message-thread-meta">
                            <strong><?= e((string) $message['sender_name']) ?></strong>
                            <span aria-hidden="true">•</span>
                            <span class="message-timestamp"><?= e(dateFmt((string) $message['created_at'])) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="message-compose">
            <form method="post" action="<?= e(url_for('messages/thread')) ?>" class="form-grid" novalidate>
                <?= Csrf::input() ?>
                <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">

                <div class="message-compose-grid">
                    <?php
                    $name = 'body';
                    $label = 'Message';
                    $as = 'textarea';
                    $type = 'text';
                    $value = $messageBodyValue;
                    $placeholder = 'Write a clear message...';
                    $autocomplete = null;
                    $required = true;
                    $hint = 'Keep the thread focused on timing, access, materials, or status updates.';
                    $error = field_error($fieldErrors, 'body');
                    $class = 'form-row message-compose-field';
                    $attrs = ['maxlength' => '4000', 'rows' => '4'];
                    require BASE_PATH . '/app/views/partials/form_field.php';
                    ?>

                    <button type="submit" class="button message-send-button">Send</button>
                </div>
            </form>
        </div>
    </section>
</div>

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
                    const emptyState = messageList.querySelector('.empty-state');

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
            .catch((error) => {
                indicator.hidden = true;
                console.log('Polling error:', error);
            });
    }

    function createMessageElement(message, isMine) {
        const article = document.createElement('article');
        article.className = 'message-thread-item' + (isMine ? ' message-thread-item-mine' : '');

        const bubble = document.createElement('div');
        bubble.className = 'message-item' + (isMine ? ' message-item-mine' : '');

        const text = document.createElement('p');
        text.className = 'message-body';
        text.textContent = message.body;
        bubble.appendChild(text);

        const metadata = document.createElement('div');
        metadata.className = 'message-thread-meta';

        const name = document.createElement('strong');
        name.textContent = message.sender_name;
        metadata.appendChild(name);

        const separator = document.createElement('span');
        separator.textContent = '•';
        separator.setAttribute('aria-hidden', 'true');
        metadata.appendChild(separator);

        const time = document.createElement('span');
        time.className = 'message-timestamp';
        time.textContent = formatDateTime(message.created_at);
        metadata.appendChild(time);

        article.appendChild(bubble);
        article.appendChild(metadata);

        return article;
    }

    function formatDateTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / (1000 * 60));

        if (diffMins < 1) {
            return 'Just now';
        }

        if (diffMins < 60) {
            return `${diffMins} minute${diffMins === 1 ? '' : 's'} ago`;
        }

        if (diffMins < 1440) {
            const diffHours = Math.floor(diffMins / 60);
            return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
        }

        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
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
