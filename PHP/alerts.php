<?php
$flashAlert = $_SESSION['flash_alert'] ?? null;
unset($_SESSION['flash_alert']);
$alertEscape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo $sitePrefix; ?>CSS/alerts.css">
<?php if (is_array($flashAlert)): ?>
<aside class="stay-toast <?php echo ($flashAlert['type'] ?? '') === 'success' ? 'is-success' : 'is-error'; ?>" role="<?php echo ($flashAlert['type'] ?? '') === 'success' ? 'status' : 'alert'; ?>" aria-live="<?php echo ($flashAlert['type'] ?? '') === 'success' ? 'polite' : 'assertive'; ?>">
    <span class="stay-toast-icon" aria-hidden="true"><?php echo ($flashAlert['type'] ?? '') === 'success' ? '&#10003;' : '!'; ?></span>
    <div><strong><?php echo $alertEscape($flashAlert['title'] ?? 'StaySmart'); ?></strong><p><?php echo $alertEscape($flashAlert['message'] ?? ''); ?></p></div>
    <button type="button" class="stay-toast-dismiss" aria-label="Dismiss notification">&times;</button>
</aside>
<?php endif; ?>
<dialog class="stay-confirm" id="stay-confirm-dialog" aria-labelledby="stay-confirm-title" aria-describedby="stay-confirm-message">
    <div class="stay-confirm-symbol" aria-hidden="true">?</div><h2 id="stay-confirm-title">Please confirm</h2><p id="stay-confirm-message"></p>
    <div class="stay-confirm-actions"><button type="button" id="stay-confirm-back" autofocus>Go back</button><button type="button" id="stay-confirm-yes">Confirm</button></div>
</dialog>
<script src="<?php echo $sitePrefix; ?>JS/alerts.js" defer></script>
