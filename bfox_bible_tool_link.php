<?php

class BfoxBibleToolLink {
	var $varCallbacks;
	var $varCallbackParams = array();
	var $varValues = array();

	function __construct() {
		$this->varCallbacks = array(
			'%ref%' => array($this, 'refString'),
			'%book%' => array($this, 'bookName'),
			'%chapter%' => array($this, 'chapterNum'),
			'%verse%' => array($this, 'verseNum'),
		);
	}

	private $_ref;
	function setRef(BfoxRef $ref) {
		$this->_ref = $ref;
		$this->_bookName = '';
		$this->_chapterNum = $this->_verseNum = 0;

		if ($this->_ref->is_valid()) {
			$bcvs = BfoxRef::get_bcvs($this->_ref->get_seqs());
			$books = array_keys($bcvs);
			$this->_bookName = BibleMeta::get_book_name($books[0]);

			$cvs = array_shift($bcvs);
			$cv = array_shift($cvs);
			list($this->_chapterNum, $this->_verseNum) = $cv->start;
		}

		foreach ($this->varCallbacks as $var => $callback) {
			$this->varValues[$var] = call_user_func_array($callback, (array)$this->varCallbackParams[$var]);
		}
	}

	function ref() {
		return $this->_ref;
	}

	function urlForTemplate($urlTemplate, $caseSensitive = false) {
		$vars = array_keys($this->varValues);
		$values = array_values($this->varValues);
		if ($caseSensitive) {
			return str_replace($vars, $values, $urlTemplate);
		}
		else {
			return str_ireplace($vars, $values, $urlTemplate);
		}
	}

	function refString() {
		return urlencode($this->_ref->get_string());
	}

	private $_bookName;
	function bookName() {
		return urlencode($this->_bookName);
	}

	private $_chapterNum;
	function chapterNum() {
		return $this->_chapterNum;
	}

	private $_verseNum;
	function verseNum() {
		return $this->_verseNum;
	}
}

?>