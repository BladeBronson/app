<?php

class YoutubeApiWrapper extends ApiWrapper {

//	protected static $API_URL = 'http://gdata.youtube.com/feeds/api/videos/$1';
	protected static $API_URL = 'https://www.googleapis.com/youtube/v3/videos';
	protected static $CACHE_KEY = 'youtubeapi';
	protected static $aspectRatio = 1.7777778;

	public static function isMatchingHostname( $hostname ) {
		return endsWith($hostname, "youtube.com")
			|| endsWith($hostname, "youtu.be" ) ? true : false;
	}

	public static function newFromUrl( $url ) {

		wfProfileIn( __METHOD__ );

		$aData = array();

		$id = '';
		$parsedUrl = parse_url( $url );
		if ( !empty( $parsedUrl['query'] ) ){
			parse_str( $parsedUrl['query'], $aData );
		};
		if ( isset( $aData['v'] ) ){
			$id = $aData['v'];
		}

		if ( empty( $id ) ){
			$parsedUrl = parse_url( $url );

			$aExploded = explode( '/', $parsedUrl['path'] );
			$id = array_pop( $aExploded );
		}

		if ( false !== strpos( $id, "&" ) ){
			$parsedId = explode("&",$id);
			$id = $parsedId[0];
		}

		if ( $id ) {
			wfProfileOut( __METHOD__ );
			return new static( $id );
		}

		wfProfileOut( __METHOD__ );
		return null;
	}

	public function getDescription() {

		wfProfileIn( __METHOD__ );

		$text = '';
		if ( $this->getVideoCategory() ) $text .= 'Category: ' . $this->getVideoCategory();
		if ( $this->getVideoKeywords() ) $text .= "\n\nKeywords: {$this->getVideoKeywords()}";

		wfProfileOut( __METHOD__ );

		return $text;
	}

	public function getThumbnailUrl() {

		wfProfileIn( __METHOD__ );

		$thumbnailDatas = $this->getVideoThumbnails();
		foreach ( $thumbnailDatas as $quality => $thumbnailData ) {
			switch ( $quality ) {
				case 'high':
					if ( !empty( $thumbnailData['url'] ) ) {
						wfProfileOut( __METHOD__ );
						return $thumbnailData['url'];
					}
					break;
				case 'medium':
					if ( !empty( $thumbnailData['url'] ) ) {
						wfProfileOut( __METHOD__ );
						return $thumbnailData['url'];
					}
					break;
				case 'default':
					if ( !empty( $thumbnailData['url'] ) ) {
						wfProfileOut( __METHOD__ );
						return $thumbnailData['url'];
					}
					break;
				default: {
					wfProfileOut( __METHOD__ );
					return '';
				}
			}
		}
		return '';
	}

	/**
	 * returns array of thumbnail data. Thumbnails taken from different
	 * points of video. Elements: time, height, width, url
	 * @return array
	 */
	protected function getVideoThumbnails() {
		if ( !empty($this->interfaceObj['items'][0]['snippet']['thumbnails']) ) {

			return $this->interfaceObj['items'][0]['snippet']['thumbnails'];
		}

		return array();
	}

	/**
	 * Title
	 * @return string
	 */
	protected function getVideoTitle() {
		if ( !empty($this->interfaceObj['items'][0]['snippet']['title']) ) {
			return $this->interfaceObj['items'][0]['snippet']['title'];
		}

		return '';
	}

	/**
	 * User-defined description
	 * @return string
	 */
	protected function getOriginalDescription() {
		if ( !empty($this->interfaceObj['items'][0]['snippet']['description']) ) {

			return $this->interfaceObj['items'][0]['snippet']['description'];
		}

		return '';
	}

	/**
	 * User-defined keywords
	 * @TODO find a way to get keywords for video
	 * @return array
	 */
	protected function getVideoKeywords() {
		if ( !empty($this->interfaceObj['entry']['media$group']['media$keywords']['$t']) ) {

			return $this->interfaceObj['entry']['media$group']['media$keywords']['$t'];
		}

		return '';
	}

	/**
	 * YouTube category
	 * @TODO fire another request to Youtube API to get category data
	 * @return string
	 */
	protected function getVideoCategory() {
		if ( !empty($this->interfaceObj['entry']['media$group']['media$category'][0]['$t']) ) {

			return $this->interfaceObj['entry']['media$group']['media$category'][0]['$t'];
		}

		return '';
	}

	/**
	 * Time that this feed entry was created, in UTC
	 * @return string
	 */
	protected function getVideoPublished() {
		if ( !empty($this->interfaceObj['items'][0]['snippet']['publishedAt']) ) {

			return strtotime($this->interfaceObj['items'][0]['snippet']['publishedAt']);
		}

		return '';
	}

	/**
	 * Video duration, in seconds
	 * @return int
	 */
	protected function getVideoDuration() {
		if ( !empty($this->interfaceObj['items'][0]['contentDetails']['duration']) ) {
			$dateInterval = new DateInterval($this->interfaceObj['items'][0]['contentDetails']['duration']);
			$seconds = (int) $dateInterval->format('%s');
			$minutes = (int) $dateInterval->format('%i');
			$hours = (int) $dateInterval->format('%h');
			$durationInSeconds = $seconds + (60 * $minutes) + (60 * 60 * $hours);

			return $durationInSeconds;
		}

		return '';
	}

	/**
	 * Is resolution of 720 or higher available
	 * @TODO find a way to ask API if HD video is accessible
	 * @return boolean
	 */
	protected function isHdAvailable() {
		return false;
	}

	/**
	 * Can video be embedded
	 * Youtube video can always be embedded because we ask for embeddable ones via API
	 * @return boolean
	 */
	protected function canEmbed() {
		return true;
	}

	protected function sanitizeVideoId( $videoId ) {
		if ( ($pos = strpos( $videoId, '?' )) !== false ) {
			$videoId = substr( $videoId, 0, $pos );
		}
		if ( ($pos = strpos( $videoId, '&' )) !== false ) {
			$videoId = substr( $videoId, 0, $pos );
		}
		return $videoId;
	}

	/**
	 * Handle response errors
	 * @param $status - The response status object
	 * @param $content - XML content from the provider
	 * @param $apiUrl - The URL for the providers API
	 * @throws VideoNotFoundException - Video cannot be found
	 * @throws VideoIsPrivateException - Video is private and cannot be viewed
	 * @throws VideoQuotaExceededException - The quota for video owner has been exceeded
	 */
	protected function checkForResponseErrors( $status, $content, $apiUrl ) {

		wfProfileIn( __METHOD__ );

		// check if still exists
		$code = empty( $status->errors[0]['params'][0] ) ? null : $status->errors[0]['params'][0];

		if ( $code == 404 ) {
			wfProfileOut( __METHOD__ );
			throw new VideoNotFoundException($status, $content, $apiUrl);
		}

		// interpret error XML response
		$sp = new SimplePie();
		$sp->set_raw_data( $content );
		$sp->init();

		// check if private
		$googleShemas ='http://schemas.google.com/g/2005';
		if ( isset( $sp->data['child'][$googleShemas] ) ) {
			$err = $sp->data['child'][$googleShemas]['errors'][0]['child'][$googleShemas]['error'][0]['child'][$googleShemas]['internalReason'][0]['data'];
			if( $err == 'Private video' ) {
				wfProfileOut( __METHOD__ );
				throw new VideoIsPrivateException( $status, $content, $apiUrl );
			}
		}

		// check if quota exceeded
		if ( isset( $sp->data['child'][''] ) ) {
			$err = $sp->data['child']['']['errors'][0]['child']['']['error'][0]['child']['']['code'][0]['data'];
			if( $err == 'too_many_recent_calls' ) {
				wfProfileOut( __METHOD__ );
				throw new VideoQuotaExceededException( $status, $content, $apiUrl );
			}
		}

		wfProfileOut( __METHOD__ );

		// return default
		parent::checkForResponseErrors($status, $content, $apiUrl);
	}

	/**
	 * Get url for API.
	 * More information: https://developers.google.com/youtube/2.0/developers_guide_protocol
	 * @return string
	 */
	protected function getApiUrl() {
		global $wgYoutubeConfig;


		$params = [
			'part' => 'snippet,contentDetails',
			'id' => $this->videoId,
			'maxResults' => '1',
			'videoEmbeddable' => true,
			'type' => 'video',
			'key' => $wgYoutubeConfig['DeveloperKeyApiV3']
		];

		$url = self::$API_URL . '?' . http_build_query( $params );
		return $url;
	}

}
