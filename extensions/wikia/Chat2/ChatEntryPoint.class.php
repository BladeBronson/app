<?php
/**
 * @author Jacek "mech" Wozniak <mech@wikia-inc.com>
 *
 * ChatEntryPoint object implementation
 */
class ChatEntryPoint {

	/**
	 * @brief This function set parseTag hook
	 */
	static public function onParserFirstCallInit( Parser &$parser ) {
		wfProfileIn( __METHOD__ );
		$parser->setHook( CHAT_TAG, array( __CLASS__, "parseTag" ) );
		wfProfileOut( __METHOD__ );
		return true;
	}


	static public function getEntryPointTemplateVars( $isEntryPoint ) {
		global $wgEnableWallExt, $wgBlankImgUrl;
		return [
			'linkToSpecialChat' => SpecialPage::getTitleFor("Chat")->escapeLocalUrl(),
			'isEntryPoint' => $isEntryPoint,
			'blankImgUrl' => $wgBlankImgUrl,
			'profileType' => !empty($wgEnableWallExt) ? 'message-wall' : 'talk-page'
		];
	}

	/**
	 * Chat tag parser implementation
	 */
	static public function parseTag( $input, $args, $parser ) {
		wfProfileIn( __METHOD__ );

		$template = new EasyTemplate( dirname( __FILE__ ) . "/templates/" );
		$template->set_vars( self::getEntryPointTemplateVars( true ) );

		if ( F::app()->checkSkin( 'oasis' ) ) {
			$html = $template->render( 'entryPointTag' );
		} else {
			$html = $template->render( 'entryPointTagMonobook' );
		}

		// remove newlines so parser does not try to wrap lines into paragraphs
		$html = str_replace( "\n", "", $html );

		wfProfileOut( __METHOD__ );
		return '<nowiki>'.$html.'</nowiki>';
	}

	static private function getChatUsersMemcKey() {
		return wfMemcKey( 'chatusersinfo' );
	}

	static public function purgeChatUsersCache() {
		WikiaDataAccess::cachePurge( self::getChatUsersMemcKey() );
	}

	/**
	 * Return information about users present in the chat channel. This method has its internal cache. Method returns
	 * an array, where each of the users is described by the following attributes:
	 * * username - chatter login
	 * * avatarUrl - chatter avatar url
	 * * editCount - number of chatter's edits
	 * * showSince - flag indicating if we can display the information when the chatter joined the wiki
	 * * since_year && since_month - month and year, when chatter joined this wiki
	 * * profileUrl - link to chatter talk page (or message wall, if it's enabled)
	 * * contribsUrl - link to chatter contribution page
	 * @return array array containing chatters info
	 */
	static public function getChatUsersInfo() {
		global $wgReadOnly;
		wfProfileIn( __METHOD__ );
		$chatters = [];
		if( empty( $wgReadOnly ) ) {
			$chatters = WikiaDataAccess::cache( self::getChatUsersMemcKey(), 60, function() {
				global $wgEnableWallExt;
				$chatters = [];
				// Gets array of users currently in chat to populate rail module and user stats menus
				$chattersIn = NodeApiClient::getChatters();
				foreach( $chattersIn as $i => $val ) {
					$chatters[ $i ] = WikiaDataAccess::cache( wfMemcKey( 'chatavatars', $val, 'v2' ), 60 * 60, function() use ( $wgEnableWallExt, $val ) {
						$chatter = [ 'username' => $val,
							'avatarUrl' => AvatarService::getAvatarUrl( $val, ChatRailController::AVATAR_SIZE)
						];

						// get stats for edit count and member since
						$user = User::newFromName( $val );
						if( is_object( $user ) ) {
							$userStatsService = new UserStatsService( $user->getId() );
							$stats = $userStatsService->getStats();

							// edit count
							$chatter[ 'editCount' ] = $stats[ 'edits' ];

							// member since
							$chatter[ 'showSince' ] = $chatter[ 'editCount' ] != 0;
							if ( $chatter[ 'showSince' ] ) {
								$date = getdate( strtotime( $stats[ 'date' ] ) );
								$chatter[ 'since_year' ] = $date[ 'year' ];
								$chatter[ 'since_month' ] =  $date[ 'mon' ];
							}

							if ( !empty( $wgEnableWallExt ) ) {
								$chatter[ 'profileUrl' ] = Title::makeTitle( NS_USER_WALL, $chatter[ 'username' ] )->getFullURL();
							} else {
								$chatter[ 'profileUrl' ] = Title::makeTitle( NS_USER_TALK, $chatter[ 'username' ] )->getFullURL();
							}

							$chatter[ 'contribsUrl' ] = SpecialPage::getTitleFor( 'Contributions', $chatter[ 'username' ] )->getFullURL();
						}
						return $chatter;
					});
				}
				return $chatters;
			});
		}
		wfProfileOut( __METHOD__ );
		return $chatters;
	}

}
