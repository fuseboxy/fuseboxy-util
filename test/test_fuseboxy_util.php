<?php
class TestFuseboxyUtil extends UnitTestCase {


	function __construct(){
		if ( !class_exists('Framework') ) {
			include __DIR__.'/utility-util/framework/1.0.3/fuseboxy.php';
			Framework::$mode = Framework::FUSEBOX_UNIT_TEST;
			Framework::$configPath = __DIR__.'/utility-util/config/fusebox_config.php';
		}
		if ( !class_exists('F') ) {
			include __DIR__.'/utility-util/framework/1.0.3/F.php';
		}
		if ( !class_exists('Util') ) {
			include dirname(__DIR__).'/app/model/Util.php';
		}
	}


	function test__decrypt(){}


	function test__encrypt(){}


	function test__html2text(){}


	function test__minifyHtml(){}


	function test__sendMail(){}


	function test__xslt(){}


} // TestFuseboxyUtil