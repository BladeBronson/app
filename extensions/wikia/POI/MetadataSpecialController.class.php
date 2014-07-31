<?php

class MetadataSpecialController extends WikiaSpecialPageController {
	const SPECIAL_NAME = 'Metadata';
	const CACHE_TIME = 3600;


	protected $model;

	/**
	 * @var Title
	 */
	protected $currentTitle;

	/**
	 * @param \HubRssFeedModel $model
	 */
	public function setModel( $model ) {
		$this->model = $model;
	}

	/**
	 * @return \HubRssFeedModel
	 */
	public function getModel() {
		return $this->model;
	}


	public function __construct() {
		parent::__construct( self::SPECIAL_NAME, self::SPECIAL_NAME, false );
		$this->currentTitle = SpecialPage::getTitleFor( self::SPECIAL_NAME );
	}


	public function index() {
		$url = $this->currentTitle->getFullUrl();
		$links = [ ];

		$this->setVal( 'links', '' );
		$this->wg->SupressPageSubtitle = true;

	}

/*
	public function index() {

		$hubName = (string)$this->request->getVal( 'par' );

		$ref = (string)$this->request->getVal( 'ref' );

		$model = BaseRssModel::newFromName( $hubName );
		if(!$model instanceof BaseRssModel){
			return $this->forward( 'MetadataSpecialController', 'notfound' );
		}
		$this->response->setCacheValidity( self::CACHE_TIME );

		$service = new RssFeedService();
		$service->setRef( $ref );

		$service->setFeedLang( $model->getFeedLanguage() );
		$service->setFeedTitle( $model->getFeedTitle() );
		$service->setFeedDescription( $model->getFeedDescription() );
		$service->setFeedUrl( RequestContext::getMain()->getRequest()->getFullRequestURL() );
		$service->setData( $model->getFeedData() );
		$this->response->setFormat( WikiaResponse::FORMAT_RAW );
		$this->response->setBody( $service->toXml() );
		$this->response->setContentType( self::RSS_CONTENT_TYPE );
	}*/

}