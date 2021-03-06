<?php
use Wikia\ContentReview\Models\ReviewModel;
use Wikia\ContentReview\Models\ReviewLogModel;
use Wikia\ContentReview\Models\CurrentRevisionModel;
use Wikia\ContentReview\Helper;

class ContentReviewSpecialController extends WikiaSpecialPageController {

	public static $statusMessageKeys = [
		ReviewModel::CONTENT_REVIEW_STATUS_UNREVIEWED => 'content-review-status-unreviewed',
		ReviewModel::CONTENT_REVIEW_STATUS_IN_REVIEW => 'content-review-status-in-review',
		ReviewModel::CONTENT_REVIEW_STATUS_APPROVED => 'content-review-status-approved',
		ReviewModel::CONTENT_REVIEW_STATUS_REJECTED => 'content-review-status-rejected',
		ReviewModel::CONTENT_REVIEW_STATUS_AUTOAPPROVED => 'content-review-status-autoapproved',
		/**
		 * The `live` index is introduced this way deliberately since it is not an actual status
		 * of a review, it is used only for presentational purposes.
		 */
		'live' => 'content-review-status-live',
	];

	function __construct() {
		parent::__construct( 'ContentReview', 'content-review', true );
	}

	public function init() {
		$this->specialPage->setHeaders();

		\Wikia::addAssetsToOutput( 'content_review_special_page_js' );
		\JSMessages::enqueuePackage( 'ContentReviewSpecialPage', \JSMessages::EXTERNAL );
	}

	protected function checkAccess() {
		if( !$this->wg->User->isLoggedIn() || !$this->wg->User->isAllowed( 'content-review' ) ) {
			return false;
		}
		return true;
	}

	public function index() {
		$this->specialPage->setHeaders();

		if( !$this->checkAccess() ) {
			$this->displayRestrictionError();
			return false;
		}

		$this->getOutput()->setPageTitle( wfMessage( 'content-review-special-title' )->plain() );

		$wikiId = $this->getPar();

		if ( !empty( $wikiId ) ) {
			$this->setVal( 'baseSpecialPageUrl', $this->specialPage->getTitle()->getFullURL() );

			$logModel = new ReviewLogModel();
			$revisionModel = new CurrentRevisionModel();

			$reviews = $logModel->getArchivedReviewsForWiki( $wikiId );
			$reviewed = $revisionModel->getLatestReviewedRevisionForWiki( $wikiId );

			$this->reviews = $this->prepareArchivedReviewData( $reviews, $reviewed );
			$this->overrideTemplate( 'archive' );
		} else {
			$reviewModel = new ReviewModel();
			$reviews = $reviewModel->getContentToReviewFromDatabase();
			$this->reviews = $this->prepareReviewData( $reviews );
		}
	}

	private function prepareReviewData( $reviewsRaw ) {
		$reviews = [];

		foreach ( $reviewsRaw as $review ) {
			$title = GlobalTitle::newFromID( $review['page_id'], $review['wiki_id'] );

			if ( !is_null( $title ) ) {
				$wiki = WikiFactory::getWikiByID( $review['wiki_id'] );

				$review['url'] = $title->getFullURL( [
					'oldid' => $review['revision_id'],
				] );
				$review['title'] = $title->getText();
				$review['wiki'] = $wiki->city_title;
				$review['wikiArchiveUrl'] = $this->getContext()->getTitle()->getFullURL( [
					self::PAR => $review['wiki_id'],
				] );

				$review['user'] = User::newFromId( $review['submit_user_id'] )->getName();
				$review['diff'] = $title->getFullURL( [
					'diff' => $review['revision_id'],
					'oldid' => $review['reviewed_id'],
					Helper::CONTENT_REVIEW_PARAM => 1,
				] );
				$review['diffText'] = $review['status'] == ReviewModel::CONTENT_REVIEW_STATUS_UNREVIEWED
					? wfMessage( 'content-review-special-start-review' )->escaped()
					: wfMessage( 'content-review-special-continue-review' )->escaped();

				if ( !empty( $review['review_user_id'] ) ) {
					$review['review_user_name'] = User::newFromId( $review['review_user_id'] )->getName();
				}

				$reviewKey = implode( ':', [
					$review['wiki_id'],
					$review['page_id'],
					ReviewModel::CONTENT_REVIEW_STATUS_IN_REVIEW
				] );
				if ( $review['status'] == ReviewModel::CONTENT_REVIEW_STATUS_UNREVIEWED
					&& isset( $reviewsRaw[$reviewKey] )
				) {
					$review['hide'] = true;
				}

				$reviews[$review['wiki_id']][] = $review;
			}
		}

		return $reviews;
	}

	private function prepareArchivedReviewData( $reviewsRaw, $reviewed ) {
		$reviews = [];

		foreach ( $reviewsRaw as $review ) {
			$title = GlobalTitle::newFromID( $review['page_id'], $review['wiki_id'] );

			if ( !is_null( $title ) ) {
				$review['title'] = $title->getText();
				$review['diff'] = $title->getFullURL( [
					'oldid' => $review['revision_id']
				] );

				if ( !empty( $review['review_user_id'] ) ) {
					$review['review_user_name'] = User::newFromId( $review['review_user_id'] )->getName();
				}

				if ( $review['revision_id'] == $reviewed[$review['page_id']]['revision_id'] ) {
					$review['status'] = 'live';
				}

				if ( $review['status'] == ReviewModel::CONTENT_REVIEW_STATUS_APPROVED
					|| $review['status'] == ReviewModel::CONTENT_REVIEW_STATUS_AUTOAPPROVED
				) {
					$review['restore'] = true;
					$review['restoreUrl'] = $title->getFullURL( [
						'oldid' => $review['revision_id'],
						'action' => 'edit',
						'summary' => wfMessage( 'content-review-restore-summary' )
							->inLanguage( $title->getPageLanguage() )
							->params( $review['revision_id'] )
							->escaped(),
					] );
				}

				$reviews[$review['page_id']][] = $review;
			}
		}

		return $reviews;
	}
}
