/*global describe, it, expect, modules, spyOn, beforeEach*/
describe('ext.wikia.adEngine.provider.gpt.helper', function () {
	'use strict';

	function noop() {}

	var AdElement,
		callbacks = [],
		mocks = {
			log: noop,
			googleTag: function () {},
			context: { opts: {} },
			adContext: {
				addCallback: noop,
				getContext: function () {
					return mocks.context;
				}
			},
			adDetect: {},
			adLogicPageParams: {
				getPageLevelParams: function () {
					return [];
				}
			},
			recoveryHelper: {
				addSlotToRecover: noop,
				createSourcePointTag: noop,
				recoverSlots: noop,
				isBlocking: noop,
				isRecoverable: noop,
				isRecoveryEnabled: noop
			},
			slotTweaker: {
				show: noop,
				hide: noop,
				removeDefaultHeight: noop
			},
			slotElement: {
				appendChild: noop
			},
			sraHelper: {
				shouldFlush: function () {
					return true;
				}
			}
		};
	mocks.googleTag.prototype.isInitialized = function () {
		return true;
	};
	mocks.googleTag.prototype.init = noop;
	mocks.googleTag.prototype.registerCallback = function (id, callback) {
		callbacks.push(callback);
	};
	mocks.googleTag.prototype.push = function (callback) {
		callback();
	};
	mocks.googleTag.prototype.addSlot = noop;
	mocks.googleTag.prototype.flush = noop;
	mocks.googleTag.prototype.setPageLevelParams = noop;
	mocks.sourcePointTag = mocks.googleTag;

	function getModule() {
		return modules['ext.wikia.adEngine.provider.gpt.helper'](
			mocks.log,
			mocks.adContext,
			mocks.adLogicPageParams,
			mocks.adDetect,
			AdElement,
			mocks.googleTag,
			mocks.recoveryHelper,
			mocks.slotTweaker,
			mocks.sraHelper
		);
	}

	beforeEach(function () {
		AdElement = noop;

		AdElement.prototype.getId = function () {
			return 'TOP_LEADERBOARD';
		};

		AdElement.prototype.getNode = function () {
			return {};
		};

		AdElement.prototype.updateDataParams = noop;

		callbacks = [];

		mocks.context = { opts: {} };
	});

	it('Initialize googletag when module is not initialized yet', function () {
		spyOn(mocks.googleTag.prototype, 'isInitialized').and.returnValue(false);
		spyOn(mocks.googleTag.prototype, 'init');

		getModule().pushAd('TOP_LEADERBOARD', mocks.slotElement, '/foo/slot/path', {}, {});

		expect(mocks.googleTag.prototype.init).toHaveBeenCalled();
	});

	it('Prevent initializing googletag if module is already initialized', function () {
		spyOn(mocks.googleTag.prototype, 'init');

		getModule().pushAd('TOP_LEADERBOARD', mocks.slotElement, '/foo/slot/path', {}, {});

		expect(mocks.googleTag.prototype.init).not.toHaveBeenCalled();
	});

	it('Push and flush ATF slot when SRA is not enabled', function () {
		spyOn(mocks.googleTag.prototype, 'push');
		spyOn(mocks.googleTag.prototype, 'flush');

		getModule().pushAd('TOP_LEADERBOARD', mocks.slotElement, '/foo/slot/path', {}, {});

		expect(mocks.googleTag.prototype.push).toHaveBeenCalled();
		expect(mocks.googleTag.prototype.flush).toHaveBeenCalled();
	});

	it('Only push ATF slot when SRA is enabled', function () {
		spyOn(mocks.googleTag.prototype, 'push');
		spyOn(mocks.googleTag.prototype, 'flush');
		spyOn(mocks.sraHelper, 'shouldFlush').and.returnValue(false);

		getModule().pushAd('TOP_LEADERBOARD', mocks.slotElement, '/foo/slot/path', {}, { sraEnabled: true });

		expect(mocks.googleTag.prototype.push).toHaveBeenCalled();
		expect(mocks.googleTag.prototype.flush).not.toHaveBeenCalled();
	});

	it('Always push and flush BTF slot even if SRA is enabled', function () {
		spyOn(mocks.googleTag.prototype, 'push');
		spyOn(mocks.googleTag.prototype, 'flush');

		getModule().pushAd('TOP_RIGHT_BOXAD', mocks.slotElement, '/foo/slot/path', {}, { sraEnabled: true });

		expect(mocks.googleTag.prototype.push).toHaveBeenCalled();
		expect(mocks.googleTag.prototype.flush).toHaveBeenCalled();
	});

	it('Prevent push when given slot is flushOnly', function () {
		spyOn(mocks.googleTag.prototype, 'push');
		spyOn(mocks.googleTag.prototype, 'flush');

		getModule().pushAd('GPT_FLUSH', mocks.slotElement, '/foo/slot/path', { flushOnly: true }, {});

		expect(mocks.googleTag.prototype.push).not.toHaveBeenCalled();
		expect(mocks.googleTag.prototype.flush).toHaveBeenCalled();
	});

	it('Register slot callback on push', function () {
		getModule().pushAd('TOP_RIGHT_BOXAD', mocks.slotElement, '/foo/slot/path', {}, {});

		expect(callbacks.length).toEqual(1);
	});

	it('Prevent push/flush when slot is not recoverable and pageview is blocked and recovery is enabled', function () {
		mocks.recoveryHelper.isBlocking = function () {
			return true;
		};

		mocks.recoveryHelper.isRecoverable = function () {
			return false;
		};

		spyOn(mocks.googleTag.prototype, 'push');
		spyOn(mocks.googleTag.prototype, 'flush');

		getModule().pushAd('TOP_RIGHT_BOXAD', mocks.slotElement, '/foo/slot/path', {}, { sraEnabled: true });

		expect(mocks.googleTag.prototype.push).not.toHaveBeenCalled();
		expect(mocks.googleTag.prototype.flush).not.toHaveBeenCalled();
	});

	it('Should push/flush when slot is recoverable', function () {
		mocks.recoveryHelper.isBlocking = function () {
			return true;
		};

		mocks.recoveryHelper.isRecoverable = function () {
			return true;
		};

		spyOn(mocks.googleTag.prototype, 'push');
		spyOn(mocks.googleTag.prototype, 'flush');

		getModule().pushAd('TOP_RIGHT_BOXAD', mocks.slotElement, '/foo/slot/path', {}, {
			sraEnabled: true,
			recoverableSlots: ['TOP_RIGHT_BOXAD']
		});

		expect(mocks.googleTag.prototype.push).toHaveBeenCalled();
		expect(mocks.googleTag.prototype.flush).toHaveBeenCalled();
	});
});
