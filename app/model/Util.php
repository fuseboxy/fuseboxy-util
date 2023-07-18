<?php
class Util {


	// property : library for corresponding methods
	private static $libPath = array(
		'array2pdf' => 'Util_PDF',
		'array2xls' => 'Util_XLS',
		'html2md' => array(
			__DIR__.'/../../lib/markdownify/2.3.1/src/Parser.php',
			__DIR__.'/../../lib/markdownify/2.3.1/src/Converter.php',
			__DIR__.'/../../lib/markdownify/2.3.1/src/ConverterExtra.php',
		),
		'html2pdf' => 'Util_PDF',
		'mail' => array(
			__DIR__.'/../../lib/phpmailer/6.1.6/src/PHPMailer.php',
			__DIR__.'/../../lib/phpmailer/6.1.6/src/Exception.php',
			__DIR__.'/../../lib/phpmailer/6.1.6/src/SMTP.php',
			__DIR__.'/../../lib/phpmailer/6.1.6/src/OAuth.php',
		),
		'md2html' => __DIR__.'/../../lib/parsedown/1.7.4/Parsedown.php',
		'phpQuery' => __DIR__.'/../../lib/phpquery/2.0.1/phpQuery.php',
		'xls2array' => 'Util_XLS',
	);




	// get (latest) error message
	private static $error;
	public static function error() { return self::$error; }




	/**
	<fusedoc>
		<description>
			generate PDF file with provided data
		</description>
		<io>
			<in>
				<array name="$fileData">
					<structure name="+">
						<string name="type" default="div" value="div|p|h1|h2|h3|h4|h5|h6|small|ol|ul|br|hr|img|pagebreak" />
						<!-- value -->
						<string name="value" oncondition="div|p|h1..h6|small" />
						<array name="value" oncondition="ol|ul">
							<string name="+" />
						</array>
						<string name="src" oncondition="img" />
						<!-- styling -->
						<boolean name="bold" default="false" />
						<boolean name="underline" default="false" />
						<boolean name="italic" default="false" />
						<string name="color|fontColor" value="ffccaa|#ffccaa|.." />
						<number name="size|fontSize" optional="yes" oncondition="div|p|ul|ol|br" />
						<!-- alignment -->
						<string name="align" value="left|right|center|justify" oncondition="div|p|h1..h6|small|img" />
						<!-- options -->
						<number name="repeat" optional="yes" default="1" oncondition="br" />
						<number name="height" optional="yes" oncondition="img" />
						<number name="width" optional="yes" oncondition="img" />
						<number name="indent" optional="yes" oncondition="ol|ul" />
						<string name="url" optional="yes" />
					</structure>
				</array>
				<string name="$filePath" optional="yes" default="~null~" comments="relative path to upload directory; use {false} or {null} to display PDF directly" />
				<structure name="$pageOptions" optional="yes">
					<string name="paperSize" default="A4" value="A3|A4|A5|~array(width,height)~">
						[A3] 297 x 420
						[A4] 210 x 297
						[A5] 148 x 210
					</string>
					<string name="orientation" default="P" value="P|L" />
					<string name="fontFamily" default="" />
					<number name="fontSize" default="12" />
					<number name="marginTop|marginLeft|marginRight|marginBottom" default="10" comments="1cm" />
				</structure>
			</in>
			<out>
				<!-- file output -->
				<file name="~uploadDir~/~filePath~" optional="yes" oncondition="when {filePath} specified" />
				<!-- return value -->
				<structure name="~return~" optional="yes" oncondition="when {filePath} specified">
					<string name="path" />
					<string name="url" />
				</structure>
			</out>
		</io>
	</fusedoc>
	*/
	public static function array2pdf($fileData, $filePath=null, $pageOptions=[]) {
		// validate library
		$libClass = self::$libPath['array2pdf'];
		if ( !class_exists($libClass) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Library is missing ('.$libClass.')';
			return false;
		}
		// proceed to transform
		$result = Util_PDF::array2pdf($fileData, $filePath, $pageOptions);
		if ( $result === false ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] '.Util_PDF::error();
			return false;
		}
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			export data into excel file (in xlsx format) & save into upload directory
		</description>
		<io>
			<in>
				<!-- parameters -->
				<structure name="$fileData">
					<array name="~worksheetName~">
						<structure name="+" comments="row">
							<string name="~columnName~" />
						</structure>
					</array>
				</structure>
				<string name="$filePath" optional="yes" comments="relative path to upload directory; download directly when not specified" />
				<structure name="$options">
					<boolean name="multipleWorksheets" default="false" />
					<boolean name="showRecordCount" default="false" />
					<structure name="columnWidth" default="~emptyArray~">
						<array name="~worksheetName~">
							<number name="+" />
						</array>
					</structure>
				</structure>
			</in>
			<out>
				<!-- file output -->
				<file name="~uploadDir~/~filePath~" />
				<!-- return value -->
				<structure name="~return~">
					<string name="path" />
					<string name="url" />
				</structure>
			</out>
		</io>
	</fusedoc>
	*/
	public static function array2xls($fileData, $filePath=null, $options=[]) {
		// validate library
		$libClass = self::$libPath['array2xls'];
		if ( !class_exists($libClass) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Library is missing ('.$libClass.')';
			return false;
		}
		// proceed to transform
		$result = Util_XLS::array2xls($fileData, $filePath, $options);
		if ( $result === false ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] '.Util_XLS::error();
			return false;
		}
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			perform data encryption
		</description>
		<io>
			<in>
				<!-- config -->
				<structure name="$fusebox->config['encrypt']|FUSEBOXY_UTIL_ENCRYPT" optional="yes">
					<string name="key" />
					<string name="vendor" optional="yes" default="mcrypt|openssl" />
					<string name="algo" optional="yes" default="~MCRYPT_RIJNDAEL_256~|BF-ECB" />
					<string name="mode" optional="yes" default="~MCRYPT_MODE_ECB~|0" comments="used as options for openssl" />
					<string name="iv" optional="yes" default="" commens="initial vector" />
				</structure>
				<!-- param -->
				<string name="$action" comments="encrypt|decrypt" />
				<string name="$data" />
				<structure name="$cfg" optional="yes">
					<string name="*" comments="override corresponding item in framework config" />
				</structure>
			</in>
			<out>
				<string name="~return~" optional="yes" oncondition="success" />
				<boolean name="~return~" value="false" optional="yes" oncondition="failure" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function crypt($action, $data, $cfg=[]) {
		// fix custom config
		if ( empty($cfg) ) $cfg = array();
		elseif ( is_string($cfg) ) $cfg = array('key' => $cfg);
		// load config (from framework or constant)
		if ( class_exists('F') ) $baseConfig = F::config('encrypt');
		elseif ( defined('FUSEBOXY_UTIL_ENCRYPT') ) $baseConfig = FUSEBOXY_UTIL_ENCRYPT;
		else $baseConfig = array();
		// fix base config
		if ( is_string($baseConfig) ) $baseConfig = array('key' => $baseConfig);
		// merge base config into custom config
		foreach ( $baseConfig as $baseKey => $baseVal ) $cfg[$baseKey] = $cfg[$baseKey] ?? $baseVal;
		// validation
		if ( empty($cfg['key']) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Encryption key is missing';
			return false;
		}
		// defult config
		if ( empty($cfg['vendor']) ) $cfg['vendor'] = ( PHP_MAJOR_VERSION < 7 ) ? 'mcrypt' : 'openssl';
		if ( empty($cfg['algo'])   ) $cfg['algo']   = ( $cfg['vendor'] == 'mcrypt' ) ? MCRYPT_RIJNDAEL_256 : 'BF-ECB';
		if ( empty($cfg['mode'])   ) $cfg['mode']   = ( $cfg['vendor'] == 'mcrypt' ) ? MCRYPT_MODE_ECB : 0;
		if ( empty($cfg['iv'])     ) $cfg['iv']     = ( $cfg['vendor'] == 'mcrypt' ) ? null : '';
		// start
		try {
			// url-friendly special character for base64 string replacement
			// ===> replace plus-sign (+), slash (/), and equal-sign (=) in base64 string
			// ===> replace by underscore (_), dash (-), and dot (.)
			$url_unsafe = array('+', '/', '=');
			$url_safe   = array('_', '-', '.');
			// encryption
			if ( $action == 'encrypt' ) {
				$result = $data;
				// raw data ===> encrypted
				if ( $cfg['vendor'] == 'mcrypt' ) $result = mcrypt_encrypt($cfg['algo'], $cfg['key'], $result, $cfg['mode'], $cfg['iv']);
				else $result = openssl_encrypt($result, $cfg['algo'], $cfg['key'], $cfg['mode'], $cfg['iv']);
				// encrypted ===> base64
				$result = base64_encode($result);
				// base64 ===> url-safe
				$result = str_replace($url_unsafe, $url_safe, $result);
			// decryption
			} elseif ( $action == 'decrypt' ) {
				$result = $data;
				// url-safe ===> base64
				$result = str_replace($url_safe, $url_unsafe, $result);
				// base64 ===> encrypted
				$result = base64_decode($result);
				// encrypted ===> raw data
				if ( $cfg['vendor'] == 'mcrypt' ) $result = mcrypt_decrypt($cfg['algo'], $cfg['key'], $result, $cfg['mode'], $cfg['iv']);
				else $result = openssl_decrypt($result, $cfg['algo'], $cfg['key'], $cfg['mode'], $cfg['iv']);
				// remove padded null characters
				// ===> http://ca.php.net/manual/en/function.mcrypt-decrypt.php#54734
				// ===> http://stackoverflow.com/questions/9781780/why-is-mcrypt-encrypt-putting-binary-characters-at-the-end-of-my-string
				$result = rtrim($result, "\0");
			// validation
			} else {
				self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Invalid action ('.$action.')';
				return false;
			}
		// catch any error
		} catch (Exception $e) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Crypt error ('.$e->getMessage().')';
			return false;
		}
		// validate result
		if ( $result === '' ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Failed to '.$action.' data';
			return false;
		}
		// done!
		return $result;
	}
	// alias methods
	public static function decrypt($data, $cfg=[]) { return self::crypt('decrypt', $data, $cfg); }
	public static function encrypt($data, $cfg=[]) { return self::crypt('encrypt', $data, $cfg); }




	/**
	<fusedoc>
		<description>
			convert hex string to rgb
		</description>
		<io>
			<in>
				<string name="$hex" example="ffccaa|#ffccaa|fca|#fca" />
			</in>
			<out>
				<structure name="~return~">
					<string name="r|g|b" />
				</structure>
			</out>
		</io>
	</fusedoc>
	*/
	public static function hex2rgb($hex) {
		$hex = trim(trim($hex), '#');
		// turn 3-digit to 6-digit (when necessary)
		if ( strlen($hex) == 3 ) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
		// extract value from hex
		// ===> covert each into decimal
		return array_map('hexdec', [
			'r' => ( ( $hex[0] ?? '' ).( $hex[1] ?? '' ) ) ?: 0,
			'g' => ( ( $hex[2] ?? '' ).( $hex[3] ?? '' ) ) ?: 0,
			'b' => ( ( $hex[4] ?? '' ).( $hex[5] ?? '' ) ) ?: 0,
		]);
	}




	/**
	<fusedoc>
		<description>
			convert html to markdown (human-readable format)
		</description>
		<io>
			<in>
				<string name="$html" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function html2md($html) {
		// load library
		foreach ( self::$libPath['html2md'] as $path ) {
			if ( !is_file($path) ) {
				self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Markdownify library is missing ('.$path.')';
				return false;
			}
			require_once($path);
		}
		// done!
		$parser = new Markdownify\Converter;
		return $parser->parseString($html);
	}




	/**
	<fusedoc>
		<description>
			convert html to PDF file
		</description>
		<io>
			<in>
				<string name="$html" />
				<string name="$filePath" optional="yes" default="~null~" comments="relative path to upload directory; use {false} or {null} to display PDF directly" />
				<structure name="$pageOptions" optional="yes">
					<string name="paperSize" default="A4" value="A3|A4|A5|~array(width,height)~">
						[A3] 297 x 420
						[A4] 210 x 297
						[A5] 148 x 210
					</string>
					<string name="orientation" default="P" value="P|L" />
					<string name="fontFamily" default="" />
					<number name="fontSize" default="12" />
					<number name="marginTop|marginLeft|marginRight|marginBottom" default="10" comments="1cm" />
				</structure>
			</in>
			<out>
				<!-- file output -->
				<file name="~uploadDir~/~filePath~" optional="yes" oncondition="when {filePath} specified" />
				<!-- return value -->
				<structure name="~return~" optional="yes" oncondition="when {filePath} specified">
					<string name="path" />
					<string name="url" />
				</structure>
			</out>
		</io>
	</fusedoc>
	*/
	public static function html2pdf($html, $filePath=null, $pageOptions=[]) {
		// validate library
		$libClass = self::$libPath['html2pdf'];
		if ( !class_exists($libClass) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Library is missing ('.$libClass.')';
			return false;
		}
		// proceed to transform
		$result = Util_PDF::html2pdf($html, $filePath, $pageOptions);
		if ( $result === false ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] '.Util_PDF::error();
			return false;
		}
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			send http-request and get response body
		</description>
		<io>
			<in>
				<!-- framework config -->
				<structure name="config" scope="$fusebox" optional="yes">
					<string name="httpProxy" optional="yes" />
					<string name="httpsProxy" optional="yes" />
				</structure>
				<!-- constants (when no framework config) -->
				<string name="UTIL_HTTP_PROXY" optional="yes" />
				<string name="UTIL_HTTPS_PROXY" optional="yes" />
				<!-- parameters -->
				<string name="$method" default="GET" example="GET|POST|PUT|DELETE|.." />
				<string name="$url" />
				<structure name="$fields">
					<string name="~fieldName~" comments="no url-encoded" />
				</structure>
				<structure name="$headers">
					<string name="~headerName~" />
				</structure>
				<reference name="&$httpStatus" />
				<reference name="&$responseHeader" />
				<reference name="&$responseTime" />
			</in>
			<out>
				<!-- return value -->
				<string name="~return~" optional="yes" oncondition="success" comments="page response" />
				<!-- additional info -->
				<string name="$httpStatus" optional="yes" />
				<string name="$responseHeader" optional="yes" oncondition="success" />
				<number name="$responseTime" optional="yes" oncondition="success" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function httpRequest($method='GET', $url, $fields=[], $headers=[], &$httpStatus=null, &$responseHeader=null, &$responseTime=null) {
		// fix param (when necessary)
		$method = strtoupper($method);
		// merge params into url (when necessary)
		if ( $method == 'GET' ) {
			$qs = !empty($fields) ? http_build_query($fields) : '';
			if ( !empty($qs) ) $url .= ( strpos($url, '?') === false ) ? '?' : '&';
			$url .= $qs;
			$fields = [];
		}
		// transform headers
		$headers = array_map(function($key, $val){
			return "{$key}: {$val}";
		}, array_keys($headers), $headers);
		// apply cookie file (to avoid redirect loop when target server check cookies)
		$cookie_file = sys_get_temp_dir().'/cookies/'.md5($_SERVER['REMOTE_ADDR']).'.txt';
		// load page remotely
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// set options according to method
		if ( $method == 'GET' ) {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		} elseif ( $method == 'POST' ) {
			curl_setopt($ch, CURLOPT_POSTREDIR, 3);
			curl_setopt($ch, CURLOPT_POST, true);
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}
		// put form data into options (when necessary)
		if ( $method == 'POST' or !empty($fields) ) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		}
		// set other options
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
		// load proxy config (when necessary)
		$httpsProxy = class_exists('F') ? F::config('httpsProxy') : ( defined('FUSEBOXY_UTIL_HTTPS_PROXY') ? FUSEBOXY_UTIL_HTTPS_PROXY : '' );
		$httpProxy  = class_exists('F') ? F::config('httpProxy')  : ( defined('FUSEBOXY_UTIL_HTTP_PROXY')  ? FUSEBOXY_UTIL_HTTP_PROXY  : '' );
		if ( !empty($httpsProxy) and parse_url($url, PHP_URL_SCHEME) == 'https' ) $proxyConfig = $httpsProxy;
		elseif ( !empty($httpProxy) ) $proxyConfig = $httpProxy;
		// parse & apply proxy config (if any)
		if ( isset($proxyConfig) ) {
			$proxy = parse_url($proxyConfig);
			$proxyAuth = '';
			if ( !empty($proxy['user']) ) $proxyAuth .= $proxy['user'];
			if ( !empty($proxy['pass']) ) $proxyAuth .= ":{$proxy['pass']}";
			$proxyURL = !empty($proxyAuth) ? str_replace("{$proxyAuth}@", '', $proxyConfig) : $proxyConfig;
			curl_setopt($ch, CURLOPT_PROXY, $proxyURL);
			if ( !empty($proxyAuth) ) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
		}
		// get response
		$response = curl_exec($ch);
		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$responseHeader = substr($response, 0, $headerSize);
		$responseTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
		$pageBody = substr($response, $headerSize);
		if ( $response === false ) $pageError = curl_error($ch);
		curl_close($ch);
		// parse response header
		$arr = array_filter(array_map('trim', explode("\n", $responseHeader)));
		$responseHeader = array();
		foreach ( $arr as $i => $item ) {
			if ( stripos($item, ':') !== false ) {
				list($key, $val) = array_map('trim', explode(':', $item, 2));
				$responseHeader[$key] = $val;
			}
		}
		// validate response
		if ( isset($pageError) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] '.$pageError;
			return false;
		}
		// success!
		return $pageBody;
	}
	// alias methods
	public static function getPage($url, &$httpStatus=null, &$responseHeader=null, &$responseTime=null) { return self::httpRequest('GET', $url, [], [], $httpStatus, $responseHeader, $responseTime); }
	public static function postPage($url, $fields=[], &$httpStatus=null, &$responseHeader=null, &$responseTime=null) { return self::httpRequest('POST', $url, $fields, [], $httpStatus, $responseHeader, $responseTime); }




	/**
	<fusedoc>
		<description>
			send email by PHPMailer
		</description>
		<io>
			<in>
				<!-- config -->
				<structure name="$fusebox->config['smtp']|FUSEBOXY_UTIL_SMTP">
					<number name="debug" comments="1 = errors and messages, 2 = messages only" />
					<string name="secure" comments="ssl|tsl, secure transfer enabled REQUIRED for GMail" />
					<boolean name="auth" comments="authentication enabled" />
					<string name="host" />
					<number name="port" />
					<string name="username" />
					<string name="password" />
				</structure>
				<!-- parameter -->
				<structure name="$param">
					<datetime name="datetime" optional="yes" />
					<string name="from_name|fromName" optional="yes" />
					<string name="from" optional="yes" default="~smtp user~" />
					<array name="to" comments="auto tranform comma-or-colon-delimited list to array" />
					<array name="cc" optional="yes" comments="auto tranform comma-or-colon-delimited list to array" />
					<array name="bcc" optional="yes" comments="auto tranform comma-or-colon-delimited list to array" />
					<string name="subject" />
					<string name="body" />
					<boolean name="isHTML" optional="yes" default="true" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function mail($param) {
		// load library
		foreach ( self::$libPath['mail'] as $path ) {
			if ( !is_file($path) ) {
				self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] PHPMailer library is missing ('.$path.')';
				return false;
			}
			require_once($path);
		}
		// fix param
		if ( !empty($param['fromName']) and empty($param['from_name']) ) {
			$param['from_name'] = $param['fromName'];
		}
		// load config (from framework or constant)
		if ( class_exists('F') ) $smtpConfig = F::config('smtp');
		elseif ( defined('FUSEBOXY_UTIL_SMTP') ) $smtpConfig = FUSEBOXY_UTIL_SMTP;
		else $smtpConfig = array();
		// fix config
		if ( is_string($smtpConfig) ) $smtpConfig = array('host' => $smtpConfig);
		// validate config
		if ( empty($smtpConfig) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] SMTP config is missing';
			return false;
		// validate parameters
		} elseif ( empty($param['from']) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Mail sender was not specified';
			return false;
		} elseif ( empty($param['to']) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Mail recipient was not specified';
			return false;
		} elseif ( empty($param['subject']) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Mail subject was not specified';
			return false;
		} elseif ( empty($param['body']) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Mail body was not specified';
			return false;
		}
		// start...
		try {
			// init mail object
			$mailer = new PHPMailer\PHPMailer\PHPMailer(true);
			$mailer->CharSet = 'UTF-8';
			// mail server settings
			$mailer->IsSMTP();  // enable SMTP
			if ( isset($smtpConfig['debug'])    ) $mailer->SMTPDebug  = $smtpConfig['debug'];
			if ( isset($smtpConfig['auth'])     ) $mailer->SMTPAuth   = $smtpConfig['auth'];
			if ( isset($smtpConfig['secure'])   ) $mailer->SMTPSecure = $smtpConfig['secure'];
			if ( isset($smtpConfig['host'])     ) $mailer->Host       = $smtpConfig['host'];
			if ( isset($smtpConfig['port'])     ) $mailer->Port       = $smtpConfig['port'];
			if ( isset($smtpConfig['username']) ) $mailer->Username   = $smtpConfig['username'];
			if ( isset($smtpConfig['password']) ) $mailer->Password   = $smtpConfig['password'];
			// manipulate variables
			if ( !is_array($param['to']) ) {
				$param['to'] = array_filter(explode(';', str_replace(',', ';', str_replace(' ', '', $param['to']))));
			}
			if ( isset($param['cc'])  and !is_array($param['cc']) ) {
				$param['cc'] = array_filter(explode(';', str_replace(',', ';', str_replace(' ', '', $param['cc']))));
			}
			if ( isset($param['bcc']) and !is_array($param['bcc']) ) {
				$param['bcc'] = array_filter(explode(';', str_replace(',', ';', str_replace(' ', '', $param['bcc']))));
			}
			// mail default value
			if ( !isset($param['from'])     ) $param['from'] = $mailer->Username;
			if ( !isset($param['datetime']) ) $param['datetime'] = time();
			// mail options
			$mailer->From = $param['from'];
			if ( isset($param['from_name']) ) $mailer->FromName = $param['from_name'];
			foreach ( $param['to'] as $to ) $mailer->AddAddress($to);
			if ( !empty($param['cc'])  ) foreach ( $param['cc']  as $cc  ) $mailer->AddCC($cc);
			if ( !empty($param['bcc']) ) foreach ( $param['bcc'] as $bcc ) $mailer->AddBCC($bcc);
			$mailer->IsHTML( isset($param['isHTML']) ? $param['isHTML'] : true );
			$mailer->Subject = $param['subject'];
			$mailer->Body = $param['body'];
			// send message
			$result = $mailer->Send();
			if ( !$result ) self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Error occurred while sending mail ('.$mailer->ErrorInfo.')';
		// catch any error
		} catch (Exception $e) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Mail error ('.$e->getMessage().')';
			return false;
		}
		// done!
		return $result;
	}
	// alias methods
	public static function sendEmail($data) { self::mail($data); }
	public static function sendMail($data)  { self::mail($data); }




	/**
	<fusedoc>
		<description>
			convert markdown (human-readable format) to html
		</description>
		<io>
			<in>
				<string name="$md" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function md2html($md) {
		// load library
		$path = self::$libPath['md2html'];
		if ( !is_file($path) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Parsedown library is missing ('.$path.')';
			return false;
		}
		require_once($path);
		// done!
		$parser = new Parsedown();
		return $parser->text($md);
	}




	/**
	<fusedoc>
		<description>
			create and return new phpQuery document
		</description>
		<io>
			<in>
				<!-- library -->
				<structure name="$libPath" scope="self">
					<path name="phpQuery" />
				</structure>
				<!-- parameter --->
				<string name="$html" />
			</in>
			<out>
				<!-- helper function -->
				<function name="pq" />
				<!-- return value -->
				<object name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function phpQuery($html) {
		// load library
		$path = self::$libPath['phpQuery'];
		if ( !is_file($path) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] phpQuery library is missing ('.$path.')';
			return false;
		}
		require_once($path);
		// done!
		return phpQuery::newDocument($html);
	}




	/**
	<fusedoc>
		<description>
			obtain request scheme (http/https)
		</description>
		<io>
			<in>
				<string name="HTTP_X_FORWARDED_PROTO" scope="$_SERVER" optional="yes" oncondition="load-balancer" />
				<string name="HTTPS" scope="$_SERVER" comments="on|1" optional="yes" />
				<string name="REQUEST_SCHEME" scope="$_SERVER" comments="http|https" optional="yes" />
			</in>
			<out>
				<string name="~return~" comments="http|https" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function requestScheme() {
		if ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ) return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
		if ( isset($_SERVER['HTTPS']) and in_array($_SERVER['HTTPS'], ['on','1']) ) return 'https';
		if ( isset($_SERVER['REQUEST_SCHEME']) ) return strtolower($_SERVER['REQUEST_SCHEME']);
		if ( isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443 ) return 'https';
		return 'http';
	}




	/**
	<fusedoc>
		<description>
			load file from server and feed binary stream to browser
		</description>
		<io>
			<in>
				<string name="$filePath" comments="full server path of file" />
				<structure name="$options">
					<boolean name="download" optional="yes" default="false" />
					<boolean name="deleteAfterward" optional="yes" default="false" />
				</structure>
			</in>
			<out />
		</io>
	</fusedoc>
	*/
	public static function streamFile($filePath, $options=[]) {
		// default options
		$options['download'] = $options['download'] ?? false;
		$options['deleteAfterward'] = $options['deleteAfterward'] ?? false;
		// check file existence
		if ( !is_file($filePath) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] File not found ('.$filePath.')';
			return false;
		}
		// get file info
		$fileType = mime_content_type($filePath);
		$fileSize = filesize($filePath);
		// send correct header
		header('Content-Type: '.$fileType);
		header('Content-Length: '.$fileSize);
		if ( $options['download'] ) header('Content-Disposition: Attachment; filename='.pathinfo($filePath, PATHINFO_BASENAME)); 
		// open file in binary mode
		$fp = fopen($filePath, 'rb');
		// stream file to client
		fpassthru($fp);
		// remove file afterward
		if ( $options['deleteAfterward'] ) unlink($filePath);
		// abort further operation
		exit();
	}




	/**
	<fusedoc>
		<description>
			load tmpDir from framework config or constant
			===> append with specified sub-path
			===> create directory in server
		</description>
		<io>
			<in>
				<!-- config -->
				<string name="$fusebox->config['tmpDir']|FUSEBOXY_UTIL_TMP_DIR" />
				<!-- param -->
				<path name="$append" optional="yes" comments="file path to append" />
			</in>
			<out>
				<!-- new directory -->
				<path name="dirname(~tmpDir~/~append~)" optional="yes" />
				<!-- return value -->
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function tmpDir($append='') {
		if ( class_exists('F') ) $result = F::config('tmpDir');
		elseif ( defined('FUSEBOXY_UTIL_TMP_DIR') ) $result = FUSEBOXY_UTIL_TMP_DIR;
		// validation
		if ( empty($result) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Config [tmpDir] is required';
			return false;
		}
		// unify directory separator
		$result = str_ireplace('\\', '/', $result);
		$append = str_ireplace('\\', '/', $append);
		// add trailing slash (when necessary)
		if ( substr($result, -1) != '/' ) $result .= '/';
		// append file path
		$result .= $append;
		// create directory (when necessary)
		if ( !is_dir($result) and !mkdir($result, 0777, true) ) {
			$err = error_get_last();
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Error creating directory - '.$err['message'];
			return false;
		}
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			load uploadDir from framework config or constant
			===> append with specified sub-path
			===> create directory in server
		</description>
		<io>
			<in>
				<!-- config -->
				<string name="$fusebox->config['uploadDir']|FUSEBOXY_UTIL_UPLOAD_DIR" />
				<!-- param -->
				<path name="$append" optional="yes" comments="file path to append" />
			</in>
			<out>
				<!-- new directory -->
				<path name="dirname(~uploadDir~/~append~)" optional="yes" />
				<!-- return value -->
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function uploadDir($append='') {
		if ( class_exists('F') ) $result = F::config('uploadDir');
		elseif ( defined('FUSEBOXY_UTIL_UPLOAD_DIR') ) $result = FUSEBOXY_UTIL_UPLOAD_DIR;
		// validation
		if ( empty($result) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Config [uploadDir] is required';
			return false;
		}
		// unify directory separator
		$result = str_ireplace('\\', '/', $result);
		$append = str_ireplace('\\', '/', $append);
		// add trailing slash (when necessary)
		if ( substr($result, -1) != '/' ) $result .= '/';
		// append file path
		$result .= $append;
		// create directory (when necessary)
		if ( !is_dir($result) and !mkdir($result, 0777, true) ) {
			$err = error_get_last();
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Error creating directory ('.$err['message'].')';
			return false;
		}
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			load uploadUrl from framework config or constant
		</description>
		<io>
			<in>
				<!-- config -->
				<string name="$fusebox->config['uploadUrl']|FUSEBOXY_UTIL_UPLOAD_URL" />
				<!-- param -->
				<path name="$append" optional="yes" comments="file path to append" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function uploadUrl($append='') {
		if ( class_exists('F') ) $result = F::config('uploadUrl');
		elseif ( defined('FUSEBOXY_UTIL_UPLOAD_URL') ) $result = FUSEBOXY_UTIL_UPLOAD_URL;
		// validation
		if ( empty($result) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Config [uploadUrl] is required';
			return false;
		}
		// unify directory separator
		$result = str_ireplace('\\', '/', $result);
		$append = str_ireplace('\\', '/', $append);
		// add trailing slash (when necessary)
		if ( substr($result, -1) != '/' ) $result .= '/';
		// append file path
		$result .= $append;
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			generate (psuedo-random) UUID
			===> http://php.net/manual/en/function.uniqid.php#94959
		</description>
		<io>
			<in>
				<string name="$version" default="v4" />
				<string name="$namespace" optional="yes" />
				<string name="$name" optional="yes" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function uuid($version='v4', $namespace=null, $name=null) {
		$version = strtolower($version);
		// validation
		if ( !in_array($version, ['v3','v4','v5']) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Invalid UUID version ('.$version.')';
			return false;
		} elseif ( in_array($version, ['v3','v5']) and ( empty($namespace) or empty($name) ) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Arguments [namespace] and [name] are both required';
			return false;
		}
		// determine method to call
		$method = 'self::uuid__'.$version;
		// done!
		return ( $version == 'v4' ) ? call_user_func($method) : call_user_func($method, $namespace, $name);
	}
	// UUID v3
	private static function uuid__v3($namespace, $name) {
		if ( self::uuid__isValidNamespace($namespace) === false ) return false;
		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-','{','}'), '', $namespace);
		// Binary Value
		$nstr = '';
		// Convert Namespace UUID to bits
		for($i = 0; $i < strlen($nhex); $i+=2) $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
		// Calculate hash value
		$hash = md5($nstr . $name);
		// done!
		return sprintf('%08s-%04s-%04x-%04x-%12s',
			// 32 bits for "time_low"
			substr($hash, 0, 8),
			// 16 bits for "time_mid"
			substr($hash, 8, 4),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 3
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			// 48 bits for "node"
			substr($hash, 20, 12)
		);
	}
	// UUID v4
	private static function uuid__v4() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
	// UUID v5
	private static function uuid__v5($namespace, $name) {
		if ( self::uuid__isValidNamespace($namespace) === false ) return false;
		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-','{','}'), '', $namespace);
		// Binary Value
		$nstr = '';
		// Convert Namespace UUID to bits
		for($i = 0; $i < strlen($nhex); $i+=2) $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
		// Calculate hash value
		$hash = sha1($nstr . $name);
		// done!
		return sprintf('%08s-%04s-%04x-%04x-%12s',
			// 32 bits for "time_low"
			substr($hash, 0, 8),
			// 16 bits for "time_mid"
			substr($hash, 8, 4),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 5
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			// 48 bits for "node"
			substr($hash, 20, 12)
		);
	}
	// UUID namspace validation
	private static function uuid__isValidNamespace($namespace) {
		$valid = ( preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1 );
		if ( !$valid ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Invalid UUID namespace ('.$namespace.')';
			return false;
		}
		return true;
	}




	/**
	<fusedoc>
		<description>
			convert csv/xls/xlsx to array
			===> convert first worksheet only
			===> use first row as column name (when necessary)
			===> use snake-case for column name (e.g. this_is_col_name)
		</description>
		<io>
			<in>
				<path name="$file" comments="excel file path" />
				<structure name="$options">
					<number name="worksheet" default="0" comments="starts from zero" />
					<number name="startRow" default="1" comments="starts from one" />
					<boolean name="firstRowAsHeader" default="true" />
					<boolean name="convertHeaderCase" default="true" />
				</structure>
			</in>
			<out>
				<array name="~return~">
					<structure name="+">
						<string name="~columnName~" />
					</structure>
				</array>
			</out>
		</io>
	</fusedoc>
	*/
	public static function xls2array($file, $options=[]) {
		// validate library
		$libClass = self::$libPath['xls2array'];
		if ( !class_exists($libClass) ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Library is missing ('.$libClass.')';
			return false;
		}
		// proceed to transform
		$result = Util_XLS::xls2array($file, $options);
		if ( $result === false ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] '.Util_XLS::error();
			return false;
		}
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			convert xml to array
		</description>
		<io>
			<in>
				<string name="$xml_string" />
			</in>
			<out>
				<array name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function xml2array($xml_string) {
		$json = self::xml2json($xml_string);
		if ( $json === false ) return false;
		$result = json_decode($json, true);
		if ( $result === false ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Error parsing JSON string';
			return false;
		}
		return $result;
	}




	/**
	<fusedoc>
		<description>
			convert xml to json
		</description>
		<io>
			<in>
				<string name="$xml_string" />
			</in>
			<out>
				<string name="~return~" format="json" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function xml2json($xml_string) {
		$xml = simplexml_load_string($xml_string);
		if ( $xml === false ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Error parsing XML string';
			return false;
		}
		$json = json_encode($xml);
		if ( $json === false ) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] Error converting XML to JSON string';
			return false;
		}
		return $json;
	}




	/**
	<fusedoc>
		<description>
			perform xsl transform
			===> require PHP_XSL component enabled at server
			===> http://php.net/manual/en/class.xsltprocessor.php
		</description>
		<io>
			<in>
				<string name="$xml_string" />
				<string name="$xsl_string" />
			</in>
			<out>
				<string name="~return~" optional="yes" oncondition="when success" />
				<boolean name="~return~" value="false" optional="yes" oncondition="when failure" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function xslt($xml_string, $xsl_string) {
		try {
			// create xml-dom
			$xml = new DOMDocument;
			$xml->loadXML($xml_string);
			// create xsl-dom
			$xsl = new DOMDocument;
			$xsl->loadXML($xsl_string);
			// php extension <php_xsl> must be loaded first
			$proc = new XSLTProcessor;
			$proc->importStyleSheet($xsl);
			// return string (when succeed) or false (when fail)
			$result = $proc->transformToXML($xml);
			if ( !$result ) self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] '.libxml_get_last_error();
		// catch any error
		} catch (Exception $e) {
			self::$error = '['.__CLASS__.'::'.__FUNCTION__.'] XSLT error ('.$e->getMessage().')';
			return false;
		}
		// done!
		return $result;
	}


} // class