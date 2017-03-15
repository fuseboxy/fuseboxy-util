<?php
class Util {


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
					<string name="encryptKey" optional="yes" />
					<string name="encryptCipher" optional="yes" />
					<string name="encryptMode" optional="yes" />
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
		global $fusebox;
		// validation
		try {
			// defult config
			$key    = isset($fusebox->config['encryptKey'])    ? $fusebox->config['encryptKey']    : 'ORSw2K365dzVn5xc17E38m7pv7GS3xkc';
			$cipher = isset($fusebox->config['encryptCipher']) ? $fusebox->config['encryptCipher'] :  MCRYPT_RIJNDAEL_256;
			$mode   = isset($fusebox->config['encryptMode'])   ? $fusebox->config['encryptMode']   :  MCRYPT_MODE_ECB;
			// url-friendly special character for base64 string replacement
			// ===> replace plus-sign (+), slash (/), and equal-sign (=) in base64 string
			// ===> replace by underscore (_), dash (-), and dot (.)
			$url_unsafe = array('+','/','=');
			$url_safe = array('_','-','.');
			// validation & start
			$result = $data;
			if ( $action == 'encrypt' ) {
				$result = mcrypt_encrypt($cipher, $key, $result, $mode);
				// base64-encode the encrypted data
				$result = base64_encode($result);
				// make the base64 string more url-friendly
				for ( $i=0; $i<count($url_unsafe); $i++ ) {
					$result = str_replace($url_unsafe[$i], $url_safe[$i], $result);
				}
			} elseif ( $action == 'decrypt' ) {
				for ( $i=0; $i<count($url_unsafe); $i++ ) {
					$result = str_replace($url_safe[$i], $url_unsafe[$i], $result);
				}
				$result = base64_decode($result);
				$result = mcrypt_decrypt($cipher, $key, $result, $mode);
				// remove padded null characters
				// ===> http://ca.php.net/manual/en/function.mcrypt-decrypt.php#54734
				// ===> http://stackoverflow.com/questions/9781780/why-is-mcrypt-encrypt-putting-binary-characters-at-the-end-of-my-string
				$result = rtrim($result, "\0");
			} else {
				self::$error = "Util::crypt() - Invalid action [{$action}]";
				return false;
			}
		// catch any error
		} catch (Exception $e) {
			self::$error = "Util::crypt() - {$e->getMessage()}";
			return false;
		}
		// done!
		return $result;
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
				<string name="$url" />
				<string name="$method" default="GET" comments="GET|POST|PUT|DELETE" />
				<structure name="$fields">
					<string name="~fieldName~" comments="no url-encoded" />
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
	public static function httpRequest($url, $method='GET', $fields=null, &$responseHeader=null, &$responseTime=null) {
		$method = strtoupper($method);
		// validation
		if ( !in_array($method, array('GET','POST','PUT','DELETE')) ) {
			self::$error = "Util::httpRequest() - Invalid method ({$method})";
			return false;
		}
		// merge params into url (when necessary)
		if ( $method != 'POST' ) {
			$qs = !empty($fields) ? http_build_query($fields) : '';
			if ( !empty($qs) ) $url .= ( strpos($url, '?') === false ) ? '?' : '&';
			$url .= $qs;
		}
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
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_INFILE, 1);
			curl_setopt($ch, CURLOPT_INFILESIZE, 1);
		} elseif ( $method == 'DELETE' ) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		// get response
		$response = curl_exec($ch);
		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$pageHeader = substr($response, 0, $headerSize);
		$pageLoadTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
		$pageBody = substr($response, $headerSize);
		if ( $response === false ) $pageError = curl_error($ch);
		curl_close($ch);
		// validate response
		if ( isset($pageError) ) {
			self::$error = $pageError;
			return false;
		} elseif ( $httpStatus != 200 ) {
			$lines = explode("\n", $pageHeader);
			self::$error = $lines[0];
			return false;
		}
		// success!
		return $pageBody;
	}
	// alias methods
	public static function getPage($url, $fields=null, &$responseHeader=null, &$responseTime=null) { return self::httpRequest('GET', $url, $fields, $responseHeader, $responseTime); }
	public static function postPage($url, $fields=null, &$responseHeader=null, &$responseTime=null) { return self::httpRequest('POST', $url, $fields, $responseHeader, $responseTime); }




	/**
	<fusedoc>
		<description>
			convert html into formatted plain text
			===> require Html2Text library
			===> https://github.com/mtibben/html2text
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
	public static function html2text($html) {
		// load library
		$classPath = dirname($fusebox->config['appPath']).'/lib/html2text/4.0.1/Html2Text.php';
		if ( !class_exists('Html2Text') and !file_exists($classPath) ) {
			self::$error = 'Util::html2text() - Library Html2Text is required';
			return false;
		}
		require_once($classPath);
		// perform conversion
		$prepared = new Html2Text(self::minifyHtml($html));
		$result = $prepared->getText();
		// check result
		if ( $result === false ) {
			self::$error = "Util::html2text() - Error occurred while converting HTML to text";
			return false;
		}
		// success!
		return $result;
	}




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
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function sendMail($mail) {
		global $fusebox;
		// load library
		$classPath = dirname($fusebox->config['appPath']).'/lib/phpmailer/5.2.22/PHPMailerAutoload.php';
		if ( !class_exists('PHPMailer') and !file_exists($classPath) ) {
			self::$error = 'Util::sendMail() - Library PHPMailer is required';
			return false;
		}
		require_once($classPath);
		// load config (when necessary)
		if ( empty($fusebox->config['smtp']) ) {
			self::$error = 'Util::sendMail() - SMTP config is required';
			return false;
		}
		$smtp_config = $fusebox->config['smtp'];
		// validation
		if ( !isset($mail['from']) ) {
			self::$error = 'Util::sendMail() - Mail sender was not specified';
			return false;
		}
		if ( !isset($mail['to']) ) {
			self::$error = 'Util::sendMail() - Mail receipient was not specified';
			return false;
		}
		// start...
		try {
			// init mail object
			$mailer = new PHPMailer(true);
			$mailer->CharSet = 'UTF-8';
			// mail server settings
			$mailer->IsSMTP();  // enable SMTP
			if ( isset($smtp_config['debug'])    ) $mailer->SMTPDebug = $smtp_config['debug'];
			if ( isset($smtp_config['auth'])     ) $mailer->SMTPAuth = $smtp_config['auth'];
			if ( isset($smtp_config['secure'])   ) $mailer->SMTPSecure = $smtp_config['secure'];
			if ( isset($smtp_config['host'])     ) $mailer->Host = $smtp_config['host'];
			if ( isset($smtp_config['port'])     ) $mailer->Port = $smtp_config['port'];
			if ( isset($smtp_config['username']) ) $mailer->Username = $smtp_config['username'];
			if ( isset($smtp_config['password']) ) $mailer->Password = $smtp_config['password'];
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
			if ( !$result ) self::$error = "Util::sendMail() - Error sending mail ({$mailer->ErrorInfo})";
		// catch any error
		} catch (Exception $e) {
			self::$error = "Util::sendMail() - Exception ({$e->getMessage()})";
			return false;
		}
		// result
		return $result;
	}
	// alias method
	public static function sendEmail($mail) { self::sendMail($mail); }




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
			self::$error = "Util::xslt() - {$e->getMessage()}";
			return false;
		}
		// result
		return $result;
	}


} // Util