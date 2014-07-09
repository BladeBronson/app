define(
	'wikia.intMap.utils',
	[
		'jquery',
		'wikia.window',
		'wikia.cache',
		'wikia.loader',
		'wikia.ui.factory',
		'wikia.mustache'
	],
	function($, w, cache, loader, uiFactory, mustache) {
		'use strict';

		/**
		 * @desc loads all assets for create map modal and initialize it
		 * @param {object} action - object with paths to different assets
		 * @param {object=} params - local storage key
		 * @param {function=} trigger - trigger function which returns data to ponto bridge
		 */
		function loadModal(action, params, trigger) {
			var amdModule = action.module,
				loadedAssets = action.assets;

			// check if the assets were already loaded
			if (loadedAssets) {
				initModule(amdModule, loadedAssets.mustache, params, trigger);
			} else {
				getAssets(convertSource(action.source), action.cacheKey).then(function (assets) {
					addAssetsToDOM(assets);

					// caching loaded assets in action configuration object so we wont load them second time
					action.assets = assets;

					initModule(amdModule, assets.mustache, params, trigger);
				});
			}
		}

		/**
		 * @desc gets assets
		 * @param {object} source - object with paths to different assets
		 * @param {string} cacheKey - local storage key
		 * @returns {object} - promise
		 */
		function getAssets(source, cacheKey) {
			var dfd = new $.Deferred(),
				assets = cache.getVersioned(cacheKey);

			if (assets) {
				dfd.resolve(assets);
			} else {
				loader({
					type: loader.MULTI,
					resources: source
				}).done(function (assets) {
					dfd.resolve(assets);
				});
			}

			return dfd.promise();
		}

		/**
		 * @desc converts paths to assets in arrays to comma separated strings
		 * @param {object} source - object with arrays of paths to different type assets
		 * @returns {object} - object with arrays converted to comma separated strings
		 */
		function convertSource(source) {
			var convertedSource = {};

			Object.keys(source).forEach(function (type) {
				convertedSource[type] = source[type].join();
			});

			return convertedSource;
		}

		/**
		 * @desc adds scripts and styles to DOM
		 * @param {object} assets - object with assets
		 */
		function addAssetsToDOM(assets) {
			loader.processScript(assets.scripts);
			loader.processStyle(assets.styles);
		}

		/**
		 * @desc initialises action modal
		 * @param {string} amdModule - module name
		 * @param {array} templates - mustache templates
		 * @param {object=} params - local storage key
		 * @param {function=} trigger - trigger function which returns data to ponto bridge
		 */
		function initModule(amdModule, templates, params, trigger) {
			require([amdModule], function (module) {
				module.init(templates, params, trigger);
			});
		}

		/**
		 * @desc creates modal component
		 * @param {object} config - modal config
		 * @param {function} cb - callback function called after creating modal
		 */
		function createModal(config, cb) {
			uiFactory.init(['modal']).then(function (modal) {
				modal.createComponent(config, cb);
			});
		}

		/**
		 * @desc bind events to a modal
		 * @param {object} modal - modal component
		 * @param {object} events - object containing array of handlers for each event type
		 */
		function bindEvents(modal, events) {
			Object.keys(events).forEach(function(event) {
				events[event].forEach(function(handler) {
					modal.bind(event, handler);
				});
			});
		}

		/**
		 * @desc render template
		 * @param {string} template - mustache template
		 * @param {object} templateData - mustache template variables
		 * @param {object=} partials - mustache partials
		 */
		function render(template, templateData, partials) {
			return mustache.render(template, templateData, (typeof partials === 'object' ? partials : null));
		}

		/**
		 * @desc configure buttons (set visibility + attach events)
		 * @param {object} modal - modal component
		 * @param {object} buttons - object with button ids as keys and event names as values
		 */
		function setButtons(modal, buttons) {
			// reset buttons visibility
			modal.$buttons.addClass('hidden');

			Object.keys(buttons).forEach(function(key) {
				modal.$buttons
					.filter(key)
					.data('event', buttons[key])
					.removeClass('hidden');
			});
		}

		/**
		 * @desc check if string is empty
		 * @param {string} value
		 * @returns {boolean}
		 */
		function isEmpty(value) {
			return value.trim().length === 0;
		}

		/**
		 * @desc serializes form
		 * @param {object} $form - jQuery selector for form element
		 * @returns {object} - promise, resolves with serialized form
		 */
		function serializeForm($form) {
			var serializedForm = {},
				formArray = $form.serializeArray(),
				fieldNameIsArrayRegex = /\[\]$/;

			$.each(formArray, function (i, element) {
				var name = element.name,
					value = element.value;

				if (fieldNameIsArrayRegex.test(name)) {
					if ($.isArray(serializedForm[name])) {
						serializedForm[name].push(value);
					} else {
						serializedForm[name] = [value];
					}
				} else {
					serializedForm[name] = value;
				}
			});

			return serializedForm;
		}

		/**
		 * @desc
		 * @param {object} modal - modal component
		 * @param {FormData} formData - FormData object with file input named wpUploadFile
		 * @param {string} uploadType - upload type, like "map" or "marker"
		 * @param {function=} successCallback - optional callback to call after successful request
		 */
		function upload(modal, formData, uploadType, successCallback) {
			var uploadEntryPoint = '/wikia.php?controller=WikiaInteractiveMapsBase&method=upload&uploadType=' +
				uploadType + '&format=json';

			modal.deactivate();

			$.ajax({
				contentType: false,
				data: formData,
				processData: false,
				type: 'POST',
				url: w.wgScriptPath + uploadEntryPoint,
				success: function(response) {
					var data = response.results;

					if (data && data.success) {
						cleanUpError(modal);

						if (typeof successCallback === 'function') {
							successCallback(data);
						}
					} else {
						showError(modal, data.errors.pop());
					}

					modal.activate();
				},
				error: function(response) {
					showError(modal, response.results.error);
					modal.activate();
				}
			});
		}

		/**
		 * @desc checks if user is logged in
		 * @returns {boolean}
		 */
		function isUserLoggedIn() {
			return (w.wgUserName !== null);
		}

		/**
		 * @desc launches force login modal
		 * @param {string} origin - used for tracking the source of force login modal
		 * @param {function} cb - callback function to be called after login
		 */
		function showForceLoginModal(origin, cb) {
			w.UserLoginModal.show({
				origin: origin,
				callback: function() {
					w.UserLogin.forceLoggedIn = true;
					cb();
				}
			});
		}

		/**
		 * @desc refreshes page after closing modal
		 * @todo do we need to do this?
		 */
		function refreshIfAfterForceLogin() {
			w.UserLogin.refreshIfAfterForceLogin();
		}

		function handleNirvanaException(modal, response) {
			showError(modal, response.statusText);
		}

		/**
		 * @desc displays error message
		 * @param {object} modal - modal object
		 * @param {string} message - error message
		 */
		function showError(modal, message) {
			modal.$errorContainer
				.html(message)
				.removeClass('hidden');
		}

		/**
		 * @desc cleans up error message and hides error container
		 */
		function cleanUpError(modal) {
			modal.$errorContainer
				.html('')
				.addClass('hidden');
		}

		/**
		 * @desc creates image url for thumbnailer
		 * @param {string} url - image url
		 * @param {number} width
		 * @param {number=} height
		 * @returns {string} - thumb url
		 */
		function createThumbURL(url, width, height) {
			var breakPoint = url.lastIndexOf('/'),
				baseUrl = url.slice(0, breakPoint),
				fileName = url.slice(breakPoint + 1),
				crop = (height ? width + 'x' + height : width + 'px') + '-';

			return baseUrl + '/thumb/' + fileName + '/' + crop + fileName;
		}

		return {
			loadModal: loadModal,
			createModal: createModal,
			bindEvents: bindEvents,
			render: render,
			setButtons: setButtons,
			isEmpty: isEmpty,
			serializeForm: serializeForm,
			upload: upload,
			isUserLoggedIn: isUserLoggedIn,
			showForceLoginModal: showForceLoginModal,
			refreshIfAfterForceLogin: refreshIfAfterForceLogin,
			handleNirvanaException: handleNirvanaException,
			showError: showError,
			cleanUpError: cleanUpError,
			createThumbURL: createThumbURL
		}
	}
);