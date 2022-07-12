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
#### decrypt
#### encrypt
#### hex2rgb
#### html2md
#### html2pdf
#### httpRequest
#### getPage
#### postPage
#### mail / sendEmail / sendMail
#### md2html
#### phpQuery
#### streamFile
#### uuid
#### xls2array

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