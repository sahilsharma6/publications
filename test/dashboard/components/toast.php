<?php if (!empty($toast)): ?>
    <div class="toast <?= $toast['type'] ?>" id="toast">
        <i class='bx <?= $toast['type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
        <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
        <button class="toast-close" onclick="dismissToast()" aria-label="Close">&times;</button>
    </div>
<?php endif; ?>