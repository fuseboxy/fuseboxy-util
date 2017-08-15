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


	function test__httpRequest(){}


	function test__postPage(){}


	function test__getPage(){}


	function test__html2text(){
		$html = file_get_contents('utility-util/hello_world.html');
		// transform and check
		$result = Util::html2text($html);
		$this->assertTrue( !empty($result) );
		// content still here
		$this->assertPattern('/Hello World/i', $result);
		// no more tags
		$this->assertNoPattern('/<html>/i', $result);
		$this->assertNoPattern('/<body>/i', $result);
		$this->assertNoPattern('/<h1>/i', $result);
	}


	function test__minifyHtml(){
		$html = file_get_contents('utility-util/hello_world.html');
		// transform and check
		$result = Util::minifyHtml($html);
		$this->assertTrue( !empty($result) );
		$this->assertTrue( strlen($result) <= strlen($html) );
		// content still here
		$this->assertPattern('/Hello World/i', $result);
		// tags still here
		$this->assertPattern('/<html>/i', $result);
		$this->assertPattern('/<body>/i', $result);
		$this->assertPattern('/<h1>/i', $result);
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