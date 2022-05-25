jQuery(document).ready(function($){
	$(function(){
		$(window).resize(function(){
			$('.fdm-image-style-image-wrapper').each(function(){
				var thisImageWrapper = $(this);
				var thisImageWrapperWidth = thisImageWrapper.width();
				thisImageWrapper.css('height', thisImageWrapperWidth+'px');
			});
			var maxHeight = -1;
			$('.fdm-menu-image .fdm-item').each(function(){
				maxHeight = maxHeight > $(this).height() ? maxHeight : $(this).height();
			});
			$('.fdm-menu-image .fdm-item').each(function(){
				$(this).height(maxHeight);
			});
		}).resize();
	});
	// $('.fdm-menu-image .fdm-item-content p').text(function(index, currentText) {
	// 	return currentText.substr(0,85);
	// });
});
