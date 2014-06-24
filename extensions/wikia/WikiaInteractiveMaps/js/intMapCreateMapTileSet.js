define(
	'wikia.intMap.createMap.tileSet',
	[
		'jquery',
		'wikia.window',
		'wikia.intMap.utils'
	],
	function($, w, utils) {
		'use strict';

		// reference to modal component
		var modal,
			// mustache template
			uiTemplate,
			tileSetThumbTemplate,
			// template data
			templateData = {
				mapType: [
					{
						type: 'geo',
						name: $.msg('wikia-interactive-maps-create-map-choose-type-geo'),
						event: 'selectTileSet'
					},
					{
						type: 'custom',
						name: $.msg('wikia-interactive-maps-create-map-choose-type-custom'),
						event: 'browseTileSets'
					}
				],
				chooseTileSetTip: $.msg('wikia-interactive-maps-create-map-choose-tile-set-tip'),
				browse: $.msg('wikia-interactive-maps-create-map-browse-tile-set'),
				uploadLink: $.msg('wikia-interactive-maps-create-map-upload-file'),
				searchPlaceholder: $.msg('wikia-interactive-maps-create-map-search-tile-set-placeholder'),
				clearSearch: $.msg('wikia-interactive-maps-create-map-clear-tile-set-search')
			},
			//modal events
			events = {
				chooseTileSet: [
					chooseTileSet
				],
				browseTileSets: [
					function() {
						showStep('browseTileSet');
					}
				],
				clearSearch: [
					clearSearchFilter
				],
				selectTileSet: [
					selectTileSet
				],
				uploadTileSetImage: [
					function() {
						$uploadInput.click()
					}
				],
				previousStep: [
					previousStep
				]
			},
			// steps for choose tile set
			steps = {
				selectType: {
					id: '#intMapChooseType',
					buttons: {}
				},
				browseTileSet: {
					id: '#intMapBrowse',
					buttons: {
						'#intMapBack': 'previousStep'
					},
					helper: loadTileSets
				}
			},
			noTileSetMsg = $.msg('wikia-interactive-maps-create-map-no-tile-set-found'),
			// stack for holding choose tile set steps
			stepsStack = [],
			// cached selectors
			$sections,
			$tileSetsContainer,
			$uploadInput,
			$clearSearchBtn,
			$searchInput;

		/**
		 * @desc initializes and configures UI
		 * @param {object} _modal - modal component
		 * @param {string} _uiTemplate - mustache template for this step UI
		 * @param {string} _tileSetThumbTemplate - mustache template for tile set thumb
		 */
		function init(_modal, _uiTemplate, _tileSetThumbTemplate) {
			modal = _modal;
			uiTemplate = _uiTemplate;
			tileSetThumbTemplate = _tileSetThumbTemplate;

			utils.bindEvents(modal, events);

			// set base step
			addToStack('selectType');

			// TODO: figure out where is better place to place it and move it there
			modal.$element
				.on('change', '#intMapUpload', function(event) {
					uploadNewTileSetImage(event.target.parentNode);
				})
				.on('keyup', '#intMapTileSetSearch', $.debounce(250, searchForTileSets));

		}

		/**
		 * @desc entry point for choose tile set steps
		 */
		function chooseTileSet() {
			modal.$innerContent.html(utils.render(uiTemplate, templateData));

			// cache selectors
			$sections = modal.$innerContent.children();
			$tileSetsContainer = $('#intMapTileSetsList');
			$uploadInput =  $('#intMapUpload');
			$clearSearchBtn = $('#intMapClearSearch');
			$searchInput = $('#intMapTileSetSearch');

			showStep(stepsStack.pop());
		}

		/**
		 * @desc adds step to steps stack
		 * @param {string} step - key of the step
		 */
		function addToStack(step) {
			stepsStack.push(step);
		}

		/**
		 * @desc shows step content
		 * @param {string} id - step is
		 */
		function showStepContent(id) {
			$sections.addClass('hidden');
			$sections.filter(id).removeClass('hidden');
		}

		/**
		 * @desc shows the given step in choose tile set flow
		 * @param {string} stepName - name of the step
		 */
		function showStep(stepName) {
			var step = steps[stepName];

			addToStack(stepName);
			showStepContent(step.id);
			utils.setButtons(modal, step.buttons);

			if (typeof step.helper === 'function') {
				step.helper();
			}

			modal.trigger('cleanUpError');

		}

		/**
		 * @desc switches to the previous step in create map flow
		 */
		function previousStep() {
			// removes current step from stack
			stepsStack.pop();

			showStep(stepsStack.pop());
		}

		/**
		 * @desc handler function for selecting tile set
		 * @param {Event} event
		 */
		function selectTileSet(event) {
			var $target = $(event.currentTarget);

			modal.trigger('previewTileSet', {
				type: $target.data('type'),
				tileSetId: $target.data('id')
			});
		}

		/**
		 * @desc handler function for search tile set input field
		 * @param {Event} event - search term
		 */
		function searchForTileSets(event) {
			var trimmedKeyword = event.target.value.trim();

			if (trimmedKeyword.length >= 2) {
				loadTileSets(trimmedKeyword);
				$clearSearchBtn.removeClass('hidden');
			}
		}

		/**
		 * @desc handler for clearing search filter - reverts to initial tile set list
		 */
		function clearSearchFilter() {
			$clearSearchBtn.addClass('hidden');
			$searchInput.val('');

			// load initial set of tile sets without keyword filter
			loadTileSets();
		}
		/**
		 * @desc sets up choose tile set step
		 * @param {string=} keyword - search term
		 */
		function loadTileSets(keyword) {
			getTileSets(keyword).done(function(tileSetData) {
				updateTileSetList(renderTileSetsListMarkup(tileSetThumbTemplate, tileSetData));
			});
		}

		/**
		 * @desc sends request to backend for tile sets
		 * @param {string=} searchTerm - search term, if specified loads tile set which name match this term
		 */
		function getTileSets(searchTerm) {
			var dfd = new $.Deferred();

			$.nirvana.sendRequest({
				controller: 'WikiaInteractiveMapsMap',
				method: 'getTileSets',
				format: 'json',
				type: 'GET',
				data: searchTerm ? {searchTerm: searchTerm} : null,
				callback: function(response) {
					var data = response.results;

					if (data && data.success) {
						dfd.resolve(data.content);
					} else {
						dfd.reject();
						modal.trigger('error', data.content.message);
					}
				},
				onErrorCallback: function(response) {
					dfd.reject();
					modal.trigger('error', response.results.content.message);
				}
			});

			return dfd.promise();
		}

		/**
		 * @desc renders tile set thumbs markup
		 * @param {string} template - mustache template
		 * @param {array} tileSets - array of tile set objects
		 * @returns {string} - HTML markup
		 */
		function renderTileSetsListMarkup(template, tileSets) {
			var html = '';

			tileSets.forEach(function(tileSet) {
				html += utils.render(template, tileSet);
			});

			return html;
		}

		/**
		 * @desc removes old tile sets from list and adds new one
		 * @param {string} markup - HTML markup
		 */
		function updateTileSetList(markup) {
			$tileSetsContainer.children('.tile-set-thumb').remove();
			modal.trigger('cleanUpError');

			if (markup) {
				$tileSetsContainer.append(markup);
			} else {
				modal.trigger('error', noTileSetMsg);
			}
		}

		/**
		 * @desc uploads tile set image to backend
		 * @param {object} form - html form node element
		 */
		function uploadNewTileSetImage(form) {
			var formData = new FormData(form);

			utils.upload(modal, formData, 'map', function (data) {
				data.type = 'custom';
				modal.trigger('previewTileSet', data);
			});
		}

		return {
			init: init
		};
	}
);
