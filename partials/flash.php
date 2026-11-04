<div class="container" id="flash">
    <?php $messages = get_messages(); ?>
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg): ?>
            <?php
            $color = htmlspecialchars($msg["color"] ?? "info");
            $text = htmlspecialchars($msg["text"] ?? "");
            ?>
            <div class="row justify-content-center">
                <div class="alert alert-<?php echo $color; ?>" role="alert">
                    <?php // Temporary prefix for clearer evidence gathering; remove after all milestones are complete. ?>
                    <?php echo $text; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    #flash {
        left: 50%;
        transform: translateX(-50%);
        width: auto;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        opacity: 0.9;
        z-index: 1000;
        position: fixed;
        top: 1rem;
    }

    #flash:empty,
    #flash:blank,
    #flash:not(:has(*)):not(:empty) {
        display: none;
    }
</style>

<script>
    (() => {
        // 30 seconds helps when gathering milestone evidence; use 5000 for 5 seconds later.
        let flash_message_delay = 5000;

        // Check the flash area on a short repeating timer.
        function process_flash_messages() {
            const now = Date.now();

            document.querySelectorAll("#flash .row").forEach((message) => {
                // data-remove-at stores the future time when this message should disappear.
                if (!message.dataset.removeAt) {
                    message.dataset.removeAt = now + flash_message_delay;
                }

                const remove_at = Number(message.dataset.removeAt);
                if (remove_at <= now) {
                    message.remove();
                }
            });
        }

        // 100 milliseconds = 0.1 seconds.
        setInterval(process_flash_messages, 100);
    })();
</script>