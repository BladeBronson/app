<?php

namespace Wikia\TemplateClassification;

use Swagger\Client\ApiException;
use Wikia\Interfaces\IRequest;
use Wikia\TemplateClassification\Permissions;
use Wikia\TemplateClassification\UnusedTemplates\Handler;

class Hooks {

	/**
	 * Register hooks for the extension
	 */
	public static function register() {
		$hooks = new self();
		\Hooks::register( 'BeforePageDisplay', [ $hooks, 'onBeforePageDisplay' ] );
		\Hooks::register( 'PageHeaderPageTypePrepared', [ $hooks, 'onPageHeaderPageTypePrepared' ] );
		\Hooks::register( 'QueryPageUseResultsBeforeRecache', [ $hooks, 'onQueryPageUseResultsBeforeRecache' ] );
		/* Edit page hooks */
		\Hooks::register( 'ArticleSaveComplete', [ $hooks, 'onArticleSaveComplete' ] );
		\Hooks::register( 'EditPage::showEditForm:fields', [ $hooks, 'onEditPageShowEditFormFields' ] );
		\Hooks::register( 'EditPageLayoutExecute', [ $hooks, 'onEditPageLayoutExecute' ] );
		\Hooks::register( 'EditPageMakeGlobalVariablesScript', [ $hooks, 'onEditPageMakeGlobalVariablesScript' ] );
	}

	/**
	 * Save template type passed from article creation
	 * changed template type is stored in templateClassificationTypeNew hidden field.
	 * Previous type is stored in templateClassificationTypeCurrent.
	 *
	 * @param \WikiPage $wikiPage
	 * @param \User $user
	 * @return bool
	 */
	public function onArticleSaveComplete( \WikiPage $article, \User $user, $text, $summary,
		$minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId
	) {
		global $wgCityId;

		$request = \RequestContext::getMain()->getRequest();
		$typeNew = $request->getVal( 'templateClassificationTypeNew' );
		$typeCurrent = $request->getVal( 'templateClassificationTypeCurrent' );

		/**
		 * The service was not available when the field's value was set
		 * so we exit early to prevent polluting of the results.
		 */
		if ( empty( $typeNew )
			|| $typeNew === \TemplateClassificationService::NOT_AVAILABLE
			|| $typeNew === $typeCurrent
		) {
			return true;
		}

		try {
			( new \TemplateClassificationService() )->classifyTemplate(
				$wgCityId,
				$article->getId(),
				$typeNew,
				\TemplateClassificationService::USER_PROVIDER,
				$user->getId()
			);
		} catch ( ApiException $e ) {
			( new Logger() )->exception( $e );
			\BannerNotificationsController::addConfirmation(
				wfMessage( 'template-classification-notification-error-retry' )->escaped(),
				\BannerNotificationsController::CONFIRMATION_WARN
			);
		}
		return true;
	}

	/**
	 * Add global variables for Javascript
	 * @param array $aVars
	 * @return bool
	 */
	public function onEditPageMakeGlobalVariablesScript( array &$aVars ) {
		$context = \RequestContext::getMain();
		// Enable TemplateClassificationEditorPlugin
		if ( ( new Permissions() )->shouldDisplayEntryPoint( $context->getUser(), $context->getTitle() )
			&& $this->isEditPage()
		) {
			$aVars['enableTemplateClassificationEditorPlugin'] = true;
		}
		return true;
	}

	/**
	 * Add hidden input to editform with template type
	 * @param \EditPage $editPage
	 * @param \OutputPage $out
	 * @return bool
	 */
	public function onEditPageShowEditFormFields( \EditPage $editPage, \OutputPage $out ) {
		global $wgCityId;

		$context = \RequestContext::getMain();

		if ( ( new Permissions() )->shouldDisplayEntryPoint( $context->getUser(), $context->getTitle() ) ) {
			$templateType = $this->getTemplateTypeForEdit( $editPage->getTitle(), $wgCityId );

			$out->addHTML( \Html::hidden( 'templateClassificationTypeCurrent', $templateType ) );
			$out->addHTML( \Html::hidden( 'templateClassificationTypeNew', '' ) );
		}

		return true;
	}

	/**
	 * Adds assets for TemplateClassification
	 *
	 * @param \OutputPage $out
	 * @param \Skin $skin
	 *
	 * @return true
	 */
	public function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {
		$title = $out->getTitle();
		if ( ( new Permissions() )->shouldDisplayEntryPoint( $skin->getUser(), $title ) ) {
			if ( $title->exists() && !$this->isEditPage() ) {
				\Wikia::addAssetsToOutput( 'template_classification_in_view_js' );
				\Wikia::addAssetsToOutput( 'template_classification_scss' );
			} elseif ( $this->isEditPage() ) {
				\Wikia::addAssetsToOutput( 'template_classification_in_edit_js' );
				\Wikia::addAssetsToOutput( 'template_classification_scss' );
			}
		}
		return true;
	}

	/**
	 * @param \PageHeaderController $pageHeaderController
	 * @param \Title $title
	 * @return bool
	 */
	public function onPageHeaderPageTypePrepared( \PageHeaderController $pageHeaderController, \Title $title ) {
		global $wgCityId;

		$user = $pageHeaderController->getContext()->getUser();
		if ( $title->inNamespace( NS_TEMPLATE ) && $title->exists() ) {
			$view = new View();
			$pageHeaderController->pageType = $view->renderTemplateType(
				$wgCityId, $title, $user, $pageHeaderController->pageType
			);
		}
		return true;
	}

	/**
	 * @param \QueryPage $queryPage
	 * @param $results
	 * @return bool
	 */
	public function onQueryPageUseResultsBeforeRecache( \QueryPage $queryPage, \DatabaseBase $db, $results ) {
		if ( $queryPage->getName() === \UnusedtemplatesPage::UNUSED_TEMPLATES_PAGE_NAME ) {
			$handler = $this->getUnusedTemplatesHandler();
			if ( $results instanceof \ResultWrapper ) {
				$handler->markAsUnusedFromResults( $results );
				$db->dataSeek( $results, 0 );	// CE-3024: reset cursor because hook caller needs the results also
			} else {
				$handler->markAllAsUsed();
			}
		}
		return true;
	}

	/**
	 * @param \EditPageLayoutController $editPage
	 * @return bool
	 */
	public function onEditPageLayoutExecute( \EditPageLayoutController $editPage ) {
		global $wgCityId;

		$user = $editPage->getContext()->getUser();
		$title = $editPage->getContext()->getTitle();
		if ( ( new Permissions() )->shouldDisplayEntryPoint( $user, $title ) ) {
			$editPage->addExtraHeaderHtml(
				( new View )->renderTemplateType( $wgCityId, $title, $user )
			);
		}
		return true;
	}

	/**
	 * @return Handler
	 */
	protected function getUnusedTemplatesHandler() {
		return new Handler();
	}

	private function isEditPage() {
		return \RequestContext::getMain()->getRequest()->getVal( 'action' ) === 'edit';
	}

	/**
	 * Retrieves template type for edit page purposes
	 * Has fallback to infobox when in template draft conversion process
	 * @param \Title $title
	 * @param int $wikiaId
	 * @return string
	 */
	private function getTemplateTypeForEdit( \Title $title, $wikiaId ) {
		global $wgEnableTemplateDraftExt;

		if ( !empty( $wgEnableTemplateDraftExt )
			&& \TemplateDraftHelper::isInfoboxDraftConversion( $title )
		) {
			return \TemplateClassificationService::TEMPLATE_INFOBOX;
		}

		try {
			$templateType = ( new \UserTemplateClassificationService() )->getType( $wikiaId, $title->getArticleID() );
		} catch ( ApiException $e ) {
			( new Logger() )->exception( $e );
			/**
			 * If the service is unreachable - fill the field with a not-available string
			 * which instructs front-end tools to skip the classification part.
			 */
			$templateType = \TemplateClassificationService::NOT_AVAILABLE;
		}

		return $templateType;
	}
}
