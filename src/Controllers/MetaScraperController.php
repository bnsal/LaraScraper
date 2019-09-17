<?php

namespace Bnsal\LaraScraper;

use Illuminate\Http\Request;

class MetaScraperController extends SimpleHTMLDomController
{

	private $URL;

	private $HOST_NAME;

	private $HTML;
	
	public function __construct( $url = 'https://ytbpals.com' ){
		$this->URL = $url;
		$this->extractHostName();
		Parent::__construct( $url );
	}

	public function getTitle(){
		$titleTag = $this->getHTML()->find('title', 0);
		if($titleTag){
			return $titleTag->plaintext;
		}
		return "";
	}

	public function getDescription(){
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

	public function getAllAnchors(){
		$results = [];
		$rows = $this->getHTML()->find('a');
		if($rows){
			foreach ($rows as $row) {
				$results[] = [
					"href" => $row->href,
					"title" => $row->title,
					"alt" => $row->alt,
					"text" => $row->plaintext
				];
			}
		}
		return $results;
	}

	public function getAllInternalAnchors(){
		$results = [];
		$rows = $this->getAllAnchors();
		if($rows){
			foreach ($rows as $row) {
				if( $row["href"] && $this->extractHostName($row["href"]) == $this->HOST_NAME ){
					$results[] = $row;
				}
			}
		}
		return $results;
	}
	
	public function getAllExternalAnchors(){
		$results = [];
		$rows = $this->getAllAnchors();
		if($rows){
			foreach ($rows as $row) {
				if( $row["href"] ){
					$host = $this->extractHostName($row["href"]);
					if( $host && $host != $this->HOST_NAME ){
						$results[] = $row;
					}
				}
			}
		}
		return $results;
	}

	public function filterFromExternalAnchors( $urlToFilter ){
		$results = [];
		$host = $this->extractHostName($urlToFilter);
		$rows = $this->getAllExternalAnchors();
		if($rows){
			foreach ($rows as $row) {
				if( $this->extractHostName($row["href"]) == $host ){
					$results[] = $row;
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



}
