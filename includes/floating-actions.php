<?php
$whatsappUrl = $whatsappUrl ?? $defaultWhatsappUrl;
?>
<!-- Floating Buttons -->
<div class="floating-actions" id="floatingActions">
<a aria-label="Chat WhatsApp" class="float-btn float-wa" href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8'); ?>" rel="noopener" target="_blank"><i class="bi bi-whatsapp"></i><span class="wa-tooltip">Chat Sekarang! Dapatkan Penawaran
                Harga Menarik.</span></a>
<button aria-label="Back to top" class="float-btn float-top" id="backToTop" type="button"><i class="bi bi-arrow-up"></i></button>
</div>
