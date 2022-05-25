<div class='fdm-ordering-popup-background fdm-hidden'></div>
<div <?php echo fdm_format_classes( $this->classes ); ?>>
	<div class='fdm-ordering-popup-close'>x</div>
	<div class='fdm-ordering-popup-inside'>
		<h3 id='fdm-ordering-popup-header'>
			<?php echo esc_html( $this->get_label( 'label-order-item-details' ) ); ?>
		</h3>
		<div id='fdm-ordering-popup-options'></div>
		<div id='fdm-ordering-popup-note'>
			<h5>
				<?php echo esc_html( $this->get_label( 'label-item-note' ) ); ?>
			</h5>
			<textarea name='fdm-ordering-popup-note'></textarea>
		</div>
		<div id='fdm-ordering-popup-submit'>
			<button>
				<?php echo esc_html( $this->get_label( 'label-confirm-details' ) ); ?>
			</button>
		</div>
	</div>
</div>