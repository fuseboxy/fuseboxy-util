<?php
class TestFuseboxyUtil extends UnitTestCase {


	function __construct(){
		if ( !class_exists('Framework') ) {
			include __DIR__.'/utility-util/framework/1.0.3/fuseboxy.php';
			Framework::$mode = Framework::FUSEBOX_UNIT_TEST;
			Framework::$configPath = __DIR__.'/utility-util/config/fusebox_config.php';
		}
		// load component
		if ( !class_exists('Util') ) {
			include dirname(__DIR__).'/app/model/Util.php';
		}
		// load config
		Framework::createAPIObject();
		Framework::loadConfig();
	}


	function test__crypt(){}


	function test__decrypt(){}


	function test__encrypt(){}


	function test__httpRequest(){
		$host = ( $_SERVER['HTTP_HOST'] == 'localhost' ) ? '127.0.0.1' : $_SERVER['HTTP_HOST'];
		$baseUrl = 'http://'.$host.dirname($_SERVER['SCRIPT_NAME']);
		// get
		$result = Util::httpRequest('GET', $baseUrl.'/utility-util/unit_test.php');
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/METHOD:GET/i', $result);
		// post
		$result = Util::httpRequest('POST', $baseUrl.'/utility-util/unit_test.php');
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/METHOD:POST/i', $result);
		// put
		$result = Util::httpRequest('PUT', $baseUrl.'/utility-util/unit_test.php');
		$this->assertTrue( !empty($result) );
		$this->assertTrue('/METHOD:PUT/i', $result);
		// delete
		$result = Util::httpRequest('DELETE', $baseUrl.'/utility-util/unit_test.php');
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/METHOD:DELETE/i', $result);
		// invalid (non-REST)
		$result = Util::httpRequest('FOOBAR', $baseUrl.'/utility-util/unit_test.php');
		$this->assertFalse( $result );
		$this->assertPattern('/invalid method/i', Util::error());
	}


	function test__postPage(){
		$host = ( $_SERVER['HTTP_HOST'] == 'localhost' ) ? '127.0.0.1' : $_SERVER['HTTP_HOST'];
		$baseUrl = 'http://'.$host.dirname($_SERVER['SCRIPT_NAME']);
		// post valid page
		$result = Util::postPage($baseUrl.'/utility-util/unit_test.php', array('title' => 'Unit Test', 'content' => 'foobar'));
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/Unit Test/i', $result);
		$this->assertPattern('/foobar/i', $result);
		$this->assertPattern('/METHOD:POST/i', $result);
		// post page without param
		$result = Util::postPage($baseUrl.'/utility-util/unit_test.php');
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/\(no title\)/i', $result);
		$this->assertPattern('/\(no content\)/i', $result);
		$this->assertPattern('/METHOD:POST/i', $result);
		// post invalid page
		$result = Util::postPage('http://this/is/invalid/url.php');
		$this->assertFalse( $result );
	}


	function test__getPage(){
		$host = ( $_SERVER['HTTP_HOST'] == 'localhost' ) ? '127.0.0.1' : $_SERVER['HTTP_HOST'];
		$baseUrl = 'http://'.$host.dirname($_SERVER['SCRIPT_NAME']);
		// get valid page
		$result = Util::getPage($baseUrl.'/utility-util/unit_test.html');
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/Unit Test/i', $result);
		$this->assertPattern('/Hello World/i', $result);
		$this->assertPattern('/<html>/i', $result);
		$this->assertPattern('/<body>/i', $result);
		$this->assertPattern('/<h1>/i', $result);
		$this->assertPattern('/<hr>/i', $result);
		// get page with param
		$result = Util::getPage($baseUrl.'/utility-util/unit_test.php?title=ABC&content=xyz');
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/ABC/i', $result);
		$this->assertPattern('/xyz/i', $result);
		$this->assertPattern('/METHOD:GET/i', $result);
		// get invalid page
		$result = Util::getPage('http://this/is/invalid/url.html');
		$this->assertFalse( $result );
	}


	function test__html2text(){
		$html = file_get_contents('utility-util/unit_test.html');
		// transform and check
		$result = Util::html2text($html);
		$this->assertTrue( !empty($result) );
		// content still here
		$this->assertPattern('/Unit Test/i', $result);
		$this->assertPattern('/Hello World/i', $result);
		// no more tags
		$this->assertNoPattern('/<html>/i', $result);
		$this->assertNoPattern('/<body>/i', $result);
		$this->assertNoPattern('/<h1>/i', $result);
		$this->assertNoPattern('/<hr>/i', $result);
	}


	function test__minifyHtml(){
		$html = file_get_contents('utility-util/unit_test.html');
		// transform and check
		$result = Util::minifyHtml($html);
		$this->assertTrue( !empty($result) );
		$this->assertTrue( strlen($result) <= strlen($html) );
		// content still here
		$this->assertPattern('/Unit Test/i', $result);
		$this->assertPattern('/Hello World/i', $result);
		// tags still here
		$this->assertPattern('/<html>/i', $result);
		$this->assertPattern('/<body>/i', $result);
		$this->assertPattern('/<h1>/i', $result);
		$this->assertPattern('/<hr>/i', $result);
		// no more space between tags
		$this->assertPattern('/></', $result);
		$this->assertNoPattern('/>  </', $result);
		$this->assertNoPattern('/> </', $result);
		$this->assertNoPattern("/>\n</", $result);
	}


	function test__sendMail(){}


	function test__xslt(){
		$xml = file_get_contents('utility-util/unit_test.xml');
		$xsl = file_get_contents('utility-util/unit_test.xsl');
		// transform and check
		$result = Util::xslt($xml, $xsl);
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/1|unit-test|Unit Test/', $result);
		$this->assertPattern('/2|foo-bar|Foo Bar/', $result);
	}


} // TestFuseboxyUtil