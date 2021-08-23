$(document).ready(() => {
	$('#toggle').on('click', function () {
		$this = $(this);
		if (!$this.hasClass('on')) {
			$this.addClass('on');
		} else {
			$this.removeClass('on');
		}
	});
});