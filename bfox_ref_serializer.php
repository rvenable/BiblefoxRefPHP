<?php

/**
 * Class for creating strings out of BfoxRef
 *
 */
class BfoxRefSerializer {
	/**
	 * Book name format
	 * @var string
	 */
	var $bookNameFormat = BibleMeta::name_normal;

	/**
	 * Punctuation strings
	 */
	var $punctuation = array(
		'book' => array(
			'separator' => '; ',
			'connector' => ' - ',
			'parentConnector' => '',
			),
		'chapter' => array(
			'separator' => '; ',
			'connector' => '-',
			'parentConnector' => ' ',
			),
		'verse' => array(
			'separator' => ',',
			'connector' => '-',
			'parentConnector' => ':',
			),
	);
	var $levelKeys = array('book', 'chapter', 'verse');

	private static $_sharedInstance = null;

	/**
	 * @return BfoxRefSerializer
	 */
	static function sharedInstance() {
		if (is_null(self::$_sharedInstance)) self::$_sharedInstance = new BfoxRefSerializer();
		return self::$_sharedInstance;
	}

	var $maximumDivergenceLevel = 2;

	/**
	 * Set to combine chapter strings (this is default)
	 *
	 * Ex. Genesis 1:1,3 becomes Genesis 1:1,3
	 */
	function setCombineChapters() {
		$this->maximumDivergenceLevel = 2;
	}

	/**
	 * Set to combine book strings (but not chapters)
	 *
	 * Ex. Genesis 1:1,3 becomes Genesis 1:1, 1:3
	 */
	function setCombineBooks() {
		$this->maximumDivergenceLevel = 1;
	}

	/**
	 * Set to not combine separate verse ranges at all
	 *
	 * Ex. Genesis 1:1,3 becomes Genesis 1:1, Genesis 1:3
	 */
	function setCombineNone() {
		$this->maximumDivergenceLevel = 0;
	}

	private $result = '';
	private $lastVerseVector = array();

	function reset() {
		$this->result = '';
		$this->lastVerseVector = array();
	}

	function popResult() {
		$result = $this->result;
		$this->reset();
		return $result;
	}

	function pushRef(BfoxRef $ref) {
		$ranges = $ref->refRanges();

		foreach ($ranges as $range) {
			$this->pushRefRange($range);
		}
	}

	function pushRefRange(BfoxRefRange $range) {
		$vector1 = $range->startVerseVector();
		$vector2 = $range->endVerseVector();

		// Correct 0s and 255s
		for ($level = 0; $level < count($vector1); $level++) {
			$value1 = &$vector1[$level];
			$value2 = &$vector2[$level];

			if ($value2 == BibleVerse::max_book_id) {
				if ($value1) $value2 = $this->passageEndAtLevel($vector2, $level);
				else $value2 = 0;
			}
			else if (!$value1) $value1 = 1;
		}

		$this->pushVerseVector($vector1, 'separator', $this->maximumDivergenceLevel);
		$this->pushVerseVector($vector2, 'connector');
	}

	function pushVerseVector($vector, $punctuation, $maxLevel = 3) {
		$level = 0;

		if (!empty($this->lastVerseVector)) {
			$level = min($this->levelOfDivergence($this->lastVerseVector, $vector), $maxLevel);
			$this->result .= $this->punctuationForLevel($punctuation, $level);
		}

		$this->result .= $this->stringForVector($vector, $level);
		$this->lastVerseVector = $vector;
	}

	function stringForVector($vector, $startLevel = 0) {
		$str = '';
		for ($level = $startLevel; $level < count($vector); $level++) {
			$value = $vector[$level];
			if (!$value) break;

			if ($level > $startLevel) $str .= $this->punctuationForLevel('parentConnector', $level);
			$str .= $this->stringForValueAtLevel($value, $level);
		}
		return $str;
	}

	/**
	 * Return the end of the passage at a certain level
	 */
	function passageEndAtLevel($vector, $level) {
		if (2 == $level) return BibleMeta::passage_end($vector[0], $vector[1]);
		if (1 == $level) return BibleMeta::passage_end($vector[0]);
		return BibleMeta::passage_end();
	}

	/**
	 * String for a value at a certain level
	 *
	 * The string is either a book name, a chapter number, or a verse number
	 */
	function stringForValueAtLevel($value, $level) {
		if (0 == $level) return BibleMeta::get_book_name($value, $this->bookNameFormat);
		return $value;
	}

	/**
	 * Returns the level (book, chapter, verse) at which two vectors diverge from each other
	 */
	function levelOfDivergence($vector1, $vector2) {
		for ($level = 0; $level < count($vector1); $level++) {
			if ($vector1[$level] != $vector2[$level]) return $level;
		}
		return $level;
	}

	/**
	 * Return the punctuation needed for a certain level
	 */
	function punctuationForLevel($punctuation, $level) {
		return $this->punctuation[$this->levelKeys[$level]][$punctuation];
	}
}

?>