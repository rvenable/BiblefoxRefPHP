<?php

class BfoxPlanScheduler {

	var $endOnAnIncludedDate = false;
	var $dateFormat = 'Y-m-d';
	var $supportedFrequencies = array(
		'daily' => array(
			'label' => 'Daily',
			'name' => 'day',
			'adjective' => 'daily',
			'increment' => '+1 day',
		),
		'weekly' => array(
			'label' => 'Weekly',
			'name' => 'week',
			'adjective' => 'weekly',
			'increment' => '+1 week',
		),
		'monthly' => array(
			'label' => 'Monthly',
			'name' => 'month',
			'adjective' => 'monthly',
			'increment' => '+1 month',
		),
	);

	function __construct() {
		$this->setFrequency();
	}

	/*
	Misc Helpers
	*/

	function normalizedDates($dates, $dateFormat = '') {
		if (empty($dateFormat)) $dateFormat = $this->dateFormat;
		$normalizedDates = array();
		foreach ((array)$dates as $date) {
			$normalizedDates []= date($dateFormat, strtotime($date));
		}
		return $normalizedDates;
	}

	function normalizeTime($time = null) {
		// Return a time that corresponds to the first second of the day
		return strtotime(date('Y-m-d', $time)); // We are formatting as 'Y-m-d', but we could use any format as long as it includes the date and excludes the time
	}

	function date($time, $dateFormat = '') {
		if (empty($dateFormat)) $dateFormat = $this->dateFormat;
		return date($dateFormat, $time);
	}

	/*
	Reading Frequency
	*/

	private $_frequency;
	function setFrequency($frequency = '') {
		if (isset($this->supportedFrequencies[$frequency])) $this->_frequency = $frequency;
		else $this->_frequency = array_shift(array_keys($this->supportedFrequencies));
	}

	function frequency() {
		return $this->_frequency;
	}

	function frequencyIncrement() {
		return $this->supportedFrequencies[$this->frequency()]['increment'];
	}

	function incrementedTime($time) {
		return strtotime($this->frequencyIncrement(), $time);
	}

	private $_readingsPerDate;
	function setReadingsPerDate($readingsPerDate) {
		$this->_readingsPerDate = max(1, $readingsPerDate);
	}

	function readingsPerDate() {
		return $this->_readingsPerDate;
	}

	/*
	Include/Exclude Dates
	*/

	function isTimeExcluded($time) {
		foreach ($this->excludedDates() as $format => $array) {
			$formatted_date = date($format, $time);
			foreach ((array) $array as $excluded_date) {
				if (is_array($excluded_date)) {
					if ($excluded_date[0] <= $formatted_date && $formatted_date <= $excluded_date[1])
						return true;
				}
				else {
					if ($excluded_date == $formatted_date) return true;
				}
			}
		}
		return false;
	}

	function nextIncludedTime($time = 0) {
		// Given a time, find the next time that isn't excluded
		// If the given time is included, it will be returned

		if (!$time) $time = $this->nextTime();
		else $time = $this->normalizeTime($time);

		// If the time is excluded, increment by one day until we find one that isn't
		while ($this->isTimeExcluded($time)) $time = strtotime('+1 day', $time);

		return $time;
	}

	var $_excludedDates = array();
	function excludedDates() {
		return $this->_excludedDates;
	}

	function setExcludedDates($excludedDates) {
		$this->_excludedDates = $excludedDates;
	}

	function resetExcludedDates($dateFormat = '') {
		if ($dateFormat) unset($this->_excludedDates[$dateFormat]);
		else $this->_excludedDates = array();
	}

	function excludeDateForFormat($date, $dateFormat) {
		if (!isset($this->_excludedDates[$dateFormat]) || !in_array($date, $this->_excludedDates[$dateFormat]))
			$this->_excludedDates[$dateFormat] []= $date;
	}

	function excludeDateRangeForFormat($startDate, $endDate, $dateFormat) {
		$this->excludeDateForFormat(array($startDate, $endDate), $dateFormat);
	}

	/*
	Exclude Regular dates
	*/

	function excludeDate($date) {
		$dateFormat = 'Y-m-d';
		$date = date($dateFormat, strtotime($date));
		$this->excludeDateForFormat($date, $dateFormat);
	}

	function excludeDateRange($startDate, $endDate) {
		$dateFormat = 'Y-m-d';
		$startDate = date($dateFormat, strtotime($startDate));
		$endDate = date($dateFormat, strtotime($endDate));
		$this->excludeDateRangeForFormat($startDate, $endDate, $dateFormat);
	}

	/*
	Include/Exclude Days of the Week
	*/

	function setDaysOfWeek($daysOfWeek) {
		// NOTE: At least one day of the week must be included, so if none are, it is a user error and we are including all days
		$this->resetExcludedDates('w'); // Reset the days of week excludes
		if (count($daysOfWeek)) {
			// Exclude the days of week that aren't included (allDaysOfWeek() - $daysOfWeek)
			$daysOfWeek = $this->normalizeDaysOfWeek($daysOfWeek);
			$excludedDays = array_diff($this->allDaysOfWeek(), $daysOfWeek);
			$this->excludeDaysOfWeek($excludedDays);
		}
	}

	function excludeDayOfWeek($dayOfWeek) {
		$this->excludeDateForFormat($this->normalizeDayOfWeek($dayOfWeek), 'w');
	}

	function excludeDaysOfWeek($daysOfWeek) {
		foreach ($daysOfWeek as $dayOfWeek) {
			$this->excludeDayOfWeek($dayOfWeek);
		}
	}

	function normalizeDayOfWeek($dayOfWeek) {
		if (is_numeric($dayOfWeek)) return $dayOfWeek;
		return date('w', strtotime($dayOfWeek));
	}

	function normalizeDaysOfWeek($daysOfWeek) {
		foreach ($daysOfWeek as &$dayOfWeek) $dayOfWeek = $this->normalizeDayOfWeek($dayOfWeek);
		return $daysOfWeek;
	}

	function allDaysOfWeek() {
		return range(0, 6);
	}

	/*
	Start Time and Date
	*/

	function setStartTime($startTime) {
		$this->_startTime = $this->normalizeTime($startTime);
	}

	function startTime() {
		return $this->_startTime;
	}

	function setStartDate($startDate) {
		$this->setStartTime(strtotime($startDate));
	}

	function startDate($dateFormat = '') {
		return $this->date($this->startTime(), $dateFormat);
	}

	/*
	Other times/dates
	*/

	private $_dates = array();
	function dates() {
		return $this->_dates;
	}

	function numDates() {
		return count($this->_dates);
	}

	function lastDate() {
		if ($numDates = $this->numDates()) return $this->_dates[$numDates - 1];
		return false;
	}

	function endDate() {
		// NOTE: endDate() is not the last date in the plan, but the next date after the last date
		if ($this->endOnAnIncludedDate) $time = $this->nextIncludedTime();
		else $time = $this->nextTime();

		return $this->date($time);
	}

	function latestDateIndex($time = 0) {
		// Returns the index of the latest date that occurs before or on $time
		// Thus, this can be used to find the date of the current reading in the plan

		$time = $this->normalizeTime($time);

		$latestDateIndex = 0;

		// Search each date until we find one that is after the target time; use the date before that
		foreach ($this->dates() as $index => $date) {
			// If we've past the target time, then we already have the latest date index
			if (strtotime($date) > $time) break;
			// otherwise, this index is a latestDateIndex candidate
			$latestDateIndex = $index;
		}

		return $latestDateIndex;
	}

	/*
	Pushing new dates
	*/

	private $_nextTime;
	function nextTime() {
		if (!$this->_nextTime) {
			$this->_nextTime = $this->startTime();
		}
		return $this->_nextTime;
	}

	private $_numReadingsLeftForCurrentDate;
	private function _incrementNextTime() {
		if (0 < $this->_numReadingsLeftForCurrentDate) {
			$this->_numReadingsLeftForCurrentDate--;
		}
		else {
			$this->_numReadingsLeftForCurrentDate = $this->readingsPerDate() - 1;

			// Find the next included time, this is the time that is actually used to push new dates (see pushNextDate()),
			// because nextTime() isn't always included, but nextIncludedTime() is
			$nextIncludedTime = $this->nextIncludedTime();

			// We need to increment nextTime until it is greater than the nextIncludedTime,
			// so that we keep moving forward, even past large chunks of excluded time
			while ($this->_nextTime <= $nextIncludedTime) $this->_nextTime = $this->incrementedTime($this->_nextTime);
		}
	}

	function pushNextDate() {
		$this->_dates []= $this->date($this->nextIncludedTime());
		$this->_incrementNextTime();
	}

	function pushNumDates($numDates) {
		for ($index = 0; $index < $numDates; $index++) {
			$this->pushNextDate();
		}
	}
}

?>