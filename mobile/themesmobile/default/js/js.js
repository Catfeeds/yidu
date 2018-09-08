$(document).ready(function() {
	 $('.h_t').click(function() {
        $('body,html').animate({scrollTop: 0},300);
    });
	$('.list').click(function(){
	 	$('.nav').toggleClass('open-nav')
	 })

});