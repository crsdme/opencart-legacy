<?php

/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
 */

/**
 * Document class
 */
class Document
{
	private $title;
	private $robots;
	private $description;
	private $keywords;

	private $links = array();
	private $styles = array();
	private $styles_before = array();
	private $scripts = array();
	private $scripts_before = array();
	private $og_image;

	/**
	 * 
	 *
	 * @param	string	$title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * 
	 * 
	 * @return	string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	public function setRobots($robots)
	{
		$this->robots = $robots;
	}

	public function getRobots()
	{
		return $this->robots;
	}

	/**
	 * 
	 *
	 * @param	string	$description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * 
	 *
	 * @param	string	$description
	 * 
	 * @return	string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * 
	 *
	 * @param	string	$keywords
	 */
	public function setKeywords($keywords)
	{
		$this->keywords = $keywords;
	}

	/**
	 *
	 * 
	 * @return	string
	 */
	public function getKeywords()
	{
		return $this->keywords;
	}

	/**
	 * 
	 *
	 * @param	string	$href
	 * @param	string	$rel
	 */
	public function addLink($href, $rel)
	{
		$this->links[$href] = array(
			'href' => $href,
			'rel'  => $rel
		);
	}

	/**
	 * 
	 * 
	 * @return	array
	 */
	public function getLinks()
	{
		return $this->links;
	}

	/**
	 * 
	 *
	 * @param	string	$href
	 * @param	string	$rel
	 * @param	string	$media
	 */
	public function addStyle($href, $rel = 'stylesheet', $media = 'screen', $position = 'header', $prepend = false)
	{
		$item = array(
			'href'  => $href,
			'rel'   => $rel,
			'media' => $media
		);

		unset($this->styles[$position][$href], $this->styles_before[$position][$href]);

		if ($prepend) {
			$this->styles_before[$position][$href] = $item;
		} else {
			$this->styles[$position][$href] = $item;
		}
	}

	/**
	 * 
	 * 
	 * @return	array
	 */
	public function getStyles($position = 'header')
	{
		$before = isset($this->styles_before[$position]) ? $this->styles_before[$position] : array();
		$rest = isset($this->styles[$position]) ? $this->styles[$position] : array();

		return $before + $rest;
	}

	/**
	 * 
	 *
	 * @param	string	$href
	 * @param	string	$position
	 * @param	bool	$prepend
	 */
	public function addScript($href, $position = 'header', $prepend = false)
	{
		unset($this->scripts[$position][$href], $this->scripts_before[$position][$href]);

		if ($prepend) {
			$this->scripts_before[$position][$href] = $href;
		} else {
			$this->scripts[$position][$href] = $href;
		}
	}

	/**
	 * 
	 *
	 * @param	string	$position
	 * 
	 * @return	array
	 */
	public function getScripts($position = 'header')
	{
		$before = isset($this->scripts_before[$position]) ? $this->scripts_before[$position] : array();
		$rest = isset($this->scripts[$position]) ? $this->scripts[$position] : array();

		return $before + $rest;
	}

	public function setOgImage($image)
	{
		$this->og_image = $image;
	}

	public function getOgImage()
	{
		return $this->og_image;
	}
}
