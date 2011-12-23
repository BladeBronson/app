<?php 
class WikiaRssHelper {
	
	/**
	 * @brief Renders a placeholder or cached feed's data
	 * @param String $input user's options
	 * 
	 * @return String
	 */
	public function renderRssPlaceholder($input) {
		$app = F::app();
		$rss = F::build('WikiaRssModel', array($input));
		
		// Kill parser cache
		//$app->wg->Parser->disableCache();
		$app->wg->ParserCacheExpireTime = 600;
		//wfDebug( "soft disable Cache (wikia rss)\n" );
		
		$html = '';
		$html .= self::getJSSnippet($rss->getRssAttributes());
		$html .= $rss->getPlaceholder();
		
		return $html;
	}
	
	/**
	 * @brief Gets JavaScript code snippet to be loaded
	 * 
	 * @param Array options passed to callback javascript function
	 */
	static private function getJSSnippet($options) {
		$html = F::build('JSSnippets')->addToStack(
			array(
//				'/extensions/wikia/WikiaRSS/css/WikiaRss.scss', //it's empty; we don't need it here...
				'/extensions/wikia/WikiaRSS/js/WikiaRss.js',
			),
			array(),
			'WikiaRss.init',
			$options,
			null
		);

		return $html;
	}
	
}