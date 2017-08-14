<?php /*
<fusedoc>
	<description>
		Core component of Fuseboxy framework
	</description>
	<properties name="version" value="1.0.3" />
	<io>
		<in>
			<string name="$mode" scope="Framework" optional="yes" comments="for unit-test of helper" />
			<string name="$configPath" scope="Framework" optional="yes" default="../../../config/fusebox_config.php" />
			<string name="$helperPath" scope="Framework" optional="yes" default="./F.php" />
		</in>
		<out />
	</io>
</fusedoc>
*/
class Framework {


	// constant : mode
	const FUSEBOX_UNIT_TEST          = 101;
	// constant : error
	const FUSEBOX_CONFIG_NOT_FOUND   = 501;
	const FUSEBOX_CONFIG_NOT_DEFINED = 502;
	const FUSEBOX_HELPER_NOT_FOUND   = 503;
	const FUSEBOX_HELPER_NOT_DEFINED = 504;
	const FUSEBOX_MISSING_CONFIG     = 505;
	const FUSEBOX_INVALID_CONFIG     = 506;
	const FUSEBOX_ERROR              = 507;
	const FUSEBOX_PAGE_NOT_FOUND     = 508;
	// constant : others
	const FUSEBOX_REDIRECT           = 901;


	// settings
	public static $mode;
	public static $configPath = __DIR__.'/../../config/fusebox_config.php';
	public static $helperPath = __DIR__.'/F.php';


	// initiate fusebox-api variable
	public static function createAPIObject() {
		global $fusebox;
		$fusebox = new StdClass();
	}


	// load config and assign default value
	public static function loadConfig() {
		global $fusebox;
		// validate config file
		if ( file_exists(Framework::$configPath) ) {
			$fusebox->config = include Framework::$configPath;
		} else {
			if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("Config file not found (".Framework::$configPath.")", self::FUSEBOX_CONFIG_NOT_FOUND);
		}
		if ( !is_array($fusebox->config) ) {
			if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("Config file must return an Array", self::FUSEBOX_CONFIG_NOT_DEFINED);
		}
		// define config default value (when necessary)
		$fusebox->config['commandVariable'] = isset($fusebox->config['commandVariable']) ? $fusebox->config['commandVariable'] : 'fuseaction';
		$fusebox->config['commandDelimiter'] = isset($fusebox->config['commandDelimiter']) ? $fusebox->config['commandDelimiter'] : '.';
		$fusebox->config['appPath'] = isset($fusebox->config['appPath']) ? $fusebox->config['appPath'] : (str_replace('\\', '/', dirname(dirname(__FILE__))).'/');
	}


	// load framework utility component
	// ===> when {$fusebox} api is ready
	public static function loadHelper() {
		global $fusebox;
		// check helper path
		if ( !file_exists(Framework::$helperPath) ) {
			if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("Helper class file not found (".Framework::$helperPath.")", self::FUSEBOX_HELPER_NOT_FOUND);
		// load helper
		} elseif ( !class_exists('F') ) {
			include Framework::$helperPath;
		}
		// validate after load
		if ( !class_exists('F') ) {
			if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("Helper class (F) not defined", self::FUSEBOX_HELPER_NOT_DEFINED);
		}
	}


	// validate config
	public static function validateConfig() {
		global $fusebox;
		// check required config
		foreach ( array('commandVariable','commandDelimiter','appPath') as $key ) {
			if ( empty($fusebox->config[$key]) ) {
				if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
				throw new Exception("Fusebox config variable {{$key}} is required", self::FUSEBOX_MISSING_CONFIG);
			}
		}
		// check command-variable
		if ( in_array(strtolower($fusebox->config['commandVariable']), array('controller','action')) ) {
			if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("Config {commandVariable} can not be 'controller' or 'action'", self::FUSEBOX_INVALID_CONFIG);

		}
		// check command-delimiter
		if ( !in_array($fusebox->config['commandDelimiter'], array('.', '-', '_')) ) {
			if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception('Config {commandDelimiter} can only be dot (.), dash (-), or underscore (_)', self::FUSEBOX_INVALID_CONFIG);
		}
		// check app-path
		if ( !is_dir($fusebox->config['appPath']) ) {
			if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("Directory specified in config {appPath} does not exist ({$fusebox->config['appPath']})", self::FUSEBOX_INVALID_CONFIG);
		}
		// check error-controller
		if ( !empty($fusebox->config['errorController']) and !is_file($fusebox->config['errorController']) ) {
			if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
			throw new Exception("Error controller does not exist ({$fusebox->config['errorController']})", self::FUSEBOX_INVALID_CONFIG);
		}
	}


	// api variables
	public static function setMyself() {
		global $fusebox;
		if ( !empty($fusebox->config['urlRewrite']) ) {
			$fusebox->self = dirname($_SERVER['SCRIPT_NAME']);
			$fusebox->self = str_replace('\\', '/', $fusebox->self);
			if ( substr($fusebox->self, -1) != '/' ) $fusebox->self .= '/';
			$fusebox->myself = $fusebox->self;
		} else {
			$fusebox->self = $_SERVER['SCRIPT_NAME'];
			$fusebox->myself = "{$fusebox->self}?{$fusebox->config['commandVariable']}=";
		}
	}


	// auto-load files or directories (non-recursive)
	public static function autoLoad() {
		global $fusebox;
		if ( !empty($fusebox->config['autoLoad']) ) {
			foreach ( $fusebox->config['autoLoad'] as $originalPath ) {
				// check type
				$isWildcard = ( strpos($originalPath, '*') !== false );
				$isExistingDir = ( file_exists($originalPath) and is_dir($originalPath) );
				$isExistingFile = ( file_exists($originalPath) and is_file($originalPath) );
				// adjust argument
				$path = $originalPath;
				if ( $isExistingDir ) $path .= "/*";
				$path = str_replace("\\", "/", $path);
				$path = str_replace("//", "/", $path);
				// throw error when auto-load path not found
				if ( !$isWildcard and !$isExistingDir and !$isExistingFile ) {
					if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
					throw new Exception("Auto-load path not found ({$path})", self::FUSEBOX_INVALID_CONFIG);
				}
				// include all file specified
				foreach ( glob($path) as $file ) {
					if ( is_file($file) ) require_once $file;
				}
			}
		}
	}


	// extract command and url variables from beauty-url
	// ===> work closely with {$fusebox->config['route']} and F::url()
	public static function urlRewrite() {
		global $fusebox;
		// request <http://{HOST}/{APP}/foo/bar> will have <REQUEST_URI=/{APP}/foo/bar>
		// request <http://{HOST}/foo/bar> will have <REQUEST_URI=/foo/bar>
		// request <http://{HOST}/foo/bar?a=1&b=2> will have <REQUEST_URI=/foo/bar?a=1&b=2>
		$isRoot = dirname($_SERVER['SCRIPT_NAME']) == rtrim($_SERVER['REQUEST_URI'], '/');
		// only process when necessary
		if ( !empty($fusebox->config['urlRewrite']) and !$isRoot ) {
			// cleanse the route config (and keep the sequence)
			if ( isset($fusebox->config['route']) ) {
				$fixedRoute = array();
				foreach ( $fusebox->config['route'] as $urlPattern => $qsReplacement ) {
					// clean unnecessary spaces
					$urlPattern = trim($urlPattern);
					$qsReplacement = trim($qsReplacement);
					// prepend forward-slash (when necessary)
					if ( substr($urlPattern, 0, 1) !== '/' and substr($urlPattern, 0, 2) != '\\/' ) {
						$urlPattern = '/'.$urlPattern;
					}
					// remove multi-(forward-)slash
					do { $urlPattern = str_replace('//', '/', $urlPattern); } while ( strpos($urlPattern, '//') !== false );
					// escape forward-slash
					$urlPattern = str_replace('/', '\\/', $urlPattern);
					// fix double-escaped forward-slash
					$urlPattern = str_replace('\\\\/', '\\/', $urlPattern);
					// put into container
					$fixedRoute[$urlPattern] = $qsReplacement;
				}
				$fusebox->config['route'] = $fixedRoute;
			}
			// start to parse the path
			$qs = rtrim($_SERVER['REQUEST_URI'], '/');
			// (1) unify slash
			$qs = str_replace('\\', '/', $qs);                                   // e.g.  /my/site//foo\bar\999??a=1&b=2&&c=3&  ------->  /my/site//foo/bar/999??a=1&b=2&&c=3&
			// (2) dupe slash, question-mark, and and-sign
			$qs = preg_replace('/\/+/', '/', $qs);                               // e.g.  /my/site//foo/bar/999??a=1&b=2&&c=3&  ------->  /my/site/foo/bar/999??a=1&b=2&&c=3&
			$qs = preg_replace('/\?+/', '?', $qs);                               // e.g.  /my/site/foo/bar/999??a=1&b=2&&c=3&  -------->  /my/site/foo/bar/999?a=1&b=2&&c=3&
			$qs = preg_replace('/&+/' , '&', $qs);                               // e.g.  /my/site/foo/bar/999??a=1&b=2&&c=3&  -------->  /my/site/foo/bar/999?a=1&b=2&c=3&
			// (3) extract (potential) query-string from path
			$baseDir = dirname($_SERVER['SCRIPT_NAME']);                         // e.g.  /my/site/index.php  ------------------------->  \my\site
			$baseDir = str_replace('\\', '/', $baseDir);                         // e.g.  \my\site  ----------------------------------->  /my/site
			if ( substr($baseDir, -1) != '/' ) $baseDir .= '/';                  // e.g.  /my/site  ----------------------------------->  /my/site/
			$baseDirPattern = preg_quote($baseDir, '/');
			$qs = preg_replace("/{$baseDirPattern}/", '', $qs, 1);               // e.g.  /my/site/foo/bar/999?a=1&b=2&c=3&  ---------->  foo/bar/999?a=1&b=2&c=3&
			// (4) append leading slash to path
			if ( substr($qs, 0, 1) != '/' ) $qs = '/'.$qs;                       // e.g.  foo/bar/999?a=1&b=2&c=3&  ------------------->  /foo/bar/999?a=1&b=2&c=3&
			// (5) check if there is route match, and apply the first match
			$hasRouteMatch = false;
			$routes = F::config('route') ? F::config('route') : array();
			foreach ( $routes as $urlPattern => $qsReplacement ) {               // e.g.  /foo/bar/([0-9]+)(.*)  ---------------------->  fuseaction=foo.bar&xyz=$1&$2
				// if path-like-query-string match the route pattern...
				if ( !$hasRouteMatch and preg_match("/{$urlPattern}/", $qs) ) {
					// turn it into true query-string
					$qs = preg_replace("/{$urlPattern}/", $qsReplacement, $qs);  // e.g.  /foo/bar/999?a=1&b=2&c=3&  ------------------>  fuseaction=foo.bar&xyz=999?a=1&b=2&c=3&
					// mark flag
					$hasRouteMatch = true;
				}
			}
			// (6) unify query-string delim (replace first question-mark only)
			$qs = preg_replace('/\?/', '&', $qs, 1);                             // e.g.  /foo/bar/999?a=1&b=2&c=3&  ------------------>  /foo/bar/999&a=1&b=2&c=3&
			// (7) if match none of the route, then turn path into query-string
			if ( !$hasRouteMatch ) {
				$qs = str_replace('/', '&', trim($qs, '/'));
				$arr = explode('&', $qs);
				if ( count($arr) == 1 and $arr[0] == '' ) $arr = array();
				$qs = '';
				// turn path-like-query-string into query-string
				// ===> extract (at most) first two elements for command-variable
				// ===> treat as command-variable when element was unnamed (no equal-sign)
				// ===> treat as url-param when element was named (has equal-sign)
				if ( count($arr) and strpos($arr[0], '=') === false ) {  // 1st time
					$qs .= ( $fusebox->config['commandVariable'] . '=' . array_shift($arr) );
				}
				if ( count($arr) and strpos($arr[0], '=') === false ) {  // 2nd time
					$qs .= ( $fusebox->config['commandDelimiter'] . array_shift($arr) );
				}
				// join remaining elements into query-string
				$qs .= ( '&' . implode('&', $arr) );
			}
			// (8) remove unnecessary query-string delimiter
			$qs = trim($qs, '&');                                                // e.g.  fuseaction=foo.bar&xyz=999?a=1&b=2&c=3&  ---->  fuseaction=foo.bar&xyz=999&a=1&b=2&c=3
			// (9) dupe query-string delimiter again
			$qs = preg_replace('/&+/' , '&', $qs);
			// (10) put parameters of query-string into GET scope
			$qsArray = explode('&', $qs);
			foreach ( $qsArray as $param ) {
				$param = explode('=', $param, 2);
				$paramKey = isset($param[0]) ? urldecode($param[0]) : '';
				$paramVal = isset($param[1]) ? urldecode($param[1]) : '';
				if ( !empty($paramKey) ) {
					// simple parameter
					if ( strpos($paramKey, '[') === false ) {
						$_GET[$paramKey] = $paramVal;
					// array parameter
					} else {
						$arrayDepth = substr_count($paramKey, '[');
						$arrayKeys = explode('[', str_replace(']', '', $paramKey));
						foreach ( $arrayKeys as $i => $singleArrayKey ) {
							if ( $i == 0 ) $pointer = &$_GET;
							if ( $singleArrayKey != '' ) {
								$pointer[$singleArrayKey] = isset($pointer[$singleArrayKey]) ? $pointer[$singleArrayKey] : array();
								$pointer = &$pointer[$singleArrayKey];
							} else {
								$pointer[count($pointer)] = isset($pointer[count($pointer)]) ? $pointer[count($pointer)] : array();
								$pointer = &$pointer[count($pointer)-1];
							}
							if ( $i+1 == count($arrayKeys) ) $pointer = $paramVal;
						}
						unset($pointer);
					}
				}
			}
			// (11) update REQUEST and SERVER scopes as well
			$_REQUEST += $_GET;
			$_SERVER['QUERY_STRING'] = $qs;
		} // if-url-rewrite
	}


	// formUrl2arguments
	// ===> default merging POST & GET scope
	// ===> user could define array of scopes to merge
	public static function formUrl2arguments() {
		global $fusebox;
		global $arguments;
		if ( isset($fusebox->config['formUrl2arguments']) and !empty($fusebox->config['formUrl2arguments']) ) {
			global $arguments;
			// config default
			if ( $fusebox->config['formUrl2arguments'] === true or $fusebox->config['formUrl2arguments'] === 1 ) {
				$fusebox->config['formUrl2arguments'] = array($_GET, $_POST);
			}
			// copy variables from scope to container (precedence = first-come-first-serve)
			if ( is_array($fusebox->config['formUrl2arguments']) ) {
				$arguments = array();
				foreach ( $fusebox->config['formUrl2arguments'] as $scope ) $arguments += $scope;
			// validation
			} else {
				if ( !headers_sent() ) header("HTTP/1.0 500 Internal Server Error");
				throw new Exception("Config {formUrl2arguments} must be Boolean or Array", self::FUSEBOX_INVALID_CONFIG);
			}
		}
	}


	// get controller & action out of command
	public static function setControllerAction() {
		global $fusebox;
		// if no command was defined, use {defaultCommand} in config
		if ( F::isCLI() ) {
			$command = !empty($argv[1]) ? $argv[1] : $fusebox->config['defaultCommand'];
		} elseif ( !empty($_GET[$fusebox->config['commandVariable']]) ) {
			$command = $_GET[$fusebox->config['commandVariable']];
		} elseif ( !empty($_POST[$fusebox->config['commandVariable']]) ) {
			$command = $_POST[$fusebox->config['commandVariable']];
		} elseif ( !empty($fusebox->config['defaultCommand']) ) {
			$command = $fusebox->config['defaultCommand'];
		} else {
			$command = false;
		}
		// parse controller & action
		$parsed = F::parseCommand($command);
		// modify fusebox-api variable
		$fusebox->controller = $parsed['controller'];
		$fusebox->action = $parsed['action'];
	}


	// run specific controller and action
	public static function run() {
		global $fusebox;
		global $arguments;
		// main process...
		self::createAPIObject();
		self::loadConfig();
		self::validateConfig();
		self::loadHelper();
		self::setMyself();
		self::autoLoad();
		self::urlRewrite();
		self::formUrl2arguments();
		self::setControllerAction();
		// do not run when no controller specified
		// ===> e.g. when default-command is empty
		// ===> otherwise, load controller and run!
		if ( !empty($fusebox->controller) ) {
			$__controllerPath = "{$fusebox->config['appPath']}/controller/{$fusebox->controller}_controller.php";
			F::pageNotFound( !file_exists($__controllerPath) );
			include $__controllerPath;
		}
	}


} // Framework