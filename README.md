Fuseboxy Util
=============

Installation
------------

#### By Composer

#### Manually

Configuration
-------------

#### With Fuseboxy Framework

#### Without Fuseboxy Framework

FUSEBOXY_UTIL_SMTP
FUSEBOXY_UTIL_ENCRYPT
FUSEBOXY_UTIL_HTTP_PROXY
FUSEBOXY_UTIL_HTTPS_PROXY
FUSEBOXY_UTIL_UPLOAD_DIR
FUSEBOXY_UTIL_UPLOAD_URL

Methods
-------

#### array2pdf

#### array2xls
```
<io>
	<in>
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
```

#### decrypt
```
<io>
	<in>
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
```

#### encrypt
```
<io>
	<in>
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
```

#### hex2rgb
```
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
```

#### html2md
```
<io>
	<in>
		<string name="$html" />
	</in>
	<out>
		<string name="~return~" />
	</out>
</io>
```

#### html2pdf

#### httpRequest
```
<io>
	<in>
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
```

#### getPage
```
<io>
	<in>
		<string name="$url" />
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
```

#### postPage
```
<io>
	<in>
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
```

#### mail / sendEmail / sendMail
```
<io>
	<in>
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
```

#### md2html
```
<io>
	<in>
		<string name="$md" />
	</in>
	<out>
		<string name="~return~" />
	</out>
</io>
```

#### phpQuery
```
<io>
	<in>
		<string name="$html" />
	</in>
	<out>
		<object name="~return~" />
	</out>
</io>
```

#### streamFile
```
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
```

#### uuid
```
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
```

#### xls2array
```
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
```

#### xslt
```
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
```