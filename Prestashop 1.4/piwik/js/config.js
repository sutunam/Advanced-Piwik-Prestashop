jQuery(document).ready(function($){
	$('.tracking-method').click(function(){
		if ($(this).val() == 'php'){
			$('.php-tracking-detail').slideDown();
		} else {
			$('.php-tracking-detail').slideUp();
		}
	});
});