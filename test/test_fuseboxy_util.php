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
		$html = '<html><body><h1>I am a BOY</h1></body></html>';
		// transform and check
		$result = Util::html2text($html);
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/I AM A BOY/i', $result);
	}


	function test__minifyHtml(){
		$html = '<html> <body>  <b>Hello World</b>  </body> </html>';
		// transform and check
		$result = Util::minifyHtml($html);
		$this->assertTrue( !empty($result) );
		$this->assertTrue( strlen($result) <= strlen($html) );
		$this->assertNoPattern('/>  </', $result);
		$this->assertNoPattern('/> </', $result);
		$this->assertPattern('/></', $result);
		$this->assertPattern('/Hello World/i', $result);
	}


	function test__sendMail(){}


	function test__xslt(){
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<recordset>
				<row>
					<id>1</id>
					<name>unit-test</name>
					<remark>Unit Test</remark>
				</row>
				<row>
					<id>2</id>
					<name>foo-bar</name>
					<remark>Foo Bar</remark>
				</row>
			</recordset>
		';
		$xsl = '<?xml version="1.0" encoding="UTF-8"?>
			<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
			<xsl:template match="/">
			<html>
			<body>
				<ul>
					<xsl:for-each select="recordset/row">
						<li><xsl:value-of select="id" />|<xsl:value-of select="name" />|<xsl:value-of select="remark" /></li>
					</xsl:for-each>
				</ul>
			</body>
			</html>
			</xsl:template>
			</xsl:stylesheet>
		';
		// transform and check
		$result = Util::xslt($xml, $xsl);
		$this->assertTrue( !empty($result) );
		$this->assertPattern('/1|unit-test|Unit Test/', $result);
		$this->assertPattern('/2|foo-bar|Foo Bar/', $result);
	}


} // TestFuseboxyUtil