<?php
class Util_PDF {


	// property : library for corresponding methods
	private static $libPath = array(
		'array2html' => 'Mpdf\Mpdf',
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
				<array name="$data">
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
						<string name="bullet" optional="yes" oncondition="ol|ul" />
						<number name="indent" optional="yes" />
						<string name="url" optional="yes" />
					</structure>
				</array>
			</in>
			<out>
				<string name="~return~" format="html" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function array2html($data) {
		$result = '';
		// go through each item
		foreach ( $data as $item ) {
			// fix : type
			if ( !isset($item['type']) ) $item['type'] = 'div';
			else $item['type'] = strtolower($item['type']);
			// fix : align
			if     ( isset($item['align']) and strtoupper($item['align']) == 'L' ) $item['align'] = 'left';
			elseif ( isset($item['align']) and strtoupper($item['align']) == 'R' ) $item['align'] = 'right';
			elseif ( isset($item['align']) and strtoupper($item['align']) == 'C' ) $item['align'] = 'center';
			elseif ( isset($item['align']) and strtoupper($item['align']) == 'J' ) $item['align'] = 'justify';
			elseif ( isset($item['align']) ) $item['align'] = strtolower($item['align']);
			// fix : color & size
			if ( !isset($item['color']) and isset($item['fontColor']) ) $item['color'] = $item['fontColor'];
			if ( !isset($item['size'])  and isset($item['fontSize'])  ) $item['size']  = $item['fontSize'];
			// validation
			$renderMethod = 'array2html__'.$item['type'];
			if ( !method_exists(__CLASS__, $renderMethod) ) {
				self::$error = '[Util_PDF::array2html] Unknown type ('.$item['type'].')';
				return false;
			}
			// render item as corresponding type
			$itemResult = self::$renderMethod($item);
			if ( $itemResult === false ) return false;
			// append to result
			$result .= $itemResult;
		}
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
				<structure name="$item">
					<string name="type" value="br" />
					<number name="repeat" optional="yes" default="1" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__br($item) {
		return str_repeat('<br />', $item['repeat'] ?? 1);
	}




	/**
	<fusedoc>
		<description>
			render paragraph (without bottom margin) to PDF
		</description>
		<io>
			<in>
				<structure name="$item">
					<string name="type" value="div" />
					<string name="value" />
					<string name="align" optional="yes" comments="J|L|C|R" />
					<boolean name="bold" optional="yes" default="false" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<number name="size" optional="yes" default="~pageOptions[fontSize]~" />
					<string name="color" optional="yes" />
					<number name="indent" optional="yes" />
					<string name="indentText" optional="yes" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__div($item) {
		$output = '<div style="';
		if ( !empty($item['size'])      ) $output .= 'font-size:'.$item['size'].( is_numeric($item['size']) ? 'pt' : '' ).';';
		if ( !empty($item['bold'])      ) $output .= 'font-weight:bold;';
		if ( !empty($item['italic'])    ) $output .= 'font-style:italic;';
		if ( !empty($item['underline']) ) $output .= 'text-decoration:underline;';
		if ( !empty($item['color'])     ) $output .= 'color:'.$item['color'].';';
		if ( !empty($item['align'])     ) $output .= 'text-align:'.$item['align'].';';
		// (close tag)
		$output .= '">'.( $item['value'] ?? '' ).'</div>';
		// done!
		return $output;
	}




	/**
	<fusedoc>
		<description>
			render image to PDF
		</description>
		<io>
			<in>
				<structure name="$item">
					<string name="type" value="img" />
					<string name="src|value" />
					<string name="align" optional="yes" comments="L|C|R" />
					<number name="height" optional="yes" />
					<number name="width" optional="yes" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__img($item) {
		$output = '<img src="'.( $item['src'] ?? $item['value'] ?? '' ).'"';
		// height & width
		if ( !empty($item['height']) or !empty($item['width']) ) {
			$output .= ' style="';
			if ( !empty($item['height']) ) $output .= 'height:'.$item['height'].( is_numeric($item['height']) ? 'pt' : '' ).';';
			if ( !empty($item['width']) ) $output .= 'width:'.$item['width'].( is_numeric($item['width']) ? 'pt' : '' ).';';
			$output .= '"';
		}
		// (close image tag)
		$output .= '/>';
		// alignment
		if ( isset($item['align']) ) $output = '<div style="text-align:'.call_user_func(function() use ($item){
			if ( $item['align'] == 'L' ) return 'left';
			if ( $item['align'] == 'C' ) return 'center';
			if ( $item['align'] == 'R' ) return 'right';
			return $item['align'];
		}).';">'.$output.'</div>';
		// done!
		return $output;
	}
	// alias method
	private static function array2html__image($item) { return self::array2html__img($item); }




	/**
	<fusedoc>
		<description>
			render heading to PDF
		</description>
		<io>
			<in>
				<structure name="$item">
					<string name="type" value="h1|h2|h3|h4|h5|h6" />
					<string name="value" />
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<string name="color" optional="yes" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__heading($item) {
		$item['bold'] = true;
		$item['value'] = "<{$item['type']}>{$item['value']}</{$item['type']}>";
		return self::array2html__div($item);
	}
	// alias method
	private static function array2html__h1($item) { return self::array2html__heading($item); }
	private static function array2html__h2($item) { return self::array2html__heading($item); }
	private static function array2html__h3($item) { return self::array2html__heading($item); }
	private static function array2html__h4($item) { return self::array2html__heading($item); }
	private static function array2html__h5($item) { return self::array2html__heading($item); }
	private static function array2html__h6($item) { return self::array2html__heading($item); }




	/**
	<fusedoc>
		<description>
			render horizontal line to PDF
		</description>
		<io>
			<in>
				<structure name="$item">
					<string name="type" value="hr" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__hr($item) {
		return '<hr style="border: solid black; border-width: 1px 0 0 0;" />';
	}




	/**
	<fusedoc>
		<description>
			render list to PDF
		</description>
		<io>
			<in>
				<structure name="$item">
					<string name="type" value="ul|ol" />
					<array name="value|list">
						<string name="+" />
					</array>
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<string name="color" optional="yes" />
					<string name="indent" optional="yes" />
				</structure>
				<string name="$listType" value="ol|ul" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__list($item) {
		// fix param
		$item['value'] = $item['value'] ?? $item['list'] ?? [];
		if ( is_string($item['value']) ) $item['value'] = array($item['value']);
		// render list
		$item['value'] = '<'.$item['type'].call_user_func(function() use ($item){
			if ( isset($item['indent']) ) return ' style="margin-left:'.$item['indent'].( is_numeric($item['indent']) ? 'pt' : '' ).';"';
			return '';
		}).'>'.implode('', array_map(function($val){
			return '<li>'.$val.'</li>';
		}, $item['value'])).'</'.$item['type'].'>';
		// wrap by [div] for styling
		return self::array2html__div($item);
	}
	private static function array2html__ol($item) { return self::array2html__list($item); }
	private static function array2html__ul($item) { return self::array2html__list($item); }




	/**
	<fusedoc>
		<description>
			render paragraph (with bottom margin) to PDF
		</description>
		<io>
			<in>
				<structure name="$item">
					<string name="value" />
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="bold" optional="yes" default="false" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<number name="size" optional="yes" default="~pageOptions[fontSize]~" />
					<string name="color" optional="yes" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__p($item) {
		$item['value'] = '<p>'.$item['value'].'</p>';
		return self::array2html__div($item);
	}




	/**
	<fusedoc>
		<description>
			render page break to PDF
		</description>
		<io>
			<in>
				<structure name="$item" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__pagebreak($item) {
		return '<div style="page-break-after: always;"></div>';
	}




	/**
	<fusedoc>
		<description>
			render small text to PDF
		</description>
		<io>
			<in>
				<structure name="$item">
					<string name="value" />
					<string name="align" optional="yes" default="J" comments="J|L|C|R" />
					<boolean name="bold" optional="yes" default="false" />
					<boolean name="italic" optional="yes" default="false" />
					<boolean name="underline" optional="yes" default="false" />
					<string name="color" optional="yes" />
				</structure>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	private static function array2html__small($item) {
		$item['value'] = '<small>'.$item['value'].'</small>';
		return self::array2html__div($item);
	}




	/**
	<fusedoc>
		<description>
			generate PDF file with provided data
		</description>
		<io>
			<in>
				<array name="$fileData" comments="please refer to {array2html}" />
				<string name="$filePath" optional="yes" comments="please refer to {html2pdf}" />
				<structure name="$pageOptions" optional="yes" comments="please refer to {html2pdf}" />
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
		$html = self::array2html($fileData);
		if ( $html === false ) return false;
		return self::html2pdf($html, $filePath, $pageOptions);
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
				<structure name="$pageOptions" optional="yes">
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
	public static function html2pdf($html, $filePath=null, $pageOptions=[]) {
		// validate library
		$libClass = self::$libPath['html2pdf'];
		if ( !class_exists($libClass) ) {
			self::$error = "[Util_PDF::html2pdf] mPDF library is missing ({$libClass}) - Please use <em>composer</em> to install <strong>mpdf/mpdf</strong> into your project";
			return false;
		}
		// start!
		$pdf = new Mpdf\Mpdf([
			'mode' => '+aCJK',
			'format' => $pageOptions['paperSize'] ?? 'A4',
		]);
		// magic config for CKJ characters
		$pdf->autoLangToFont = true;
		$pdf->autoScriptToLang = true;
		// write output to file
		$pdf->WriteHTML($html);
		// view as PDF directly (when file path not specified)
		if ( empty($filePath) ) exit($pdf->Output());
		// determine output location
		$result = array('path' => Util::uploadDir($filePath), 'url'  => Util::uploadUrl($filePath));
		if ( $result['path'] === false or $result['url'] === false ) {
			self::$error = '[Util_PDF::html2pdf] '.Util::error();
			return false;
		}
		// save into file
		$pdf->Output($result['path']);
		// done!
		return $result;
	}


} // class