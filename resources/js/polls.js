(function() {

	function _showOtherTextInput(e) {
		$(this)
			.parents('.poll-option')
			.next('.poll-other')
			.toggleClass('hide', !$(this).prop('checked'))
			.find('input')
			.focus();
	}

	$(function() {

		$('.poll-option-input:checkbox').on('change', _showOtherTextInput);

		$('.poll-option-input:radio').on('change', function(e) {

			$(this)
				.parents('.poll-options')
				.find('.poll-other')
				.addClass('hide');

			_showOtherTextInput.call(this, e);
		});

		$('.poll-results-link').on('click', function(e) {
			e.preventDefault();
			$(this)
				.parents('.poll-form')
				.addClass('hide')
				.next('.poll-results')
				.removeClass('hide');
		});

		$('.poll-back-button').on('click', function(e) {
			e.preventDefault();
			$(this)
				.parents('.poll-results')
				.addClass('hide')
				.prev('.poll-form')
				.removeClass('hide');
		});

	});

})();
