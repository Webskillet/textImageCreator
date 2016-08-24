<?php
	
class textImageCreator {
	
	public $base_image = null;
	public $fontTTFfiles = array();
	public $defaultFontName = null;
	public $defaultFontSize = null;
	public $defaultColor = array(0, 0, 0); // default to black text
	public $defaultAngle = 0; // default to regular horizontal text
	public $downloadFileName = null;
	public $saveFileName = null;
	public $content = array();
	private $mimeType = null;
	private $imageType = null;
	private $createFunction = null;
	private $outputFunction = null;
	private $image = null;
	
	public function __construct( $base_image = null, $fontTTFfiles = null ) {
		if (!extension_loaded('gd') || !function_exists('gd_info')) trigger_error( 'textImageCreator requires the GD extension', E_USER_ERROR );
		if (is_null($base_image) || !$base_image) trigger_error('textImageCreator: base_image must be supplied', E_USER_ERROR);
		if (!is_array($fontTTFfiles) || !count($fontTTFfiles)) trigger_error('textImageCreator: font TTF files must be supplied as an array');
		$this->base_image = $base_image;
		$this->fontTTFfiles = $fontTTFfiles;
		$this->mimeType = mime_content_type($base_image);
		list($image, $imageType) = explode('/',$this->mimeType);
		if ($image != 'image') trigger_error('textImageCreator: base_image must be an gif, png or jpeg image file', E_USER_ERROR);
		if ( ($imageType != 'gif') && ($imageType != 'png') && ($imageType != 'jpeg') ) trigger_error('textImageCreator: base_image must be an gif, png or jpeg image file', E_USER_ERROR);
		$this->imageType = $imageType;
		$this->createFunction = 'imagecreatefrom'.$imageType;
		$this->outputFunction = 'image'.$imageType;
		$this->downloadFileName = 'image.'.$imageType;
		$createFunction = $this->createFunction;
		$this->image = $createFunction($this->base_image);
	}
	
	public function addText( $content = array() ) {
		if (!is_array($content) || !count($content)) { return; }
		
		// if this is a single piece of content, create a new array with one item
		if (isset($content['text']) && isset($content['xPos']) && isset($content['yPos'])) {
			$textslug = strtolower(preg_replace('/[^a-zA-Z0-9]/','',$content['text']));
			if (!$textslug) trigger_error('textImageCreator->addText: text cannot be empty', E_USER_ERROR);
			$content = array( $textslug => $content );
		}
		
		// loop through content, and add text to the image
		foreach($content as $slug => $line) {
			
			// check that we have all the information we need
			if (!isset($line['text']) || !$line['text']) {
				trigger_error('textImageCreator->addText: you must supply text', E_USER_ERROR);
			}
			if (!isset($line['xPos'])) {
				trigger_error('textImageCreator->addText: you must supply xPos', E_USER_ERROR);
			}
			if (!isset($line['yPos'])) {
				trigger_error('textImageCreator->addText: you must supply yPos', E_USER_ERROR);
			}
			if (!isset($line['fontName']) || !$line['fontName']) {
				if ($this->defaultFontName) {
					$line['fontName'] = $this->defaultFontName;
				} else {
					trigger_error('textImageCreator->addText: you must supply fontName if there is no default fontName set', E_USER_ERROR);
				}
			}
			if (!isset($line['fontSize']) || !$line['fontSize']) {
				if ($this->defaultFontSize) {
					$line['fontSize'] = $this->defaultFontSize;
				} else {
					trigger_error('textImageCreator->addText: you must supply fontSize if there is no default fontSize set', E_USER_ERROR);
				}
			}
			if (!isset($line['color']) || !is_array($line['color']) || (count($line['color']) != 3)) {
				$line['color'] = $this->defaultColor;
			}
			if (!isset($line['angle'])) {
				$line['angle'] = $this->defaultAngle;
			}
			
			// check that the fontName is valid
			$fontFile = (isset($this->fontTTFfiles[$line['fontName']]) && file_exists($this->fontTTFfiles[$line['fontName']])) ? $this->fontTTFfiles[$line['fontName']] : null;
			if (!$fontFile) trigger_error('textImageCreator->addText: '.$line['fontName'].' is not a valid font name', E_USER_ERROR);
			
			$text_color = imagecolorallocate($this->image, $line['color'][0], $line['color'][1], $line['color'][2]);
			imagettftext($this->image, $line['fontSize'], $line['angle'], $line['xPos'], $line['yPos'], $text_color, $fontFile, $line['text']);
			
			$this->content[$slug] = $line;
		}
	}
	
	public function output( $download = false ) {
		header ("Content-Type: ".$this->mimeType);
	    if($download == "true"){
	      header('Content-Disposition: attachment; filename="'.$this->downloadFileName.'"');
	    }
	    if ($this->imageType != 'jpeg') {
		    imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
	    }
	    $outputFunction = $this->outputFunction;
	    $outputFunction($this->image);
	}
	
	public function save( $filename = null ) {
		if (!$filename) { $filename = $this->saveFileName; }
		if (!$filename) trigger_error('textImageCreator->save: you must supply a filename if there is no saveFileName set' , E_USER_ERROR);
		chmod($filename, 0755);
	    if ($this->imageType != 'jpeg') {
		    imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
	    }
		$outputFunction = $this->outputFunction;
	    $outputFunction($this->image, $filename);
	}
	
	public function destroy() {
		imagedestroy($this->image);
	}
	
}