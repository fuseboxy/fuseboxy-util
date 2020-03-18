<?php
class Util {


	// properties : library for corresponding methods
	public static $libPath = array(
		'mail' => array(
			__DIR__.'/../../lib/phpmailer/6.1.4/PHPMailer.php',
			__DIR__.'/../../lib/phpmailer/6.1.4/Exception.php',
			__DIR__.'/../../lib/phpmailer/6.1.4/SMTP.php',
			__DIR__.'/../../lib/phpmailer/6.1.4/OAuth.php',
		),
		'phpQuery' => array(
			__DIR__.'/../../lib/phpquery/2.0/phpQuery.php',
		),
		'md2html' => array(
			__DIR__.'/../../lib/parsedown/1.7.4/Parsedown.php',
		),
		'xls2array' => array(
			__DIR__.'/../../lib/simplexls/0.9.4/SimpleXLS.php',
			__DIR__.'/../../lib/simplexlsx/0.8.9/SimpleXLSX.php',
		),
	);




	// get (latest) error message
	private static $error;
	public static function error() { return self::$error; }




	/**
	<fusedoc>
		<description>
			perform data encryption
		</description>
		<io>
			<in>
				<structure name="config" scope="$fusebox">
					<structure name="encrypt">
						<string name="key" />
						<string name="library" optional="yes" comments="mcrypt|openssl" />
						<string name="cipher" optional="yes" default="~MCRYPT_RIJNDAEL_256~|AES-256-CBC" />
						<string name="mode" optional="yes" default="~MCRYPT_MODE_ECB~|0" comments="used as options for openssl" />
						<string name="iv" optional="yes" default="" />
					</structure>
				</structure>
				<string name="$action" comments="encrypt|decrypt" />
				<string name="$data" />
			</in>
			<out>
				<string name="~return~" optional="yes" oncondition="success" />
				<boolean name="~return~" value="false" optional="yes" oncondition="failure" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function crypt($action, $data) {
		$encryptConfig = F::config('encrypt');
		// validation
		if ( empty($encryptConfig['key']) ) {
			self::$error = 'Encrypt key is missing';
			return false;
		}
		// defult config
		if ( empty($encryptConfig['library']) ) $encryptConfig['library'] = ( PHP_MAJOR_VERSION < 7 ) ? 'mcrypt' : 'openssl';
		if ( empty($encryptConfig['cipher'] ) ) $encryptConfig['cipher']  = ( $encryptConfig['library'] == 'mcrypt' ) ? MCRYPT_RIJNDAEL_256 : 'BF-ECB';
		if ( empty($encryptConfig['mode']   ) ) $encryptConfig['mode']    = ( $encryptConfig['library'] == 'mcrypt' ) ? MCRYPT_MODE_ECB : 0;
		if ( empty($encryptConfig['iv']     ) ) $encryptConfig['iv']      = ( $encryptConfig['library'] == 'mcrypt' ) ? null : '';
		// start
		try {
			// url-friendly special character for base64 string replacement
			// ===> replace plus-sign (+), slash (/), and equal-sign (=) in base64 string
			// ===> replace by underscore (_), dash (-), and dot (.)
			$url_unsafe = array('+','/','=');
			$url_safe = array('_','-','.');
			// validation & start
			if ( $action == 'encrypt' ) {
				if ( $encryptConfig['library'] == 'mcrypt' ) {
					$data = mcrypt_encrypt($encryptConfig['cipher'], $encryptConfig['key'], $data, $encryptConfig['mode'], $encryptConfig['iv']);
				} else {
					$data = openssl_encrypt($data, $encryptConfig['cipher'], $encryptConfig['key'], $encryptConfig['mode'], $encryptConfig['iv']);
				}
				// base64-encode the encrypted data
				$data = base64_encode($data);
				// make the base64 string more url-friendly
				for ( $i=0; $i<count($url_unsafe); $i++ ) {
					$data = str_replace($url_unsafe[$i], $url_safe[$i], $data);
				}
			} elseif ( $action == 'decrypt' ) {
				for ( $i=0; $i<count($url_unsafe); $i++ ) {
					$data = str_replace($url_safe[$i], $url_unsafe[$i], $data);
				}
				$data = base64_decode($data);
				if ( $encryptConfig['library'] == 'mcrypt' ) {
					$data = mcrypt_decrypt($encryptConfig['cipher'], $encryptConfig['key'], $data, $encryptConfig['mode'], $encryptConfig['iv']);
				} else {
					$data = openssl_decrypt($data, $encryptConfig['cipher'], $encryptConfig['key'], $encryptConfig['mode'], $encryptConfig['iv']);
				}
				// remove padded null characters
				// ===> http://ca.php.net/manual/en/function.mcrypt-decrypt.php#54734
				// ===> http://stackoverflow.com/questions/9781780/why-is-mcrypt-encrypt-putting-binary-characters-at-the-end-of-my-string
				$data = rtrim($data, "\0");
			} else {
				self::$error = "Invalid action ({$action})";
				return false;
			}
		// catch any error
		} catch (Exception $e) {
			self::$error = "Crypt error ({$e->getMessage()})";
			return false;
		}
		// done!
		return $data;
	}
	// alias methods
	public static function decrypt($data) { return self::crypt('decrypt', $data); }
	public static function encrypt($data) { return self::crypt('encrypt', $data); }




	/**
	<fusedoc>
		<description>
			send http-request and get response body
		</description>
		<io>
			<in>
				<structure name="config" scope="$fusebox">
					<string name="httpProxy" optional="yes" />
					<string name="httpsProxy" optional="yes" />
				</structure>
				<string name="$url" />
				<string name="$method" default="GET" comments="GET|POST|PUT|DELETE" />
				<structure name="$fields">
					<string name="~fieldName~" comments="no url-encoded" />
				</structure>
				<structure name="$headers">
					<string name="~headerName~" />
				</structure>
				<reference name="&$responseHeader" />
				<reference name="&$responseTime" />
			</in>
			<out>
				<string name="~return~" optional="yes" oncondition="success" comments="page response" />
				<string name="$responseHeader" optional="yes" oncondition="success" />
				<number name="$responseTime" optional="yes" oncondition="success" />
				<boolean name="~return~" value="false" optional="yes" oncondition="failure" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function httpRequest($method='GET', $url, $fields=array(), $headers=array(), &$responseHeader=null, &$responseTime=null) {
		// fix param (when necessary)
		$method = strtoupper($method);
		// validation
		if ( !in_array($method, array('GET','POST','PUT','DELETE')) ) {
			self::$error = "Invalid REST method ({$method})";
			return false;
		}
		// merge params into url (when necessary)
		if ( $method != 'POST' ) {
			$qs = !empty($fields) ? http_build_query($fields) : '';
			if ( !empty($qs) ) $url .= ( strpos($url, '?') === false ) ? '?' : '&';
			$url .= $qs;
		}
		// transform headers
		$arr = $headers;
		$headers = array();
		foreach ( $arr as $key => $val ) $headers[] = "{$key} : {$val}";
		// apply cookie file (to avoid redirect loop when target server check cookies)
		$cookie_file = sys_get_temp_dir().'/cookies/'.md5($_SERVER['REMOTE_ADDR']).'.txt';
		// load page remotely
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if ( $method == 'GET' ) {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		} elseif ( $method == 'POST' ) {
			curl_setopt($ch, CURLOPT_POSTREDIR, 3);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		} elseif ( $method == 'PUT' ) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		} elseif ( $method == 'DELETE' ) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
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
		// apply proxy (when necessary)
		if ( parse_url($url, PHP_URL_SCHEME) == 'https' and !empty( F::config('httpsProxy') ) ) {
			$proxyConfig = F::config('httpsProxy');
		} elseif ( !empty( F::config('httpProxy') ) ) {
			$proxyConfig = F::config('httpProxy');
		}
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
		// validate response
		if ( isset($pageError) ) {
			self::$error = $pageError;
			return false;
		}
		// success!
		return $pageBody;
	}
	// alias methods
	public static function getPage ($url,                  &$responseHeader=null, &$responseTime=null) { return self::httpRequest('GET',  $url, array(), array(), $responseHeader, $responseTime); }
	public static function postPage($url, $fields=array(), &$responseHeader=null, &$responseTime=null) { return self::httpRequest('POST', $url, $fields, array(), $responseHeader, $responseTime); }




	/**
	<fusedoc>
		<description>
			remove space between tags
		</description>
		<io>
			<in>
				<string name="$html" />
			</in>
			<out>
				<string name="~return~" optional="yes" oncondition="success" />
				<boolean name="~return~" value="false" optional="yes" oncondition="failure" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function minifyHtml($html) {
		return preg_replace('~>\s+<~m', '><', trim($html));
	}




	/**
	<fusedoc>
		<description>
			send email
			===> require PHPMailer library
			===> https://github.com/PHPMailer/PHPMailer
		</description>
		<io>
			<in>
				<!-- library -->
				<structure name="$libPath" scope="self">
					<array name="mail">
						<string name="+" />
					</array>
				</structure>
				<!-- config -->
				<structure name="config" scope="$fusebox">
					<structure name="smtp" optional="yes">
						<number name="debug" comments="1 = errors and messages, 2 = messages only" />
						<boolean name="auth" comments="authentication enabled" />
						<string name="secure" comments="ssl|tsl, secure transfer enabled REQUIRED for GMail" />
						<string name="host" />
						<number name="port" />
						<string name="username" />
						<string name="password" />
					</structure>
				</structure>
				<!-- parameter -->
				<structure name="$mail">
					<datetime name="datetime" optional="yes" />
					<string name="from_name" optional="yes" />
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
	public static function mail($mail) {
		// load library files
		if ( !class_exists('PHPMailer') ) {
			foreach ( self::$libPath['mail'] as $path ) {
				if ( !is_file($path) ) {
					self::$error = "PHPMailer library is missing ({$path})";
					return false;
				}
				require_once($path);
			}
		}
		// load config (when necessary)
		$smtpConfig = F::config('smtp');
		if ( empty($smtpConfig) ) {
			self::$error = 'SMTP config is missing';
			return false;
		}
		// validation
		if ( !isset($mail['from']) ) {
			self::$error = 'Mail sender was not specified';
			return false;
		}
		if ( !isset($mail['to']) ) {
			self::$error = 'Mail recipient was not specified';
			return false;
		}
		if ( !isset($mail['subject']) ) {
			self::$error = 'Mail subject was not specified';
			return false;
		}
		if ( !isset($mail['body']) ) {
			self::$error = 'Mail body was not specified';
			return false;
		}
		// start...
		try {
			// init mail object
			$mailer = new PHPMailer(true);
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
			if ( !is_array($mail['to']) ) {
				$mail['to'] = array_filter(explode(';', str_replace(',', ';', str_replace(' ', '', $mail['to']))));
			}
			if ( isset($mail['cc'])  and !is_array($mail['cc']) ) {
				$mail['cc'] = array_filter(explode(';', str_replace(',', ';', str_replace(' ', '', $mail['cc']))));
			}
			if ( isset($mail['bcc']) and !is_array($mail['bcc']) ) {
				$mail['bcc'] = array_filter(explode(';', str_replace(',', ';', str_replace(' ', '', $mail['bcc']))));
			}
			// mail default value
			if ( !isset($mail['from'])     ) $mail['from'] = $mailer->Username;
			if ( !isset($mail['datetime']) ) $mail['datetime'] = time();
			// mail options
			$mailer->From = $mail['from'];
			if ( isset($mail['from_name']) ) $mailer->FromName = $mail['from_name'];
			foreach ( $mail['to'] as $to ) $mailer->AddAddress($to);
			if ( !empty($mail['cc'])  ) foreach ( $mail['cc']  as $cc  ) $mailer->AddCC($cc);
			if ( !empty($mail['bcc']) ) foreach ( $mail['bcc'] as $bcc ) $mailer->AddBCC($bcc);
			$mailer->IsHTML( isset($mail['isHTML']) ? $mail['isHTML'] : true );
			$mailer->Subject = $mail['subject'];
			$mailer->Body = $mail['body'];
			// send message
			$result = $mailer->Send();
			if ( !$result ) self::$error = "Error occurred while sending mail ({$mailer->ErrorInfo})";
		// catch any error
		} catch (Exception $e) {
			self::$error = "Exception ({$e->getMessage()})";
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
			if ( !$result ) self::$error = libxml_get_last_error();
		// catch any error
		} catch (Exception $e) {
			self::$error = "XSLT error ({$e->getMessage()})";
			return false;
		}
		// done!
		return $result;
	}


} // class