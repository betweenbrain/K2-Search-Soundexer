<?php defined('_JEXEC') or die;

/**
 * File       search_soundexer.php
 * Created    1/13/14 12:52 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2014 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

jimport('joomla.error.log');

// Load the K2 Plugin API
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR . '/components/com_k2/lib/k2plugin.php');

// Instantiate class for K2 plugin events
class plgK2Search_soundexer extends K2Plugin
{

	var $pluginName = 'search_soundexer';
	var $pluginNameHumanReadable = 'K2 - Search Soundexer';

	/**
	 * Constructor
	 */
	function __construct(&$subject, $results)
	{
		parent::__construct($subject, $results);
		$this->app = JFactory::getApplication();
		$this->db  = JFactory::getDbo();
		$this->log = JLog::getInstance();
	}

	/**
	 * Function to Update item's extra_fields_search data with tag names
	 *
	 * @param $row
	 * @param $isNew
	 */
	function onAfterK2Save(&$row, $isNew)
	{
		if ($this->app->isAdmin())
		{
			if ($this->setSoundexTable())
			{
				$this->setSoundex($row);
			}
		}
	}

	/**
	 * Sets the soundex value of each word of the titles belonging to the items in the designated category
	 *
	 * @param $ids
	 */
	private function setSoundex($row)
	{

		$titleParts = explode(' ', $row->title);
		foreach ($titleParts as $part)
		{
			// Strip non-alpha characters as we are dealing with language
			$part = preg_replace("/[^\w]/ui", '',
				preg_replace("/[0-9]/", '', $part));
			if ($part)
			{
				$query = 'INSERT INTO ' . $this->db->nameQuote('#__k2_search_soundex') . '
							(' . $this->db->nameQuote('itemId') . ',
							' . $this->db->nameQuote('word') . ',
							' . $this->db->nameQuote('soundex') . ')
							VALUES (' . $this->db->Quote($row->id) . ',
							' . $this->db->Quote($part) . ',
							' . $this->db->Quote(soundex($part)) . ')';
				$this->db->setQuery($query);
				$this->db->query();
				JFactory::getApplication()->enqueueMessage('Soundexing ' . $part);
			}
		}

	}

	private function cleanSoundexTable($id)
	{
		$query = 'DELETE FROM ' . $this->db->nameQuote('#__k2_search_soundex') . '
					WHERE ' . $this->db->nameQuote('itemId') . '=' . $this->db->Quote($id) . '';
		$this->db->setQuery($query);
		$this->db->query();

		return true;
	}

	private function setSoundexTable()
	{
		$query = "CREATE TABLE IF NOT EXISTS `jos_k2_search_soundex` (
						`id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`itemId`       INT(11)          NOT NULL,
						`word`         varchar(64)      NOT NULL,
						`soundex`      varchar(5)       NOT NULL,
						PRIMARY KEY (`id`),
						UNIQUE KEY (`word`(64))
					)
						ENGINE =MyISAM
						AUTO_INCREMENT =0
						DEFAULT CHARSET =utf8;";
		$this->db->setQuery($query);
		$this->db->query();

		return true;
	}

	/**
	 * Checks for any database errors after running a query
	 *
	 * @param null $backtrace
	 */
	private function checkDbError($backtrace = null)
	{
		if ($error = $this->db->getErrorMsg())
		{
			if ($backtrace)
			{
				$e = new Exception();
				$error .= "\n" . $e->getTraceAsString();
			}

			$this->log->addEntry(array('LEVEL' => '1', 'STATUS' => 'Database Error:', 'COMMENT' => $error));
			JError::raiseWarning(100, $error);
		}
	}

}