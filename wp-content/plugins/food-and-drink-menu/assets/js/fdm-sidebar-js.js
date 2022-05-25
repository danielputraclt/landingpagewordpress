jQuery(document).ready(function($){
	$('.fdm-menu-sidebar-section-title:first-of-type').addClass('fdm-menu-sidebar-section-title-selected');
	$('.fdm-menu-sidebar-section-description:nth-of-type(2)').removeClass('fdm-hidden');

	if(fdmFromSettings.sidebar_click_action == 'onlyselected'){
		$('.fdm-the-menu').addClass('onlyselected');
		$('.fdm-section').addClass('fdm-hidden');
		$('.fdm-column:first-of-type .fdm-section:first-of-type').removeClass('fdm-hidden');
	}

	$('.fdm-menu-sidebar-section-title').click(function(){
		var thisSection = $(this).attr('id');
		$('.fdm-menu-sidebar-section-title').removeClass('fdm-menu-sidebar-section-title-selected');
		$('.fdm-menu-sidebar-section-description').addClass('fdm-hidden');
		$('.fdm-menu-sidebar-section-title#'+thisSection).addClass('fdm-menu-sidebar-section-title-selected');
		$('.fdm-menu-sidebar-section-description#'+thisSection).removeClass('fdm-hidden');
		if(fdmFromSettings.sidebar_click_action == 'scroll'){
			$('html, body').animate({
				scrollTop: $('#fdm-section-header-'+thisSection).offset().top - 120
			}, 500);
		}
		if(fdmFromSettings.sidebar_click_action == 'onlyselected'){
			$('.fdm-section').addClass('fdm-hidden');
			$('.fdm-section-'+thisSection).removeClass('fdm-hidden');
		}
		$('.fdm-image-style-image-wrapper').each(function(){
			var thisImageWrapper = $(this);
			var thisImageWrapperWidth = thisImageWrapper.width();
			thisImageWrapper.css('height', thisImageWrapperWidth+'px');
		});
	});
});
