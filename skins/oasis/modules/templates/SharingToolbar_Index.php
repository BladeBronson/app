	<div id="SharingToolbar">
<?php
	foreach($shareButtons as $shareButton) {
		echo '<div>';
		echo $shareButton->getShareBox();
		echo '</div>';
	}
?>

	<a class="wikia-button secondary email-link" data-lightboxShareEmailLabel="<?= wfMsg('lightbox-share-email-page-label') ?>" data-lightboxShareEmailLabelAddress="<?= wfMsg('lightbox-share-email-page-label-address') ?>" data-lightboxSend="<?= wfMsg('lightbox-send') ?>">
		<img width="0" height="0" class="sprite email" src="<?= F::app()->wg->BlankImgUrl ?>">
		<?= wfMsg('lightbox-share-button-email') ?>
	</a>

	</div>
