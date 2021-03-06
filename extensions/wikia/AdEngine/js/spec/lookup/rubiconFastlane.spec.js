/*global beforeEach, describe, it, modules, expect, spyOn*/
describe('ext.wikia.adEngine.lookup.rubiconFastlane', function () {
	'use strict';
	function noop() {}

	var slotParams = {},
		mocks = {
			targeting: {},
			adContext: {
				getContext: function () {
					return {
						targeting: mocks.targeting
					};
				}
			},
			tracker: {
				measureDiff: function () {
					return mocks.tracker;
				},
				track: noop
			},
			adTracker: {
				measureTime: function () {
					return mocks.tracker;
				},
				track: noop
			},
			adLogicZoneParams: {
				getSite: function () {
					return 'life';
				},
				getMappedVertical: function () {
					return '_dragonball';
				},
				getPageType: function () {
					return 'article';
				},
				getLanguage: function () {
					return 'en';
				}
			},
			doc: {
				node: {
					parentNode: {
						insertBefore: noop
					}
				},
				createElement: function () {
					return mocks.doc.node;
				},
				getElementsByTagName: function () {
					return [mocks.doc.node];
				}
			},
			log: noop,
			slot: {
				setFPI: function (key, value) {
					slotParams[key] = value;
				},
				setPosition: noop,
				getAdServerTargeting: function () {
					return [{
						key: 'rpflKey',
						values: ['1_tier', '3_tier']
					}];
				}
			},
			win: {
				rubicontag: {
					cmd: [],
					run: function (onResponse) {
						onResponse();
					},
					defineSlot: function () {
						return mocks.slot;
					}
				}
			}
		};

	function getRubiconFastlane() {
		return modules['ext.wikia.adEngine.lookup.rubiconFastlane'](
			mocks.adContext,
			mocks.adTracker,
			mocks.adLogicZoneParams,
			mocks.doc,
			mocks.log,
			mocks.win
		);
	}

	beforeEach(function () {
		mocks.win.rubicontag.cmd.push = function (callback) {
			callback();
		};
		mocks.targeting = {
			pageType: 'article'
		};
		slotParams = {};
	});

	it('Returns falsy status without initialise', function () {
		var rubiconFastlane = getRubiconFastlane();

		expect(rubiconFastlane.wasCalled()).toBeFalsy();
	});

	it('Returns truthy status after initialise', function () {
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('oasis');

		expect(rubiconFastlane.wasCalled()).toBeTruthy();
	});

	it('Define all 7 slots for oasis skin', function () {
		spyOn(mocks.win.rubicontag, 'defineSlot').and.callThrough();
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('oasis');

		expect(mocks.win.rubicontag.defineSlot.calls.count()).toEqual(7);
	});

	it('Define all 3 slots for mercury skin', function () {
		spyOn(mocks.win.rubicontag, 'defineSlot').and.callThrough();
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('mercury');

		expect(mocks.win.rubicontag.defineSlot.calls.count()).toEqual(3);
	});

	it('Define /TOP/ slot as atf', function () {
		spyOn(mocks.slot, 'setPosition');
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('oasis');

		expect(mocks.slot.setPosition.calls.argsFor(0)[0]).toEqual('atf');
	});

	it('Define not-/TOP/ slot as btf', function () {
		spyOn(mocks.slot, 'setPosition');
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('oasis');

		expect(mocks.slot.setPosition.calls.argsFor(2)[0]).toEqual('btf');
	});

	it('Do not define position on mobile', function () {
		spyOn(mocks.slot, 'setPosition');
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('mercury');

		expect(mocks.slot.setPosition).not.toHaveBeenCalled();
	});

	it('Returns empty parameters list on not defined slot', function () {
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('mercury');

		expect(rubiconFastlane.getSlotParams('TOP_LEADERBOARD')).toEqual({});
	});

	it('Returns parameters list on defined slot', function () {
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('mercury');

		expect(rubiconFastlane.getSlotParams('MOBILE_TOP_LEADERBOARD')).toEqual({
			rpflKey: ['1_tier', '3_tier']
		});
	});

	it('Sets FPI.src to mobile on mercury', function () {
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('mercury');

		expect(slotParams.src).toEqual('mobile');
	});

	it('Sets FPI.src to gpt on oasis', function () {
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('oasis');

		expect(slotParams.src).toEqual('gpt');
	});

	it('Sets FPI.s1 to dbName when it is in top1k', function () {
		mocks.targeting.wikiIsTop1000 = true;
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('oasis');

		expect(slotParams.s1).toEqual('_dragonball');
	});

	it('Sets FPI.s1 to defined string when it is not in top1k', function () {
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('oasis');

		expect(slotParams.s1).toEqual('not a top1k wiki');
	});

	it('Sets other FPI based on AdLogicZoneParams', function () {
		var rubiconFastlane = getRubiconFastlane();

		rubiconFastlane.call('oasis');

		expect(slotParams.s0).toEqual('life');
		expect(slotParams.s2).toEqual('article');
		expect(slotParams.lang).toEqual('en');
	});
});
