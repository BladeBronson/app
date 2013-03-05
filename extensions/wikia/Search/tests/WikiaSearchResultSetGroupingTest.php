<?php

require_once( 'WikiaSearchBaseTest.php' );

class WikiaSearchResultSetGroupingGroupingTest extends WikiaSearchBaseTest
{
	
	/**
	 * Convenience method to easily handle the necessary dependencies & method mocking for recurrent mocks
	 * @param array $resultSetMethods
	 * @param array $configMethods
	 * @param array $resultMethods
	 */
	protected function prepareMocks( $resultSetMethods = array(), $configMethods = array(), $resultMethods = array(), $interfaceMethods = array() ) { 
	
		$this->searchResult		=	$this->getMockBuilder( 'Solarium_Result_Select' )
									->disableOriginalConstructor()
									->setMethods( $resultMethods )
									->getMock();
		
		$this->config		=	$this->getMockBuilder( 'WikiaSearchConfig' )
									->disableOriginalConstructor()
									->setMethods( $configMethods )
									->getMock();
		
		$this->resultSet	=	$this->getMockBuilder( '\Wikia\Search\ResultSet\Grouping' )
									->disableOriginalConstructor()
									->setMethods( $resultSetMethods )
									->getMock();
		
		$this->interface = $this->getMockbuilder( 'Wikia\Search\MediaWikiInterface' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( $interfaceMethods )
		                        ->getMock();
		
		$reflResult = new ReflectionProperty( '\Wikia\Search\ResultSet\Base', 'searchResultObject' );
		$reflResult->setAccessible( true );
		$reflResult->setValue( $this->resultSet, $this->searchResult );
		
		$reflConfig = new ReflectionProperty(  '\Wikia\Search\ResultSet\Base', 'searchConfig' );
		$reflConfig->setAccessible( true );
		$reflConfig->setValue( $this->resultSet, $this->config );
		
		$reflConfig = new ReflectionProperty(  '\Wikia\Search\ResultSet\Base', 'interface' );
		$reflConfig->setAccessible( true );
		$reflConfig->setValue( $this->resultSet, $this->interface );
	}

	/**
	 * @covers Wikia\Search\ResultSet\Grouping::getHostGrouping
	 */
	public function testGetHostGroupingWithoutGrouping() {
		$this->prepareMocks( array(), array(), array( 'getGrouping' ) );
		
		$this->searchResult
			->expects	( $this->at( 0 ) )
			->method	( 'getGrouping' )
			->will		( $this->returnValue( null ) )
		;
		
		$method = new ReflectionMethod( 'Wikia\Search\ResultSet\Grouping', 'getHostGrouping' );
		$method->setAccessible( true );
		
		try {
			$method->invoke( $this->resultSet );
		} catch ( Exception $e ) { }
		
		$this->assertInstanceOf( 
				'Exception', 
				$e,
				'Wikia\Search\ResultSet\Grouping::getHostGrouping should throw an exception if called in a situation where we are not grouping results'
		);
	}
	
	/**
	 * @covers Wikia\Search\ResultSet\Grouping::getHostGrouping
	 */
	public function testGetHostGroupingWithoutHostGrouping() {
		$this->prepareMocks( array(), array(), array( 'getGrouping' ) );
		
		$mockGrouping = $this->getMockBuilder( 'Solarium_Result_Select_Grouping' )
							->disableOriginalConstructor()
							->setMethods( array( 'getGroup' ) )
							->getMock();
		
		$this->searchResult
			->expects	( $this->at( 0 ) )
			->method	( 'getGrouping' )
			->will		( $this->returnValue( $mockGrouping ) )
		;
		$mockGrouping
			->expects	( $this->at( 0 ) )
			->method	( 'getGroup' )
			->with		( 'host' )
			->will		( $this->returnValue( null ) )
		;
		
		$method = new ReflectionMethod( 'Wikia\Search\ResultSet\Grouping', 'getHostGrouping' );
		$method->setAccessible( true );
		
		try {
			$method->invoke( $this->resultSet );
		} catch ( Exception $e ) { }
		
		$this->assertInstanceOf( 
				'Exception', 
				$e,
				'Wikia\Search\ResultSet\Grouping::getHostGrouping should throw an exception if called in a situation where we are not grouping results by host'
		);
	}
	
	/**
	 * @covers Wikia\Search\ResultSet\Grouping::getHostGrouping
	 */
	public function testGetHostGroupingWorks() {
		
		$this->prepareMocks( array(), array(), array( 'getGrouping' ) );
		
		$mockGrouping = $this->getMockBuilder( 'Solarium_Result_Select_Grouping' )
							->disableOriginalConstructor()
							->setMethods( array( 'getGroup' ) )
							->getMock();
		
		$mockFieldGroup = $this->getMockBuilder( 'Solarium_Result_Select_Grouping_FieldGroup' )
							->disableOriginalConstructor()
							->setMethods( array( 'getValueGroups' ) )
							->getMock();
		
		$this->searchResult
			->expects	( $this->at( 0 ) )
			->method	( 'getGrouping' )
			->will		( $this->returnValue( $mockGrouping ) )
		;
		$mockGrouping
			->expects	( $this->at( 0 ) )
			->method	( 'getGroup' )
			->with		( 'host' )
			->will		( $this->returnValue( $mockFieldGroup ) )
		;
		
		$method = new ReflectionMethod( 'Wikia\Search\ResultSet\Grouping', 'getHostGrouping' );
		$method->setAccessible( true );
		
		$this->assertEquals(
				$mockFieldGroup,
				$method->invoke( $this->resultSet ),
				'Wikia\Search\ResultSet\Grouping::getHostGrouping should return an instance of Solarium_Result_Select_Grouping_FieldGroup'
		);
	}
	
	public function testConfigure() {
		$dcMethods = array( 'getResult', 'getConfig', 'getInterface', 'getParent', 'getMetaposition' );
		$dc = $this->getMockBuilder( 'Wikia\Search\ResultSet\DependencyContainer' )
		           ->disableOriginalConstructor()
		           ->setMethods( $dcMethods )
		           ->getMock();
		
		$mockGrouping = $this->getMockBuilder( 'Wikia\Search\ResultSet\Grouping' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'setResultsFromHostGrouping', 'configureHeaders' ) )
		                     ->getMock();
		foreach ( $dcMethods as $method ) {
			$dc
			    ->expects( $this->once() )
			    ->method ( $method )
			;
		}
		$mockGrouping
		    ->expects( $this->once() )
		    ->method ( 'setResultsFromHostGrouping' )
		    ->will   ( $this->returnValue( $mockGrouping ) )
		;
		$mockGrouping
		    ->expects( $this->once() )
		    ->method ( 'configureHeaders' )
		    ->will   ( $this->returnValue( $mockGrouping ) )
		;
		$configure = new ReflectionMethod( 'Wikia\Search\ResultSet\Grouping', 'configure' );
		$configure->setAccessible( true );
		$configure->invoke( $mockGrouping, $dc );
	}
	
	
	
	/**
	 * @covers Wikia\Search\ResultSet\Grouping::setResultsFromHostGrouping
	 */
	public function testSetResultsFromHostGrouping() {
		$mockFieldGroup = $this->getMockBuilder( 'Solarium_Result_Select_Grouping_FieldGroup' )
							->disableOriginalConstructor()
							->setMethods( array( 'getValueGroups' ) )
							->getMock();
		
		$mockGrouping = $this->getMockBuilder( 'Wikia\Search\ResultSet\Grouping' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'getHostGrouping', 'setResults' ) )
		                     ->getMock();
		
		$mockValueGroup = $this->getMockBuilder( 'Solarium_Result_Select_Grouping_ValueGroup' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'getValue', 'getNumFound', 'getDocuments' ) )
		                       ->getMock();
		
		$metapos = new ReflectionProperty( 'Wikia\Search\ResultSet\Grouping', 'metaposition' );
		$metapos->setAccessible( true );
		$metapos->setValue( $mockGrouping, 0 );
		
		$mockGrouping
		    ->expects( $this->once() )
		    ->method ( 'getHostGrouping' )
		    ->will   ( $this->returnValue( $mockFieldGroup ) )
		;
		$mockFieldGroup
		    ->expects( $this->once() )
		    ->method ( 'getValueGroups' )
		    ->will   ( $this->returnValue( array( $mockValueGroup ) ) )
		;
		$mockValueGroup
		    ->expects( $this->once() )
		    ->method ( 'getValue' )
		    ->will   ( $this->returnValue( 'foo.wikia.com' ) )
		;
		$mockValueGroup
		    ->expects( $this->once() )
		    ->method ( 'getNumFound' )
		    ->will   ( $this->returnValue( 20 ) )
		;
		$mockValueGroup
		    ->expects( $this->once() )
		    ->method ( 'getDocuments' )
		    ->will   ( $this->returnValue( array( 'doc' ) ) )
		;
		$mockGrouping
		    ->expects( $this->once() )
		    ->method ( 'setResults' )
		    ->with   ( array( 'doc' ) )
		;
		
		$set = new ReflectionMethod( 'Wikia\Search\ResultSet\Grouping', 'setResultsFromHostGrouping' );
		$set->setAccessible( true );
		$this->assertEquals(
				$mockGrouping,
				$set->invoke( $mockGrouping )
		);
		$host = new ReflectionProperty( 'Wikia\Search\ResultSet\Grouping', 'host' );
		$host->setAccessible( true );
		$this->assertEquals(
				'foo.wikia.com',
				$host->getValue( $mockGrouping )
		);
		$found = new ReflectionProperty( 'Wikia\Search\ResultSet\Grouping', 'resultsFound' );
		$found->setAccessible( true );
		$this->assertEquals(
				20,
				$found->getValue( $mockGrouping )
		);
	}
	
	/**
	 * @covers Wikia\Search\ResultSet::configureHeaders
	 */
	public function testConfigureHeaders() {
		$mockResult = $this->getMock( 'Wikia\Search\Result', array( 'offsetGet', 'getFields' ) );
		$results = new \ArrayIterator( array( $mockResult ) );
		$this->prepareMocks( array( 'addHeaders', 'setHeader' ), array(), array(), array( 'getStatsInfoForWikiId', 'getVisualizationInfoForWikiId', 'getGlobalForWiki' ) );
		$fields = array( 'id' => 123 );
		$vizInfo = array( 'description' => 'yup' );
		$mockResult
		    ->expects( $this->at( 0 ) )
		    ->method ( 'offsetGet' )
		    ->with   ( 'wid' )
		    ->will   ( $this->returnValue( 123 ) )
		;
		$resultsRefl = new ReflectionProperty( 'Wikia\Search\ResultSet\Grouping', 'results' );
		$resultsRefl->setAccessible( true );
		$resultsRefl->setValue( $this->resultSet, $results );
		$this->interface
		    ->expects( $this->at( 0 ) )
		    ->method ( 'getStatsInfoForWikiId' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( array( 'users' => 100 ) ) )
		;
		$this->interface
		    ->expects( $this->at( 1 ) )
		    ->method ( 'getVisualizationInfoForWikiId' )
		    ->with   ( 123 )
		    ->will   ( $this->returnValue( $vizInfo ) )
		;
		$mockResult
		    ->expects( $this->once() )
		    ->method ( 'getFields' )
		    ->will   ( $this->returnValue( $fields ) )
		;
		$this->resultSet
		    ->expects( $this->at( 0 ) )
		    ->method ( 'addHeaders' )
		    ->with   ( $fields )
		    ->will   ( $this->returnValue( $this->resultSet ) )
		;
		$this->resultSet
		    ->expects( $this->at( 1 ) )
		    ->method ( 'addHeaders' )
		    ->with   ( $vizInfo )
		    ->will   ( $this->returnValue( $this->resultSet ) )
		;
		$this->resultSet
		    ->expects( $this->at( 2 ) )
		    ->method ( 'addHeaders' )
		    ->with   ( array( 'users_count' => 100 ) )
		    ->will   ( $this->returnValue( $this->resultSet ) )
		;
		$this->interface
		    ->expects( $this->any() )
		    ->method ( 'getGlobalForWiki' )
		    ->with   ( 'wgSitename', 123 )
		    ->will   ( $this->returnValue( "my title" ) )
		;
		$this->resultSet
		    ->expects( $this->once() )
		    ->method ( 'setHeader' )
		    ->with   ( "wikititle", "my title" )
		    ->will   ( $this->returnValue( $this->resultSet ) )
		;
		$conf = new ReflectionMethod( 'Wikia\Search\ResultSet\Grouping', 'configureHeaders' );
		$conf->setAccessible( true );
		$this->assertEquals(
				$this->resultSet,
				$conf->invoke( $this->resultSet )
		);
	}
}