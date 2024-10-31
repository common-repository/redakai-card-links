var $j = jQuery.noConflict();

$j(document).ready(function() {
	$j('a.redakaicardref_rollover').cluetip({
		arrows: true,
		showTitle: false,
		dropShadow: false,
		clickThrough: true,
		width: 242
	});
});