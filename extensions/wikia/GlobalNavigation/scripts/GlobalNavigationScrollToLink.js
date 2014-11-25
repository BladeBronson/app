require(
	['wikia.scrollToLink', 'wikia.window', 'venus.variables', 'jquery', 'wikia.browserDetect'],
	function(scrollToLink, win, variables, $, browserDetect) {
		'use strict';

		var offset = 0;

		/**
		 * @desc handler for hashchange event
		 * @param {Event} event
		 */
		function hashChangeHandler(event) {
			if (scrollToLink.handleScrollTo(win.location.hash, offset) || browserDetect.isIOS7orLower()) {
				// prevent only if we managed to scroll to desired ID
				event.preventDefault();
				return false;
			}
		}

		/**
		 * @desc Initialize: deal with hash passed as part of URL, bind handler for hash change event
		 */
		function initScrollToLink() {
			// offset is negative - we want scroll BEFORE element's top offset
			offset = -(win.document.getElementById('globalNavigation').offsetHeight +
				variables.normalTextFontSize); // also scroll a bit, so element won't be sticked to GlobalNavigation

			scrollToLink.disableBrowserJump();

			// setTimeout after 1 second is needed here because of Chrome which sometimes scrolls to top on end of DOM load
			win.setTimeout(function() {
				scrollToLink.handleScrollTo(win.location.hash, offset);
			}, 1000);

			$(win).on('hashchange', hashChangeHandler);
		}

		// bind to DOMReady
		$(initScrollToLink);
});
