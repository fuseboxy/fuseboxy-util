<?php
class Util {


	// property : library for corresponding methods
	private static $libPath = array(
		'array2pdf' => 'Util_PDF',
		'array2xls' => array(
			'PhpOffice\PhpSpreadsheet\Spreadsheet',
			'PhpOffice\PhpSpreadsheet\Writer\Xlsx',
		),
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
		'xls2array' => array(
			__DIR__.'/../../lib/simplexls/0.9.5/src/SimpleXLS.php',
			__DIR__.'/../../lib/simplexlsx/0.8.15/src/SimpleXLSX.php',
		),
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
				<array name="$fileData" />
				<string name="$filePath" optional="yes" comments="relative path to upload directory" />
				<structure name="$pageOptions" optional="yes" />
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
	public static function array2pdf($fileData, $filePath='', $pageOptions=[]) {
		// validate library
		foreach ( self::$libPath['array2pdf'] as $libClass ) {
			if ( !class_exists($libClass) ) {
				self::$error = "[Util::array2pdf] Library is missing ({$libClass})";
				return false;
			}
		}
		// proceed to transform
		$result = Util_PDF::array2pdf($fileData, $filePath, $pageOptions);
		if ( $result === false ) {
			self::$error = '[Util::array2pdf] '.Util_PDF::error();
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
				<string name="$filePath" comments="relative path to upload directory" />
				<structure name="$options">
					<boolean name="showRecordCount" optional="yes" />
					<structure name="columnWidth" optional="yes">
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
	public static function array2xls($fileData, $filePath, $options=[]) {
		// fix swapped parameters
		if ( is_string($fileData) and is_array($filePath) ) list($fileData, $filePath) = array($filePath, $fileData);
		// mark start time
		$startTime = microtime(true);
		// validate library
		foreach ( self::$libPath['array2xls'] as $libClass ) {
			if ( !class_exists($libClass) ) {
				self::$error = "[Util::array2xls] PhpSpreadsheet library is missing ({$libClass})<br />Please use <em>composer</em> to install <strong>phpoffice/phpspreadsheet</strong> into your project";
				return false;
			}
		}
		// validate data format
		if ( !is_array($fileData) ) {
			self::$error = '[Util::array2xls] Invalid data structure for Excel (Array is required)';
			return false;
		} elseif ( !empty($fileData) ) {
			$firstWorksheetKey = array_key_first($fileData);
			$firstWorksheetData = $fileData[$firstWorksheetKey];
			if ( !is_array($firstWorksheetData) ) {
				self::$error = '[Util::array2xls] Invalid data structure for Excel (1st level of array is worksheet name, and 2nd level of array is worksheet data)';
				return false;
			}
		}
		// determine output location
		$result = array('path' => self::uploadDir($filePath), 'url'  => self::uploadUrl($filePath));
		if ( $result['path'] === false or $result['url'] === false ) return false;
		// create blank spreadsheet
		$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
		// go through each worksheet
		$wsIndex = 0;
		foreach ( $fileData as $worksheetName => $worksheet ) {
			// show number of records at worksheet name (when necessary)
			if ( !empty($options['showRecordCount']) and !empty($worksheet) ) {
				$worksheetName .= ' ('.count($worksheet).')';
			}
			// create worksheet
			if ( $wsIndex > 0 ) $spreadsheet->createSheet();
			$spreadsheet->setActiveSheetIndex($wsIndex);
			$activeSheet = $spreadsheet->getActiveSheet();
			$activeSheet->setTitle($worksheetName);
			// all column names (from A to ZZ)
			$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$colNames = str_split($alphabet);
			for ( $i=0; $i<strlen($alphabet); $i++ ) {
				for ( $j=0; $j<strlen($alphabet); $j++ ) {
					$colNames[] = $alphabet[$i].$alphabet[$j];
				}
			}
			// column format
			$activeSheet->getStyle('A:ZZ')->getFont()->setSize(10);
			$activeSheet->getStyle('A:ZZ')->getAlignment()->setWrapText(true);
			$activeSheet->getStyle('A:ZZ')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
			$activeSheet->getStyle('A:ZZ')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
			// header format
			$activeSheet->getStyle('1:1')->getFont()->setBold(true);
			$activeSheet->getStyle('1:1')->getAlignment()->setWrapText(true);
			$activeSheet->getStyle('1:1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
			$activeSheet->getStyle('1:1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFDDDDDD');
			// column width (when necessary)
			if ( !empty($options['columnWidth'][$worksheetName]) ) {
				foreach ( $options['columnWidth'][$worksheetName] as $key => $val ) {
					$activeSheet->getColumnDimension($colNames[$key])->setWidth($val);
				}
			}
			// output header
			if ( !empty($worksheet) ) {
				$row = $worksheet[0];
				$colIndex = 0;
				foreach ( $row as $key => $val ) {
					$activeSheet->setCellValue($colNames[$colIndex].'1', $key);
					$colIndex++;
				}
			}
			// output each row of data
			foreach ( $worksheet as $rowIndex => $row ) {
				$rowNumber = $rowIndex + 2;
				// go through each column
				$colIndex = 0;
				foreach ( $row as $key => $val ) {
					$activeSheet->setCellValue($colNames[$colIndex].$rowNumber, $val);
					$colIndex++;
				} // foreach-col
			} // foreach-row
			$wsIndex++;
			// focus first cell (when finished)
			$activeSheet->getStyle('A1');
		} // foreach-worksheet
		// mark end time
		$endTime = microtime(true);
		$et = round(($endTime-$startTime)*1000);
		// show execution time at last worksheet
		$spreadsheet->createSheet();
		$spreadsheet->setActiveSheetIndex( count($fileData) );
		$activeSheet = $spreadsheet->getActiveSheet();
		$activeSheet->setTitle('et ('.$et.'ms)');
		// focus first worksheet (when finished)
		$spreadsheet->setActiveSheetIndex(0);
		// write to report
		$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$writer->save($result['path']);
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
			self::$error = '[Util::crypt] Encryption key is missing';
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
				self::$error = "[Util::crypt] Invalid action ({$action})";
				return false;
			}
		// catch any error
		} catch (Exception $e) {
			self::$error = '[Util::crypt] Crypt error ('.$e->getMessage().')';
			return false;
		}
		// validate result
		if ( $result === '' ) {
			self::$error = "[Util::crypt] Failed to {$action} data";
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
				self::$error = "[Util::html2md] Markdownify library is missing ({$path})";
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
				<!-- parameters -->
				<string name="$html" />
				<string name="$filePath" optional="yes" comments="relative path to upload directory" />
				<structure name="$pageOptions" optional="yes" />
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
	public static function html2pdf($html, $filePath=null, $options=[]) {
		// validate library
		foreach ( self::$libPath['html2pdf'] as $libClass ) {
			if ( !class_exists($libClass) ) {
				self::$error = "[Util::html2pdf] Library is missing ({$libClass})";
				return false;
			}
		}
		// proceed to transform
		$result = Util_PDF::array2pdf($html, $filePath, $pageOptions);
		if ( $result === false ) {
			self::$error = '[Util::html2pdf] '.Util_PDF::error();
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
				<string name="~return~" optional="yes" oncondition="success" comments="page response" />
				<string name="$httpStatus" optional="yes" />
				<string name="$responseHeader" optional="yes" oncondition="success" />
				<number name="$responseTime" optional="yes" oncondition="success" />
				<boolean name="~return~" value="false" optional="yes" oncondition="failure" />
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
			self::$error = '[Util::httpRequest] '.$pageError;
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
				self::$error = "[Util::mail] PHPMailer library is missing ({$path})";
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
			self::$error = '[Util::mail] SMTP config is missing';
			return false;
		// validate parameters
		} elseif ( empty($param['from']) ) {
			self::$error = '[Util::mail] Mail sender was not specified';
			return false;
		} elseif ( empty($param['to']) ) {
			self::$error = '[Util::mail] Mail recipient was not specified';
			return false;
		} elseif ( empty($param['subject']) ) {
			self::$error = '[Util::mail] Mail subject was not specified';
			return false;
		} elseif ( empty($param['body']) ) {
			self::$error = '[Util::mail] Mail body was not specified';
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
			if ( !$result ) self::$error = "[Util::mail] Error occurred while sending mail ({$mailer->ErrorInfo})";
		// catch any error
		} catch (Exception $e) {
			self::$error = '[Util::mail] Mail error ('.$e->getMessage().')';
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
			self::$error = "[Util::md2html] Parsedown library is missing ({$path})";
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
			self::$error = "[Util::phpQuery] phpQuery library is missing ({$path})";
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
			self::$error = "[Util::streamFile] File not found ({$filePath})";
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
			self::$error = '[Util::uploadDir] Config [uploadDir] is required';
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
		$dir2create = dirname($result);
		if ( !is_dir($dir2create) and !mkdir($dir2create, 0777, true) ) {
			$err = error_get_last();
			self::$error = '[Util::uploadDir] Error creating directory ('.$err['message'].')';
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
			self::$error = '[Util::uploadUrl] Config [uploadUrl] is required';
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
			self::$error = "[Util::uuid] Invalid UUID version ({$version})";
			return false;
		} elseif ( in_array($version, ['v3','v5']) and ( empty($namespace) or empty($name) ) ) {
			self::$error = '[Util::uuid] Arguments [namespace] and [name] are both required';
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
			self::$error = "[Util::uuid] Invalid UUID namespace ({$namespace})";
			return false;
		}
		return true;
	}




	/**
	<fusedoc>
		<description>
			convert csv/xls/xlsx to array
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
		// default options
		$options['startRow'] = $options['startRow'] ?? 1;
		$options['worksheet'] = $options['worksheet'] ?? 0;
		$options['firstRowAsHeader'] = $options['firstRowAsHeader'] ?? true;
		$options['convertHeaderCase'] = $options['convertHeaderCase'] ?? true;
		// load library
		foreach ( self::$libPath['xls2array'] as $path ) {
			if ( !is_file($path) ) {
				self::$error = "[Util::xls2array] SimpleXLSX library is missing ({$path})";
				return false;
			}
			require_once($path);
		}
		// validation
		$fileExt = strtoupper( pathinfo($file, PATHINFO_EXTENSION) );
		if ( !is_file($file) ) {
			self::$error = "[Util::xls2array] File not found ({$file})";
			return false;
		} elseif ( !in_array($fileExt, ['XLSX','XLS','CSV']) ) {
			self::$error = "[Util::xls2array] File type <strong><em>{$fileExt}</em></strong> is not supported";
			return false;
		}
		// parse csv by php
		if ( $fileExt == 'CSV' ) {
			$data = file_get_contents($file);
			$data = mb_convert_encoding($data, 'UTF-8', mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true));
			$data = array_map('str_getcsv', explode(PHP_EOL, $data));
		// parse excel by library
		} else {
			$data = call_user_func('Simple'.$fileExt.'::parse', $file);
			if ( $data === false ) {
				self::$error = '[Util::xls2array] '.call_user_func('Simple'.$fileExt.'::parseError');
				return false;
			}
		}
		// extract data from specific worksheet (when necessary)
		if ( method_exists($data, 'rows') ) $data = $data->rows($options['worksheet']);
		for ( $i=0; $i<($options['startRow']-1); $i++ ) if ( isset($data[$i]) ) unset($data[$i]);
		$data = array_values($data);
		// validation
		// ===> simply return when no data
		// ===> simply return when no need to apply first row as header
		if ( empty($data) or !$options['firstRowAsHeader'] ) return $data;
		// get column name from first row
		$colNames = $data[0];
		unset($data[0]);
		$data = array_values($data);
		// convert column name into snake case
		if ( $options['convertHeaderCase'] ) {
			$colNames = array_map('strtolower', $colNames);
			foreach ( $colNames as $i => $val ) {
				$val = strtolower($val);
				$val = preg_replace( '/[^a-z0-9]/i', ' ', $val);
				$val = preg_replace('!\s+!', ' ', $val);
				$val = str_replace(' ', '_', $val);
				$val = trim($val, '_');
				$colNames[$i] = $val;
			}
		}
		// go through each row and create new record
		$result = array();
		foreach ( $data as $row => $rowData ) {
			$item = array();
			foreach ( $colNames as $colIndex => $colName ) {
				$item[$colName] = isset($rowData[$colIndex]) ? $rowData[$colIndex] : '';
			}
			$result[] = $item;
		}
		// clean-up data
		foreach ( $result as $row => $rowData ) {
			foreach ( $rowData as $col => $val ) {
				$result[$row][$col] = trim($val);
			}
		}
		// done!
		return $result;
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
			if ( !$result ) self::$error = '[Util::xslt] '.libxml_get_last_error();
		// catch any error
		} catch (Exception $e) {
			self::$error = '[Util::xslt] XSLT error ('.$e->getMessage().')';
			return false;
		}
		// done!
		return $result;
	}


} // class