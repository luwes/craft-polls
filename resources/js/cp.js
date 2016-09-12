(function() {

	var Polls = function() {

		this.initQuestionsIndex = function() {

			$('.options-menubtn').each(function() {

				new Garnish.MenuBtn(this);
			});
		};
	};

	window.polls = new Polls();

})();
