<?php

/**
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Helmut Schottmüller 2009-2013
 * @author     Helmut Schottmüller <https://github.com/hschottm>
 * @package    tags
 * @license    LGPL
 * @filesource
 */

namespace Contao;

/**
 * Class ModuleEventlistTags
 *
 * Front end module "event list with tags support".
 * @copyright  Helmut Schottmüller 2009-2013
 * @author     Helmut Schottmüller <https://github.com/hschottm>
 * @package    Controller
 */
class ModuleEventlistTags extends \ModuleEventlist
{
	/**
	 * Generate module
	 */
	protected function getAllEvents($arrCalendars, $intStart, $intEnd)
	{
		$arrAllEvents = parent::getAllEvents($arrCalendars, $intStart, $intEnd);
		if (($this->tag_ignore) && !strlen($this->tag_filter)) return $arrAllEvents;
	
		if (strlen(\Input::get('tag')) || strlen($this->tag_filter))
		{
			$limit = null;
			$offset = 0;
			$tagids = array();
			if (strlen($this->tag_filter)) $tagids = $this->getFilterTags();

			$relatedlist = (strlen(\Input::get('related'))) ? preg_split("/,/", \Input::get('related')) : array();
			$tagArray = (strlen(\Input::get('tag'))) ? array(\Input::get('tag')) : array();
			$alltags = array_merge($tagArray, $relatedlist);
			foreach ($alltags as $tag)
			{
				if (count($tagids))
				{
					$tagids = $this->Database->prepare("SELECT tid FROM tl_tag WHERE from_table = ? AND tag = ? AND tid IN (" . join($tagids, ",") . ")")
						->execute('tl_calendar_events', $tag)
						->fetchEach('tid');
				}
				else
				{
					$tagids = $this->Database->prepare("SELECT tid FROM tl_tag WHERE from_table = ? AND tag = ?")
						->execute('tl_calendar_events', $tag)
						->fetchEach('tid');
				}
			}
			if (count($tagids))
			{
				foreach ($arrAllEvents as $allEventsIdx => $days)
				{
					foreach ($days as $daysIdx => $day)
					{
						foreach ($day as $dayIdx => $event)
						{
							if (!in_array($event['id'], $tagids)) unset($arrAllEvents[$allEventsIdx][$daysIdx][$dayIdx]);
						}
					}
				}
			}
			else
			{
				$arrAllEvents = array();
			}
		}
		return $arrAllEvents;
	}
	
	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var \PageModel $objPage */
		global $objPage;

		$blnClearInput = false;

		$intYear = \Input::get('year');
		$intMonth = \Input::get('month');
		$intDay = \Input::get('day');

		// Jump to the current period
		if (!isset($_GET['year']) && !isset($_GET['month']) && !isset($_GET['day']))
		{
			switch ($this->cal_format)
			{
				case 'cal_year':
					$intYear = date('Y');
					break;

				case 'cal_month':
					$intMonth = date('Ym');
					break;

				case 'cal_day':
					$intDay = date('Ymd');
					break;
			}

			$blnClearInput = true;
		}

		$blnDynamicFormat = (!$this->cal_ignoreDynamic && in_array($this->cal_format, array('cal_day', 'cal_month', 'cal_year')));

		// Create the date object
		try
		{
			if ($blnDynamicFormat && $intYear)
			{
				$this->Date = new \Date($intYear, 'Y');
				$this->cal_format = 'cal_year';
				$this->headline .= ' ' . date('Y', $this->Date->tstamp);
			}
			elseif ($blnDynamicFormat && $intMonth)
			{
				$this->Date = new \Date($intMonth, 'Ym');
				$this->cal_format = 'cal_month';
				$this->headline .= ' ' . \Date::parse('F Y', $this->Date->tstamp);
			}
			elseif ($blnDynamicFormat && $intDay)
			{
				$this->Date = new \Date($intDay, 'Ymd');
				$this->cal_format = 'cal_day';
				$this->headline .= ' ' . \Date::parse($objPage->dateFormat, $this->Date->tstamp);
			}
			else
			{
				$this->Date = new \Date();
			}
		}
		catch (\OutOfBoundsException $e)
		{
			/** @var \PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();
			$objHandler->generate($objPage->id);
		}

		list($strBegin, $strEnd, $strEmpty) = $this->getDatesFromFormat($this->Date, $this->cal_format);

		// Get all events
		$arrAllEvents = $this->getAllEvents($this->cal_calendar, $strBegin, $strEnd);
		$sort = ($this->cal_order == 'descending') ? 'krsort' : 'ksort';

		// Sort the days
		$sort($arrAllEvents);

		// Sort the events
		foreach (array_keys($arrAllEvents) as $key)
		{
			$sort($arrAllEvents[$key]);
		}

		$arrEvents = array();
		$dateBegin = date('Ymd', $strBegin);
		$dateEnd = date('Ymd', $strEnd);

		// Remove events outside the scope
		foreach ($arrAllEvents as $key=>$days)
		{
			if ($key < $dateBegin || $key > $dateEnd)
			{
				continue;
			}

			foreach ($days as $day=>$events)
			{
				foreach ($events as $event)
				{
					$event['firstDay'] = $GLOBALS['TL_LANG']['DAYS'][date('w', $day)];
					$event['firstDate'] = \Date::parse($objPage->dateFormat, $day);
					$event['datetime'] = date('Y-m-d', $day);

					$arrEvents[] = $event;
				}
			}
		}

		unset($arrAllEvents);
		$total = count($arrEvents);
		$limit = $total;
		$offset = 0;

		// Overall limit
		if ($this->cal_limit > 0)
		{
			$total = min($this->cal_limit, $total);
			$limit = $total;
		}

		// Pagination
		if ($this->perPage > 0)
		{
			$id = 'page_e' . $this->id;
			$page = (\Input::get($id) !== null) ? \Input::get($id) : 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				/** @var \PageError404 $objHandler */
				$objHandler = new $GLOBALS['TL_PTY']['error_404']();
				$objHandler->generate($objPage->id);
			}

			$offset = ($page - 1) * $this->perPage;
			$limit = min($this->perPage + $offset, $total);

			$objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$strMonth = '';
		$strDate = '';
		$strEvents = '';
		$dayCount = 0;
		$eventCount = 0;
		$headerCount = 0;
		$imgSize = false;

		// Override the default image size
		if ($this->imgSize != '')
		{
			$size = deserialize($this->imgSize);

			if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]))
			{
				$imgSize = $this->imgSize;
			}
		}

		// Parse events
		for ($i=$offset; $i<$limit; $i++)
		{
			$event = $arrEvents[$i];
			$blnIsLastEvent = false;

			// Last event on the current day
			if (($i+1) == $limit || !isset($arrEvents[($i+1)]['firstDate']) || $event['firstDate'] != $arrEvents[($i+1)]['firstDate'])
			{
				$blnIsLastEvent = true;
			}

			/** @var \FrontendTemplate|object $objTemplate */
			$objTemplate = new \FrontendTemplate($this->cal_template);
			$objTemplate->setData($event);

			// Month header
			if ($strMonth != $event['month'])
			{
				$objTemplate->newMonth = true;
				$strMonth = $event['month'];
			}

			// Day header
			if ($strDate != $event['firstDate'])
			{
				$headerCount = 0;
				$objTemplate->header = true;
				$objTemplate->classHeader = ((($dayCount % 2) == 0) ? ' even' : ' odd') . (($dayCount == 0) ? ' first' : '') . (($event['firstDate'] == $arrEvents[($limit-1)]['firstDate']) ? ' last' : '');
				$strDate = $event['firstDate'];

				++$dayCount;
			}

			// Show the teaser text of redirect events (see #6315)
			if (is_bool($event['details']))
			{
				$objTemplate->details = $event['teaser'];
			}

			// Add the template variables
			$objTemplate->classList = $event['class'] . ((($headerCount % 2) == 0) ? ' even' : ' odd') . (($headerCount == 0) ? ' first' : '') . ($blnIsLastEvent ? ' last' : '') . ' cal_' . $event['parent'];
			$objTemplate->classUpcoming = $event['class'] . ((($eventCount % 2) == 0) ? ' even' : ' odd') . (($eventCount == 0) ? ' first' : '') . ((($offset + $eventCount + 1) >= $limit) ? ' last' : '') . ' cal_' . $event['parent'];
			$objTemplate->readMore = specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $event['title']));
			$objTemplate->more = $GLOBALS['TL_LANG']['MSC']['more'];
			$objTemplate->locationLabel = $GLOBALS['TL_LANG']['MSC']['location'];

			// Short view
			if ($this->cal_noSpan)
			{
				$objTemplate->day = $event['day'];
				$objTemplate->date = $event['date'];
				$objTemplate->span = ($event['time'] == '' && $event['day'] == '') ? $event['date'] : '';
			}
			else
			{
				$objTemplate->day = $event['firstDay'];
				$objTemplate->date = $event['firstDate'];
				$objTemplate->span = '';
			}

			$objTemplate->addImage = false;

			// Add an image
			if ($event['addImage'] && $event['singleSRC'] != '')
			{
				$objModel = \FilesModel::findByUuid($event['singleSRC']);

				if ($objModel === null)
				{
					if (!\Validator::isUuid($event['singleSRC']))
					{
						$objTemplate->text = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
					}
				}
				elseif (is_file(TL_ROOT . '/' . $objModel->path))
				{
					if ($imgSize)
					{
						$event['size'] = $imgSize;
					}

					$event['singleSRC'] = $objModel->path;
					$this->addImageToTemplate($objTemplate, $event);
				}
			}

			$objTemplate->enclosure = array();

			// Add enclosure
			if ($event['addEnclosure'])
			{
				$this->addEnclosuresToTemplate($objTemplate, $event);
			}

			////////// CHANGES BY ModuleEventlistTags
			$objTemplate->showTags = $this->event_showtags;
			if ($this->event_showtags)
			{
				$helper = new \TagHelper();
				$tagsandlist = $helper->getTagsAndTaglistForIdAndTable($event['id'], 'tl_calendar_events', $this->tag_jumpTo);
				$tags = $tagsandlist['tags'];
				$taglist = $tagsandlist['taglist'];
				$objTemplate->showTagClass = $this->tag_named_class;
				$objTemplate->tags = $tags;
				$objTemplate->taglist = $taglist;
			}
			////////// CHANGES BY ModuleEventlistTags

			$strEvents .= $objTemplate->parse();

			++$eventCount;
			++$headerCount;
		}

		// No events found
		if ($strEvents == '')
		{
			$strEvents = "\n" . '<div class="empty">' . $strEmpty . '</div>' . "\n";
		}

		// See #3672
		$this->Template->headline = $this->headline;
		$this->Template->events = $strEvents;

		// Clear the $_GET array (see #2445)
		if ($blnClearInput)
		{
			\Input::setGet('year', null);
			\Input::setGet('month', null);
			\Input::setGet('day', null);
		}

		////////// CHANGES BY ModuleEventlistTags
		$headlinetags = array();
		if ((strlen(\Input::get('tag')) && (!$this->tag_ignore)) || (strlen($this->tag_filter)))
		{
			if (strlen($this->tag_filter))
			{
				$headlinetags = preg_split("/,/", $this->tag_filter);
				$tagids = $this->getFilterTags();
				$first = false;
			}
			else
			{
				$headlinetags = array();
			}
			$relatedlist = (strlen(\Input::get('related'))) ? preg_split("/,/", \Input::get('related')) : array();
			$tagArray = (strlen(\Input::get('tag'))) ? array(\Input::get('tag')) : array();
			$headlinetags = array_merge($headlinetags, $tagArray);
			if (count($relatedlist))
			{
				$headlinetags = array_merge($headlinetags, $relatedlist);
			}
		}
		if (strlen($this->Template->events) == 0)
		{
			$headlinetags = array_merge(array(\Input::get('tag')), $relatedlist);
			$this->Template->events = $GLOBALS['TL_LANG']['MSC']['emptyevents'];
		}
		$this->Template->tags_activetags = $headlinetags;
		////////// CHANGES BY ModuleEventlistTags
	}

}

?>