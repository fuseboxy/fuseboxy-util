<?php
class Util {


	// properties : library for corresponding methods
	public static $libPath = array(
		'array2xls' => array(
			'PhpOffice\PhpSpreadsheet\Spreadsheet',
			'PhpOffice\PhpSpreadsheet\Writer\Xlsx',
		),
		'html2md' => array(
			__DIR__.'/../../lib/markdownify/2.3.1/src/Parser.php',
			__DIR__.'/../../lib/markdownify/2.3.1/src/Converter.php',
			__DIR__.'/../../lib/markdownify/2.3.1/src/ConverterExtra.php',
		),
		'mail' => array(
			__DIR__.'/../../lib/phpmailer/6.1.6/src/PHPMailer.php',
			__DIR__.'/../../lib/phpmailer/6.1.6/src/Exception.php',
			__DIR__.'/../../lib/phpmailer/6.1.6/src/SMTP.php',
			__DIR__.'/../../lib/phpmailer/6.1.6/src/OAuth.php',
		),
		'md2html' => __DIR__.'/../../lib/parsedown/1.7.4/Parsedown.php',
		'pdf' => __DIR__.'/../../lib/fpdf/1.84/fpdf.php',
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
			export data into excel file (in xlsx format) & save into upload directory
		</description>
		<io>
			<in>
				<!-- config -->
				<structure name="config" scope="$fusebox">
					<string name="uploadDir" />
					<string name="uploadUrl" />
				</structure>
				<!-- parameters -->
				<string name="$filePath" comments="relative path to upload directory" />
				<structure name="$fileData">
					<array name="~worksheetName~">
						<structure name="+" comments="row">
							<string name="~columnName~" />
						</structure>
					</array>
				</structure>
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
	public static function array2xls($filePath, $fileData, $options=[]) {
		// mark start time
		$startTime = microtime(true);
		// validate library
		foreach ( self::$libPath['array2xls'] as $libClass ) {
			if ( !class_exists($path) ) {
				self::$error = "PhpSpreadsheet library is missing ({$libClass})";
				return false;
			}
		}
		// validate config
		if ( empty(F::config('uploadDir')) ) {
			self::$error = 'Config [uploadDir] is required';
			return false;
		} elseif ( empty(F::config('uploadUrl')) ) {
			self::$error = 'Config [uploadUrl] is required';
			return false;
		}
		// validate data format
		if ( !is_array($fileData) ) {
			self::$error = 'Invalid data structure for Excel (Array is required)';
			return false;
		} elseif ( !empty($fileData) ) {
			$firstWorksheetKey = array_key_first($fileData);
			$firstWorksheetData = $fileData[$firstWorksheetKey];
			if ( !is_array($firstWorksheetData) ) {
				self::$error = 'Invalid data structure for Excel (1st level of array is worksheet name, and 2nd level of array is worksheet data)';
				return false;
			}
		}
		// unify directory separator
		$filePath  = str_ireplace('\\', '/', $filePath);
		$uploadDir = str_ireplace('\\', '/', F::config('uploadDir'));
		$uploadUrl = str_ireplace('\\', '/', F::config('uploadUrl'));
		// determine output location
		$result = array(
			'path' => $uploadDir.( ( substr($uploadDir, -1) != '/' ) ? '/' : '' ).$filePath,
			'url'  => $uploadUrl.( ( substr($uploadUrl, -1) != '/' ) ? '/' : '' ).$filePath,
		);
		// create directory (when necessary)
		$dir2create = dirname($result['path']);
		if ( !is_dir($dir2create) and !mkdir($dir2create, 0777, true) ) {
			$err = error_get_last();
			self::$error = $err['message'];
			return false;
		}
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
				<!-- framework config -->
				<structure name="config" scope="$fusebox">
					<structure name="encrypt">
						<string name="key" />
						<string name="vendor" optional="yes" default="mcrypt|openssl" />
						<string name="algo" optional="yes" default="~MCRYPT_RIJNDAEL_256~|BF-ECB" />
						<string name="mode" optional="yes" default="~MCRYPT_MODE_ECB~|0" comments="used as options for openssl" />
						<string name="iv" optional="yes" default="" commens="initial vector" />
					</structure>
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
		// load & fix base config
		$baseConfig = F::config('encrypt');
		if ( empty($baseConfig) ) $baseConfig = array();
		elseif ( is_string($baseConfig) ) $baseConfig = array('key' => $baseConfig);
		// merge base config into custom config
		foreach ( $baseConfig as $baseKey => $baseVal ) $cfg[$baseKey] = $cfg[$baseKey] ?? $baseVal;
		// validation
		if ( empty($cfg['key']) ) {
			self::$error = 'Encryption key is missing';
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
				self::$error = "Invalid action ({$action})";
				return false;
			}
		// catch any error
		} catch (Exception $e) {
			self::$error = 'Crypt error ('.$e->getMessage().')';
			return false;
		}
		// validate result
		if ( $result === '' ) {
			self::$error = "Failed to {$action} data";
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
				self::$error = "Markdownify library is missing ({$path})";
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
			send http-request and get response body
		</description>
		<io>
			<in>
				<structure name="config" scope="$fusebox">
					<string name="httpProxy" optional="yes" />
					<string name="httpsProxy" optional="yes" />
				</structure>
				<string name="$url" />
				<string name="$method" default="GET" example="GET|POST|PUT|DELETE|.." />
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
			self::$error = $pageError;
			return false;
		}
		// success!
		return $pageBody;
	}
	// alias methods
	public static function getPage ($url,             &$httpStatus=null, &$responseHeader=null, &$responseTime=null) { return self::httpRequest('GET',  $url, [],      [], $httpStatus, $responseHeader, $responseTime); }
	public static function postPage($url, $fields=[], &$httpStatus=null, &$responseHeader=null, &$responseTime=null) { return self::httpRequest('POST', $url, $fields, [], $httpStatus, $responseHeader, $responseTime); }




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
						<path name="+" />
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
				self::$error = "PHPMailer library is missing ({$path})";
				return false;
			}
			require_once($path);
		}
		// fix param
		if ( !empty($param['fromName']) and empty($param['from_name']) ) {
			$param['from_name'] = $param['fromName'];
		}
		// load config (when necessary)
		$smtpConfig = F::config('smtp');
		if ( empty($smtpConfig) ) {
			self::$error = 'SMTP config is missing';
			return false;
		}
		// validation
		if ( empty($param['from']) ) {
			self::$error = 'Mail sender was not specified';
			return false;
		}
		if ( empty($param['to']) ) {
			self::$error = 'Mail recipient was not specified';
			return false;
		}
		if ( empty($param['subject']) ) {
			self::$error = 'Mail subject was not specified';
			return false;
		}
		if ( empty($param['body']) ) {
			self::$error = 'Mail body was not specified';
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
			if ( !$result ) self::$error = "Error occurred while sending mail ({$mailer->ErrorInfo})";
		// catch any error
		} catch (Exception $e) {
			self::$error = 'Mail error ('.$e->getMessage().')';
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
			self::$error = "Parsedown library is missing ({$path})";
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
			generate PDF file with provided data
		</description>
		<io>
			<in>
				<!-- config -->
				<structure name="config" scope="$fusebox">
					<string name="uploadDir" />
					<string name="uploadUrl" />
				</structure>
				<!-- parameters -->
				<string name="$filePath" comments="relative path to upload directory" />
				<array name="$fileData">
					<structure name="+">
						<!-- paragraph type -->
						<string name="type" default="div" value="div|p|h1|h2|h3|h4|h5|h6|small|ol|ul|br|hr|img|pagebreak">
							[div]       paragraph (without bottom margin)
							[p]         paragraph (with bottom margin)
							[h1..h6]    headings in biggest to smallest font size
							[small]     small text
							[ol]        order list (1,2,3,4,..)
							[ul]        unorder list (just bullet)
							[br]        line-break
							[hr]        horizontal line
							[img]       image
							[pagebreak] page-break
						</string>
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
						<string name="color" value="ffccaa|#ffccaa|.." />
						<number name="size" optional="yes" oncondition="p|ul|ol" />
						<!-- alignment -->
						<string name="align" default="J" value="J|L|C|R" oncondition="p|h1..h6|small|img" />
						<!-- options -->
						<number name="repeat" optional="yes" default="1" oncondition="br" />
						<number name="height" optional="yes" oncondition="img" />
						<number name="width" optional="yes" oncondition="img" />
						<string name="url" optional="yes" />
					</structure>
				</array>
				<!-- page options -->
				<structure name="$options">
					<string name="paperSize" default="A4" value="A3|A4|A5|~array(width,height)~">
						[A3] 297 x 420
						[A4] 210 x 297
						[A5] 148 x 210
					</string>
					<string name="orientation" default="P" value="P|L" />
					<string name="fontFamily" default="Times" />
					<string name="fontStyle" default="" />
					<string name="fontSize" default="12" />
					<structure name="margin">
						<number name="L|R|T" default="10" comments="1cm" />
					</structure>
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
	public static function pdf($filePath, $fileData, $pageOptions=[]) {
		// default page options
		$pageOptions['paperSize']   = $pageOptions['paperSize']   ?? 'A4';
		$pageOptions['orientation'] = $pageOptions['orientation'] ?? 'P';
		$pageOptions['fontFamily']  = $pageOptions['fontFamily']  ?? 'Times';
		$pageOptions['fontStyle']   = $pageOptions['fontStyle']   ?? '';
		$pageOptions['fontSize']    = $pageOptions['fontSize']    ?? 12;
		$pageOptions['margin']      = $pageOptions['margin']      ?? [];
		$pageOptions['margin']['L'] = $pageOptions['margin']['L'] ?? 10;
		$pageOptions['margin']['R'] = $pageOptions['margin']['R'] ?? 10;
		$pageOptions['margin']['T'] = $pageOptions['margin']['T'] ?? 10;
		// load library
		$path = self::$libPath['pdf'];
		if ( !is_file($path) ) {
			self::$error = "FPDF library is missing ({$path})";
			return false;
		}
		require_once($path);
		// unify directory separator
		$filePath  = str_ireplace('\\', '/', $filePath);
		$uploadDir = str_ireplace('\\', '/', F::config('uploadDir'));
		$uploadUrl = str_ireplace('\\', '/', F::config('uploadUrl'));
		// determine output location
		$result = array(
			'path' => $uploadDir.( ( substr($uploadDir, -1) != '/' ) ? '/' : '' ).$filePath,
			'url'  => $uploadUrl.( ( substr($uploadUrl, -1) != '/' ) ? '/' : '' ).$filePath,
		);
		// create directory (when necessary)
		$dir2create = dirname($result['path']);
		if ( !is_dir($dir2create) and !mkdir($dir2create, 0777, true) ) {
			$err = error_get_last();
			self::$error = $err['message'];
			return false;
		}
		// start!
		$pdf = new FPDF($pageOptions['orientation'], 'mm', $pageOptions['paperSize']);
		// apply page settings
		$pdf->SetMargins($pageOptions['margin']['L'], $pageOptions['margin']['T'], $pageOptions['margin']['R']);
		$pdf->SetFont($pageOptions['fontFamily'], $pageOptions['fontStyle'], $pageOptions['fontSize']);
		// go through each item
		$pdf->AddPage();
		foreach ( $fileData as $item ) {
			// fix : type
			if ( !isset($item['type']) ) $item['type'] = 'p';
			else $item['type'] = strtolower($item['type']);
			// fix : align
			if     ( isset($item['align']) and strtolower($item['align']) == 'left'      ) $item['align'] = 'L';
			elseif ( isset($item['align']) and strtolower($item['align']) == 'right'     ) $item['align'] = 'R';
			elseif ( isset($item['align']) and strtolower($item['align']) == 'center'    ) $item['align'] = 'C';
			elseif ( isset($item['align']) and strtolower($item['align']) == 'justified' ) $item['align'] = 'J';
			elseif ( isset($item['align']) ) $item['align'] = strtoupper($item['align']);
			// fix : color & size
			if ( !isset($item['color']) and isset($item['fontColor']) ) $item['color'] = $item['fontColor'];
			if ( !isset($item['size'])  and isset($item['fontSize'])  ) $item['size']  = $item['fontSize'];
			// fix : value
			if ( isset($item['value']) and is_string($item['value']) and in_array($item['type'], ['ol','ul']) ) $item['value'] = array($item['value']);
			// validation
			$method = "pdf__render{$item['type']}";
			if ( !method_exists(__CLASS__, $method) ) {
				self::$error = 'Unknown type ('.$item['type'].')';
				return false;
			}
			// render item as corresponding type
			$itemResult = self::$method($pdf, $item, $pageOptions);
			if ( $itemResult === false ) return false;
		}
		// save into file
		$pdf->Output('F', $result['path']);
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			render line break to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item">
					<number name="repeat" optional="yes" default="1" />
					<number name="size" optional="yes" />
				</structure>
				<structure name="$pageOptions" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderBR(&$pdf, $item, $pageOptions) {
		$item['repeat'] = $item['repeat'] ?? 1;
		$item['value'] = str_repeat(PHP_EOL, $item['repeat']);
		return self::pdf__renderDiv($pdf, $item, $pageOptions);
	}




	/**
	<fusedoc>
		<description>
			render paragraph to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item">
					<string name="value" />
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="bold" optional="yes" default="false" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<number name="size" optional="yes" default="~pageOptions[fontSize]~" />
					<string name="color" optional="yes" />
				</structure>
				<structure name="$pageOptions">
					<string name="fontFamily" />
					<string name="fontStyle" />
					<number name="fontSize" />
					<structure name="margin">
						<number nam="L|R|T" />
					</structure>
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderDiv(&$pdf, $item, $pageOptions) {
		$pageWidth = ( $pdf->GetPageWidth() - $pageOptions['margin']['L'] - $pageOptions['margin']['R'] );
		// default
		$item['value']     = $item['value']     ?? '';
		$item['size']      = $item['size']      ?? $pageOptions['fontSize'];
		$item['align']     = $item['align']     ?? 'J';
		$item['bold']      = $item['bold']      ?? ( stripos($pageOptions['fontStyle'], 'B') !== false );
		$item['italic']    = $item['italic']    ?? ( stripos($pageOptions['fontStyle'], 'I') !== false );
		$item['underline'] = $item['underline'] ?? ( stripos($pageOptions['fontStyle'], 'U') !== false );
		$item['color']     = $item['color']     ?? '000000';
		// font style of item
		$itemFontStyle = '';
		if ( $item['bold']      ) $itemFontStyle .= 'B';
		if ( $item['italic']    ) $itemFontStyle .= 'I';
		if ( $item['underline'] ) $itemFontStyle .= 'U';
		// font color in RGB
		$color = self::hex2rgb($item['color']);
		if ( $color === false ) return false;
		// line height (for min cell height)
		$lineHeight = $item['size'] / 2;
		// display in specified font size & style
		$pdf->setFont($pageOptions['fontFamily'], $itemFontStyle, $item['size']);
		$pdf->setTextColor($color['r'], $color['g'], $color['b']);
		$pdf->MultiCell($pageWidth, $lineHeight, $item['value'], null, $item['align']);
		// restore to original settings afterward
		$pdf->setFont($pageOptions['fontFamily'], $pageOptions['fontStyle'], $pageOptions['fontSize']);
		$pdf->setTextColor(0, 0, 0);
		// done!
		return true;
	}




	/**
	<fusedoc>
		<description>
			render image to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item">
					<string name="src" />
					<string name="align" optional="yes" comments="L|C|R" />
					<number name="width" optional="yes" />
					<number name="height" optional="yes" />
				</structure>
				<structure name="$pageOptions">
					<structure name="margin">
						<number name="L|R|T" />
					</structure>
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderImg(&$pdf, $item, $pageOptions) {
		$pageWidth = ( $pdf->GetPageWidth() - $pageOptions['margin']['L'] - $pageOptions['margin']['R'] );
		// calculate dimension
		$width  = $item['width']  ?? $pageWidth;
		$height = $item['height'] ?? null;
		// calculate position
		if     ( !isset($item['align']) ) $left = null;
		elseif ( $item['align'] == 'C'  ) $left = ($pageWidth/2) - ($width/2) + $pageOptions['margin']['L'];
		elseif ( $item['align'] == 'R'  ) $left = $pageWidth - $width + $pageOptions['margin']['L'];
		else $left = null;
		// display
		$pdf->Image($item['src'], $left, null, $width, $height);
		// done!
		return true;
	}
	private static function pdf__renderImage(&$pdf, $item, $pageOptions) { return self::pdf__renderImg($pdf, $item, $pageOptions); }




	/**
	<fusedoc>
		<description>
			render heading to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item">
					<string name="value" />
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<string name="color" optional="yes" />
				</structure>
				<structure name="$pageOptions">
					<number name="fontSize" />
				</structure>
				<string name="$hSize" value="h1|h2|h3|h4|h5|h6" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderHeading(&$pdf, $item, $pageOptions, $hSize) {
		$item['bold'] = true;
		if ( $hSize == 'h1' ) $item['size'] = $pageOptions['fontSize'] * 2;
		if ( $hSize == 'h2' ) $item['size'] = $pageOptions['fontSize'] * 1.5;
		if ( $hSize == 'h3' ) $item['size'] = $pageOptions['fontSize'] * 1.17;
		if ( $hSize == 'h5' ) $item['size'] = $pageOptions['fontSize'] * .83;
		if ( $hSize == 'h6' ) $item['size'] = $pageOptions['fontSize'] * .67;
		return self::pdf__renderDiv($pdf, $item, $pageOptions);
	}
	private static function pdf__renderH1(&$pdf, $item, $pageOptions) { return self::pdf__renderHeading($pdf, $item, $pageOptions, 'h1'); }
	private static function pdf__renderH2(&$pdf, $item, $pageOptions) { return self::pdf__renderHeading($pdf, $item, $pageOptions, 'h2'); }
	private static function pdf__renderH3(&$pdf, $item, $pageOptions) { return self::pdf__renderHeading($pdf, $item, $pageOptions, 'h3'); }
	private static function pdf__renderH4(&$pdf, $item, $pageOptions) { return self::pdf__renderHeading($pdf, $item, $pageOptions, 'h4'); }
	private static function pdf__renderH5(&$pdf, $item, $pageOptions) { return self::pdf__renderHeading($pdf, $item, $pageOptions, 'h5'); }
	private static function pdf__renderH6(&$pdf, $item, $pageOptions) { return self::pdf__renderHeading($pdf, $item, $pageOptions, 'h6'); }




	/**
	<fusedoc>
		<description>
			render horizontal line to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item">
				</structure>
				<structure name="$pageOptions">
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderHR(&$pdf, $item, $pageOptions) {
		$pdf->MultiCell(null, 1, null, 'B');
		return true;
	}




	/**
	<fusedoc>
		<description>
			render list to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item">
				</structure>
				<structure name="$pageOptions">
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderList(&$pdf, $item, $pageOptions, $listType) {


	}
	private static function pdf__renderOL(&$pdf, $item, $pageOptions) { return self::pdf__renderList($pdf, $item, $pageOptions, 'ol'); }
	private static function pdf__renderUL(&$pdf, $item, $pageOptions) { return self::pdf__renderList($pdf, $item, $pageOptions, 'ul'); }




	/**
	<fusedoc>
		<description>
			render paragraph to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item">
					<string name="value" />
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="bold" optional="yes" default="false" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<number name="size" optional="yes" default="~pageOptions[fontSize]~" />
					<string name="color" optional="yes" />
				</structure>
				<structure name="$pageOptions">
					<number name="fontSize" />
					<structure name="margin">
						<number nam="L|R|T" />
					</structure>
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderP(&$pdf, $item, $pageOptions) {
		return ( self::pdf__renderDiv($pdf, $item, $pageOptions) and self::pdf__renderBR($pdf, $item, $pageOptions) );
	}




	/**
	<fusedoc>
		<description>
			render page break to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item" />
				<structure name="$pageOptions" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderPageBreak(&$pdf, $item, $pageOptions) {
		$pdf->AddPage();
		return true;
	}




	/**
	<fusedoc>
		<description>
			render small text to PDF
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$item">
					<string name="value" />
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="bold" optional="yes" default="false" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<string name="color" optional="yes" />
				</structure>
				<structure name="$pageOptions">
					<number name="fontSize" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function pdf__renderSmall(&$pdf, $item, $pageOptions) {
		$item['size'] = $options['fontSize'] * .8;
		return self::pdf__renderDiv($pdf, $item, $pageOptions);
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
			self::$error = "phpQuery library is missing ({$path})";
			return false;
		}
		require_once($path);
		// done!
		return phpQuery::newDocument($html);
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
			self::$error = "File not found ({$filePath})";
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
		die();
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
			self::$error = "Invalid UUID version ({$version})";
			return false;
		} elseif ( in_array($version, ['v3','v5']) and ( empty($namespace) or empty($name) ) ) {
			self::$error = 'Arguments [namespace] and [name] are both required';
			return false;
		}
		// determine method to call
		$method = 'self::uuid__'.$version;
		// done!
		return ( $version == 'v4' ) ? call_user_func($method) : call_user_func($method, $namespace, $name);
	}
	// UUID v3
	public static function uuid__v3($namespace, $name) {
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
	public static function uuid__v4() {
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
	public static function uuid__v5($namespace, $name) {
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
	public static function uuid__isValidNamespace($namespace) {
		$valid = ( preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1 );
		if ( !$valid ) {
			self::$error = "Invalid UUID namespace ({$namespace})";
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
				self::$error = "SimpleXLSX library is missing ({$path})";
				return false;
			}
			require_once($path);
		}
		// validation
		$fileExt = strtoupper( pathinfo($file, PATHINFO_EXTENSION) );
		if ( !is_file($file) ) {
			self::$error = "File not found ({$file})";
			return false;
		} elseif ( !in_array($fileExt, ['XLSX','XLS','CSV']) ) {
			self::$error = "File type <strong><em>{$fileExt}</em></strong> is not supported";
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
				self::$error = call_user_func('Simple'.$fileExt.'::parseError');
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
			if ( !$result ) self::$error = libxml_get_last_error();
		// catch any error
		} catch (Exception $e) {
			self::$error = 'XSLT error ('.$e->getMessage().')';
			return false;
		}
		// done!
		return $result;
	}


} // class