<?php
class Util_PDF {


	// property : library for corresponding methods
	private static $libPath = array(
		'array2pdf' => 'Mpdf\Mpdf',
		'html2pdf' => 'Mpdf\Mpdf'
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
				<!-- parameters -->
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
						<string name="color" value="ffccaa|#ffccaa|.." />
						<number name="size" optional="yes" oncondition="div|p|ul|ol|br" />
						<!-- alignment -->
						<string name="align" default="J" value="J|L|C|R" oncondition="div|p|h1..h6|small|img" />
						<!-- options -->
						<number name="repeat" optional="yes" default="1" oncondition="br" />
						<number name="height" optional="yes" oncondition="img" />
						<number name="width" optional="yes" oncondition="img" />
						<string name="bullet" optional="yes" oncondition="ol|ul" />
						<number name="indent" optional="yes" />
						<string name="url" optional="yes" />
					</structure>
				</array>
				<string name="$filePath" optional="yes" comments="relative path to upload directory" />
				<!-- page options -->
				<structure name="$pageOptions" optional="yes">
					<string name="paperSize" default="A4" value="A3|A4|A5|~array(width,height)~">
						[A3] 297 x 420
						[A4] 210 x 297
						[A5] 148 x 210
					</string>
					<string name="orientation" default="P" value="P|L" />
					<string name="fontFamily" default="Times|Big5|GB|.." />
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
	public static function array2pdf($fileData, $filePath='', $pageOptions=[]) {
		// fix swapped parameters (when necessary)
		if ( is_string($fileData) and is_array($filePath) ) list($fileData, $filePath) = array($filePath, $fileData);
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
		// validate library
		$libClass = self::$libPath['html2pdf'];
		if ( !class_exists($libClass) ) {
			self::$error = "[Util::array2pdf] mPDF library is missing ({$libClass})<br />Please use <em>composer</em> to install <strong>mpdf/mpdf</strong> into your project";
			return false;
		}
		// determine output location
		$result = array('path' => self::uploadDir($filePath), 'url'  => self::uploadUrl($filePath));
		if ( $result['path'] === false or $result['url'] === false ) return false;
		// start!
		$pdf = new Mpdf\Mpdf([ 'mode' => 'utf-8', 'format' => $pageOptions['paperSize'] ]);
		$pdf->SetFont($pageOptions['fontFamily'], $pageOptions['fontStyle'], $pageOptions['fontSize']);
		if ( self::array2pdf__newBlankPage($pdf, $pageOptions) === false ) return false;
		// go through each item
		foreach ( $fileData as $item ) {
			// fix : type
			if ( !isset($item['type']) ) $item['type'] = 'div';
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
			// fix : list value
			if ( isset($item['value']) and is_string($item['value']) and in_array($item['type'], ['ol','ul']) ) $item['value'] = array($item['value']);
			// validation
			$method = "array2pdf__render{$item['type']}";
			if ( !method_exists(__CLASS__, $method) ) {
				self::$error = '[Util::array2pdf] Unknown type ('.$item['type'].')';
				return false;
			}
			// render item as corresponding type
			$itemResult = self::$method($pdf, $item, $pageOptions);
			if ( $itemResult === false ) return false;
		}
		// view as PDF directly (when file path not specified)
		if ( empty($filePath) ) die( $pdf->Output() );
		// save into file
		$pdf->Output($result['path']);
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			start a new blank page
		</description>
		<io>
			<in>
				<object name="&$pdf" comments="reference" />
				<structure name="$pageOptions" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function array2pdf__newBlankPage(&$pdf, $pageOptions) {
		$pdf->AddPage($pageOptions['orientation'], null, null, null, null, $pageOptions['margin']['L'], $pageOptions['margin']['R']);
		return true;
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
	private static function array2pdf__renderBR(&$pdf, $item, $pageOptions) {
		$item['repeat'] = $item['repeat'] ?? 1;
		$item['value'] = str_repeat(PHP_EOL, $item['repeat']);
		return self::array2pdf__renderDiv($pdf, $item, $pageOptions);
	}




	/**
	<fusedoc>
		<description>
			render paragraph (without bottom margin) to PDF
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
					<number name="indent" optional="yes" />
					<string name="indentText" optional="yes" />
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
	private static function array2pdf__renderDiv(&$pdf, $item, $pageOptions) {
		// default
		$item['value']     = $item['value']     ?? '';
		$item['size']      = $item['size']      ?? $pageOptions['fontSize'];
		$item['align']     = $item['align']     ?? '';
		$item['bold']      = $item['bold']      ?? ( stripos($pageOptions['fontStyle'], 'B') !== false );
		$item['italic']    = $item['italic']    ?? ( stripos($pageOptions['fontStyle'], 'I') !== false );
		$item['underline'] = $item['underline'] ?? ( stripos($pageOptions['fontStyle'], 'U') !== false );
		$item['color']     = $item['color']     ?? '#000000';
		// font style of item
		$itemFontStyle = '';
		if ( $item['bold']      ) $itemFontStyle .= 'B';
		if ( $item['italic']    ) $itemFontStyle .= 'I';
		if ( $item['underline'] ) $itemFontStyle .= 'U';
		// font color in RGB
		$color = self::hex2rgb($item['color']);
		if ( $color === false ) return false;
		// min cell height
		$contentHeight = $item['size'] / 2;
		// change to specified font size & style
		$pdf->setFont($pageOptions['fontFamily'], $itemFontStyle, $item['size']);
		$pdf->setTextColor($color['r'], $color['g'], $color['b']);
		// display indent (when necessary)
		if ( !empty($item['indent']) ) $pdf->Cell($item['indent'], $contentHeight, ' '.$item['indentText'] ?? '', 0);
		// display content
		$contentWidth = $pdf->pgwidth - ( $item['indent'] ?? 0 );
		$pdf->MultiCell($contentWidth, $contentHeight, $item['value'], 0, $item['align']);
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
					<string name="src|value" />
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
	private static function array2pdf__renderImg(&$pdf, $item, $pageOptions) {
		// calculate dimension
		$imgWidth = $item['width'] ?? $pdf->pgwidth;
		$imgHeight = $item['height'] ?? null;
		// calculate left position
		if     ( isset($item['align']) and $item['align'] == 'C'  ) $left = ($pdf->pgwidth/2) - ($imgWidth/2);
		elseif ( isset($item['align']) and $item['align'] == 'R'  ) $left = $pdf->pgwidth - $imgWidth;
		else $left = $pageOptions['margin']['L'];
		// display
		$pdf->Image($item['src'] ?? $item['value'], $left, $pdf->y, $imgWidth, $imgHeight);
		// done!
		return true;
	}
	private static function array2pdf__renderImage(&$pdf, $item, $pageOptions) { return self::array2pdf__renderImg($pdf, $item, $pageOptions); }




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
	private static function array2pdf__renderHeading(&$pdf, $item, $pageOptions, $hSize) {
		$item['bold'] = true;
		if ( $hSize == 'h1' ) $item['size'] = $pageOptions['fontSize'] * 2;
		if ( $hSize == 'h2' ) $item['size'] = $pageOptions['fontSize'] * 1.5;
		if ( $hSize == 'h3' ) $item['size'] = $pageOptions['fontSize'] * 1.17;
		if ( $hSize == 'h5' ) $item['size'] = $pageOptions['fontSize'] * .83;
		if ( $hSize == 'h6' ) $item['size'] = $pageOptions['fontSize'] * .67;
		return self::array2pdf__renderDiv($pdf, $item, $pageOptions);
	}
	private static function array2pdf__renderH1(&$pdf, $item, $pageOptions) { return self::array2pdf__renderHeading($pdf, $item, $pageOptions, 'h1'); }
	private static function array2pdf__renderH2(&$pdf, $item, $pageOptions) { return self::array2pdf__renderHeading($pdf, $item, $pageOptions, 'h2'); }
	private static function array2pdf__renderH3(&$pdf, $item, $pageOptions) { return self::array2pdf__renderHeading($pdf, $item, $pageOptions, 'h3'); }
	private static function array2pdf__renderH4(&$pdf, $item, $pageOptions) { return self::array2pdf__renderHeading($pdf, $item, $pageOptions, 'h4'); }
	private static function array2pdf__renderH5(&$pdf, $item, $pageOptions) { return self::array2pdf__renderHeading($pdf, $item, $pageOptions, 'h5'); }
	private static function array2pdf__renderH6(&$pdf, $item, $pageOptions) { return self::array2pdf__renderHeading($pdf, $item, $pageOptions, 'h6'); }




	/**
	<fusedoc>
		<description>
			render horizontal line to PDF
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
	private static function array2pdf__renderHR(&$pdf, $item, $pageOptions) {
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
					<array name="value">
						<string name="+" />
					</array>
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<string name="color" optional="yes" />
					<string name="indent" optional="yes" default="10" />
					<string name="bullet" optional="yes" default="~chr(149)~|{n}." />
				</structure>
				<structure name="$pageOptions" />
				<string name="$listType" value="ol|ul" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2pdf__renderList(&$pdf, $item, $pageOptions, $listType) {
		$item['value']  = $item['value']  ?? [];
		$item['indent'] = $item['indent'] ?? 8;
		$item['bullet'] = $item['bullet'] ?? ( ( $listType == 'ol' ) ? '{n}.' : '•' );
		// go through each item in list
		$i = 0;
		foreach ( $item['value'] as $key => $val ) {
			$i++;
			// prepare options of list item
			$listItem = $item;
			$listItem['value'] = $val;
			// when non-numeric key
			// ===> use as bullet text
			// ===> otherwise, auto-generate bullet text
			if ( !is_numeric($key) ) $listItem['indentText'] = $key;
			elseif ( $listType == 'ol' ) $listItem['indentText'] = str_replace('{n}', $i, $item['bullet']);
			else $listItem['indentText'] = $item['bullet'];
			// render list item
			$rendered = self::array2pdf__renderDiv($pdf, $listItem, $pageOptions);
			if ( $rendered === false ) return false;
		}
		// done!
		return true;
	}
	private static function array2pdf__renderOL(&$pdf, $item, $pageOptions) { return self::array2pdf__renderList($pdf, $item, $pageOptions, 'ol'); }
	private static function array2pdf__renderUL(&$pdf, $item, $pageOptions) { return self::array2pdf__renderList($pdf, $item, $pageOptions, 'ul'); }




	/**
	<fusedoc>
		<description>
			render paragraph (with bottom margin) to PDF
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
				<structure name="$pageOptions" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2pdf__renderP(&$pdf, $item, $pageOptions) {
		return ( self::array2pdf__renderDiv($pdf, $item, $pageOptions) and self::array2pdf__renderBR($pdf, $item, $pageOptions) );
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
	private static function array2pdf__renderPageBreak(&$pdf, $item, $pageOptions) {
		return self::array2pdf__newBlankPage($pdf, $pageOptions);
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
	private static function array2pdf__renderSmall(&$pdf, $item, $pageOptions) {
		$item['size'] = $pageOptions['fontSize'] * .8;
		return self::array2pdf__renderDiv($pdf, $item, $pageOptions);
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
				<!-- page options -->
				<structure name="$options" optional="yes">
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
	public static function html2pdf($html, $filePath=null, $options=[]) {
		// default page options
/*
		$pageOptions['paperSize']   = $pageOptions['paperSize']   ?? 'A4';
		$pageOptions['orientation'] = $pageOptions['orientation'] ?? 'P';
		$pageOptions['fontFamily']  = $pageOptions['fontFamily']  ?? 'Times';
		$pageOptions['fontStyle']   = $pageOptions['fontStyle']   ?? '';
		$pageOptions['fontSize']    = $pageOptions['fontSize']    ?? 12;
		$pageOptions['margin']      = $pageOptions['margin']      ?? [];
		$pageOptions['margin']['L'] = $pageOptions['margin']['L'] ?? 10;
		$pageOptions['margin']['R'] = $pageOptions['margin']['R'] ?? 10;
		$pageOptions['margin']['T'] = $pageOptions['margin']['T'] ?? 10;
*/
		// validate library
		$libClass = self::$libPath['html2pdf'];
		if ( !class_exists($libClass) ) {
			self::$error = "[Util::html2pdf] mPDF library is missing ({$libClass}) - Please use <em>composer</em> to install <strong>mpdf/mpdf</strong> into your project";
			return false;
		}
		// determine output location
		$result = array('path' => self::uploadDir($filePath), 'url'  => self::uploadUrl($filePath));
		if ( $result['path'] === false or $result['url'] === false ) return false;
		// start!
		$pdf = new Mpdf\Mpdf();
		// magic config for CKJ characters
		$pdf->autoLangToFont = true;
		$pdf->autoScriptToLang = true;
		// write output to file
		$pdf->WriteHTML($html);
		// view as PDF directly (when file path not specified)
		if ( empty($filePath) ) die( $pdf->Output() );
		// save into file
		$pdf->Output($result['path']);
		// done!
		return $result;
	}


} // class