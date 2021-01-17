<?php

namespace Bnsal\LaraScraper;

use Illuminate\Http\Request;

class SimpleHTMLDomController
{

	private $URL;

	public $HTML;
	
	public function __construct($url){
		$this->URL = $url;
		require_once __DIR__ . '/../Helpers/simple_html_dom.php';
		$this->getHTML();
	}

	public function getHTML(){
		if(!$this->HTML){
			$this->HTML = file_get_html($this->URL);
		}
		return $this->HTML;
	}
	

}
