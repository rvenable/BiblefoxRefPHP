<?php

class BfoxPlanParser {

	var $readingRefs = array();
	var $readingLeftovers = array();

	function newReadingRefParser() {
		$parser = new BfoxRefParser;
		$parser->total_ref = new BfoxRef; // Save total_ref
		$parser->leftovers = ''; // Save leftovers if not null
		$parser->max_level = 2; // Include all book abbreviations

		return $parser;
	}

	function readingRefStrings() {
		$refStrings = array();
		foreach ($this->readingRefs as $readingRef) {
			$refStrings []= $readingRef->get_string();
		}
		return $refStrings;
	}

	function addReading($ref, $leftovers = '') {
		$this->readingRefs []= $ref;
		$this->readingLeftovers []= $leftovers;
	}

	function parseReading($reading) {
		$parser = $this->newReadingRefParser();
		$leftovers = $parser->parse_string($reading);

		if ($parser->total_ref->is_valid()) {
			$this->addReading($parser->total_ref, $leftovers);
		}
	}

	function parseReadings($readings) {
		foreach ((array)$readings as $reading) {
			$this->parseReading($reading);
		}
	}

	function parseContent($content, $readingDelimiter) {
		$this->parseReadings(explode($readingDelimiter, $content));
	}

	function parsePassagesIntoReadings($passages, $readingSize, $allowPassageGroups = true) {
		$readingSize = max(1, $readingSize);

		if ($allowPassageGroups) $ref = BfoxRefParser::with_groups($passages); // Allow groups to be used
		else $ref = new BfoxRef($passages);

		if ($ref->is_valid()) {
			$readingRefs = $ref->get_sections($readingSize);

			foreach ($readingRefs as $readingRef) if ($readingRef->is_valid()) {
				$this->addReading($readingRef);
			}
		}

		return $readings;
	}

}

?>