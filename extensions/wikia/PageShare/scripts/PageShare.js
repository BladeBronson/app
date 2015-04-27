require(['wikia.window', 'wikia.tracker', 'jquery'], function(win, tracker, $) {
	'use strict';

	var trackFunc = tracker.buildTrackingFunction({
		action: win.Wikia.Tracker.ACTIONS.CLICK,
		category: 'share',
		trackingMethod: 'both'
	});

	/**
	 * @desc Share click handler
	 *
	 * @param {Event} event
	 */
	function shareLinkClick(event) {
		event.stopPropagation();
		event.preventDefault();

		var service = $(event.target).closest('a'),
			url = service.prop('href'),
			title = service.prop('title'),
			h = (win.innerHeight / 2 | 0), // round down
			w = (win.innerWidth / 2 | 0);  // round down

		trackFunc({label: service.data('share-service')});

		win.open(url, title, 'width=' + w + ',height=' + h);
	}

	function appendShareIcons(data) {
		var $container = $('#PageShareContainer');
		if (data.socialIcons) {
			$container.html(data.socialIcons)
				.on('click', '.page-share a', shareLinkClick);
		}
	}

	function loadShareIcons() {
		var useLang = $.getUrlVar('uselang'),
			mCache = $.getUrlVar('mcache'),
			requestData,
			browserLang = (win.navigator.language || win.navigator.browserLanguage),
			browserLangShort;

		if (browserLang) {
			browserLangShort = browserLang.substr(0, 2);
		}

		requestData = {
			browserLang: browserLangShort,
			title: win.document.title,
			url: win.location.origin + win.location.pathname,
			useLang: useLang,
			isTouchScreen: win.Wikia.isTouchScreen() ? 1 : 0
		};

		if (mCache) {
			requestData.mcache = mCache;
		}

		$.nirvana.sendRequest({
			type: 'GET',
			controller: 'PageShare',
			method: 'getShareIcons',
			data: requestData,
			callback: appendShareIcons
		});
	}

	// bind events to links
	$(function() {
		loadShareIcons();
	});
});