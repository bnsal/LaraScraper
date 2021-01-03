<?php

namespace Bnsal\LaraScraper;

use Illuminate\Http\Request;

class MetaScraperController extends SimpleHTMLDomController
{

	private $URL;

	private $URL_SCEHEME;

	private $URL_HOST;

	private $HOST_NAME;

	private $HTML;
	
	public function __construct( $url = 'http://bnsal.com' ){
		$this->URL = $url;

		$parts = parse_url($url);
		$this->URL_SCEHEME = $parts['scheme'];
		$this->URL_HOST = $parts['host'];

		$this->extractHostName();
		Parent::__construct( $url );
	}
	

	public function getTitle(){
		if( !$this->getHTML() ){
			return "";
		}
		$titleTag = $this->getHTML()->find('title', 0);
		if($titleTag){
			return $titleTag->plaintext;
		}
		return "";
	}

	public function getDescription(){
		if( !$this->getHTML() ){
			return "";
		}
		$description = $this->getHTML()->find('meta[name=description]', 0);
		if(!$description){
			$description = $this->getHTML()->find('meta[itemprop=description]', 0);
		}
		if(!$description){
			$description = $this->getHTML()->find('meta[property=description]', 0);
		}
		if(!$description){
			$description = $this->getHTML()->find('meta[type=description]', 0);
		}
		if($description){
			return $description->content;
		}
		return "";
	}

	public function getAllAnchors( $splitByHash = false ){
		$results = [];
		if( !$this->getHTML() ){
			return $results;
		}
		$rows = $this->getHTML()->find('a');
		if($rows){
			foreach ($rows as $row) {

				$doFollow = 1;
				if( isset($row->rel) && $row->rel ){
					if( stripos( '__' . $row->rel , "nofollow" ) ){
						$doFollow = 0;
					}
				}

				$row->href = trim( ltrim(rtrim($row->href, "/"), "/"), "/");
				if( !$this->startsWith($row->href, 'http://') && !$this->startsWith($row->href, 'https://') && !$this->startsWith($row->href, 'www.') ) {
					$row->href = $this->URL_SCEHEME . '://' . $this->URL_HOST . '/' . $row->href;
				}

				if( strpos($row->href, '#') ) {
					$row->href = trim( explode("#", $row->href)[0] );
				}

				$results[] = [
					"href" => $row->href,
					"title" => $row->title,
					"alt" => $row->alt,
					"text" => $row->plaintext,
					"doFollow" => $doFollow
				];
			}
		}
		return $results;
	}

	public function getAllInternalAnchors( $prefix = null, $splitByHash = false ){
		$results = [];
		$rows = $this->getAllAnchors( $splitByHash );
		if($rows){
			foreach ($rows as $row) {
				if( $prefix ) {
					if( $row["href"] && $this->startsWith($row["href"], $prefix) ){
						$results[] = $row;
					}
				} else if( $row["href"] && $this->extractHostName($row["href"]) == $this->HOST_NAME ){
					$results[] = $row;
				}
			}
		}
		return $results;
	}
	
	public function getAllExternalAnchors( $hostToFilter ){
		$results = [];
		$rows = $this->getAllAnchors();
		if($rows){
			foreach ($rows as $row) {
				if( $row["href"] ){
					$host = $this->extractHostName($row["href"]);
					if( $host && ( ($host != $this->HOST_NAME) || stripos( '_' . urldecode($row['href']), $hostToFilter ) ) ){
						$results[] = $row;
					}
				}
			}
		}
		return $results;
	}

	public function filterFromExternalAnchors( $urlToFilter ){
		$results = [];
		if( !$this->getHTML() ){
			return $results;
		}
		$host = $this->extractHostName($urlToFilter);
		$rows = $this->getAllExternalAnchors( $host );
		if($rows){
			foreach ($rows as $row) {
				//pr( urldecode($row['href']) );
				if( $this->extractHostName($row["href"]) == $host ){
					$results[] = $row;
				}else{
					if( stripos( '_' . urldecode($row['href']), $host ) ){
						$results[] = $row;
					}
				}
			}
		}
		return $results;
	}

	public function extractHostName( $url = null ){
		if($this->HOST_NAME && !$url){
			return $this->HOST_NAME;
		}
		$hostName = str_ireplace([ "https://", "http://", "www." ], "", $url?$url:$this->URL);
		$hostName = explode("/", $hostName)[0];
		$hostName = trim( explode("#", $hostName)[0] );
		if( !strpos($hostName, ".") ){
			return null;
		}
		if(!$url){
			$this->HOST_NAME = $hostName;
		}
		return $hostName;
	}

	public function startsWith ($string, $startString) { 
	    return (substr($string, 0, strlen($startString)) === $startString); 
	}



}
