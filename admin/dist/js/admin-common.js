
$(function () {
	$(".noBrowserAutofill").attr("readonly", true);
	
	setTimeout(function () {
            $(".noBrowserAutofill").attr("readonly", false);
        }, 1000);
});
