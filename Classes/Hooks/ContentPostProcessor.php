<?php

namespace Ka\Http2Push\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Kevin Lieser <info@ka-mediendesign.de>, www.ka-mediendesign.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * HTTP/2 Push
 *
 * @author		Kevin Lieser <info@ka-mediendesign.de>
 * @package		TYPO3
 * @subpackage	http2_push
 */
class ContentPostProcessor {
	
	var $headerLinkContent = array();

	function renderHttp2PushHeader() {
		$this->readFilesFromContent();
		header('Link: '.$this->checkHeaderLengthAndReturnImplodedArray($this->headerLinkContent));
    }
	
	function readFilesFromContent() {
		preg_match_all('/href="([^"]+\.css[^"]*)"|src="([^"]+\.js[^"]*)"|src="([^"]+\.jpg[^"]*)"|src="([^"]+\.png[^"]*)"/', $GLOBALS['TSFE']->content, $matches);
		$result = array_filter(array_merge($matches[1], $matches[2], $matches[3], $matches[4]));
		foreach($result as $file) {
			if($this->checkFileForInternal($file)) {
				array_push($this->headerLinkContent, '</'.$file.'>; '.$this->getConfigForFiletype($file));
			}
		}
	}
	
	function checkFileForInternal($file) {
		$components = parse_url($file);
		if(!isset($components['host']) && !isset($components['scheme'])) {
			return true;
		}
		return false;
	}
	
	function getConfigForFiletype($file) {
		$extension = end(explode('.', parse_url($file, PHP_URL_PATH)));
		switch ($extension) {
			case "css":
				return 'rel=preload; as=stylesheet';
				break;
			case "js":
				return 'rel=preload; as=script';
				break;
			case "png":
			case "jpg":
				return 'rel=preload; as=image';
			default:
				return 'rel=preload';
		}
	}
	
	function checkHeaderLengthAndReturnImplodedArray($array) {
		$limit = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['http2_push'])['webserverHeaderLengthLimit'];
		if(empty($limit)) { $limit = 8190; }
		$full = implode(', ', $array);
		if(strlen($full) < $limit) {
			return $full;
		} else {
			$short = substr($full, 0, $limit);
			return substr($short, 0, strrpos($short, ','));
		}
	}
		
	/**
	 *	renderAll method
	 *	render method for cached pages
	 */
	public function renderAll(){
		if(!$GLOBALS['TSFE']->isINTincScript()) {
			$this->renderHttp2PushHeader();	
		}
	}
	
	/**
	 *	renderOutput method
	 *	render method for INT pages
	 */
	public function renderOutput(){
		if($GLOBALS['TSFE']->isINTincScript()) {
			$this->renderHttp2PushHeader();
		}
	}
	

}




