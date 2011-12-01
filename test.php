<?php

include 'biblefox-ref.php';

function pre($expr) {
	if (empty($expr)) $expr = '0 (empty)';
	echo '<pre>'; print_r($expr); echo '</pre>';
	return $expr;
}

class BfoxTests {

	function test_html_ref_replace() {
		$str = "<xml>
		<p>I like Gen 1. origen gen. gen. 1. song 5 Song of solomon 2</p>
		<p>What do you think? <a href=''>john 21</a> Do you prefer<d><d> ex 2 or 1sam 3 - 4 or 1 th 4? gen 3:4-8:2 gen 3ddd:2 fff- 1 1 3 </p>
		<p>gen lala yoyo 4:5</p>
		</xml>
		";

		echo $str;
		echo strip_tags($str);

		$start = microtime(true);
		$str = bfox_ref_replace_html($str);
		$end = microtime(true);

		echo $str;

		pre("start: $start");
		pre("end: $end");
		pre("Took " . ($end - $start) . " seconds");
	}

	/**
	 * Takes a bible ref string and uses it to create a BfoxRef to test BfoxRef for different inputs
	 *
	 * @param string $ref_str Bible Reference string to test
	 */
	private function test_ref($ref_str, $expected = '', $comment = '') {
		// Test setting a BfoxRef by a string
		$ref = new BfoxRef($ref_str);
		$result = $ref->get_string();
		if (empty($result)) $result = 'Invalid';

		// Test setting a BfoxRef by a set of unique ids
		$ref2 = new BfoxRef($ref);
		$result2 = $ref2->get_string();
		if (empty($result2)) $result2 = 'Invalid';

		echo "$ref_str -> <strong>$result</strong>";
		if (!empty($expected)) {
			if ($expected != $result) {
				echo " (ERROR: expected $expected)";
				$this->test_ref_unexpected++;
			}
			else echo " (Expected...)";
		}

		if ($result != $result2) {
			echo " (ERROR: Result2 not equal - $result2)";
			$this->test_ref_unequal++;
		}

		if ($comment) echo " // $comment";

		echo '<br/>';
	}

	/**
	 * Tests different bible reference input strings
	 *
	 */
	function test_refs() {
		$start = microtime(true);

		// Test the typical references
		$this->test_ref('1 sam', '1 Samuel');
		$this->test_ref('1sam 1', '1 Samuel 1');
		$this->test_ref('1sam 1-2', '1 Samuel 1-2');
		$this->test_ref('1sam 1:1', '1 Samuel 1:1');
		$this->test_ref('1sam 1:1-5', '1 Samuel 1:1-5');
		$this->test_ref('1sam 1:1-2:5', '1 Samuel 1:1-2:5');
		$this->test_ref('1sam 1:2-2:5', '1 Samuel 1:2-2:5');
		$this->test_ref('1sam 1-2:5', '1 Samuel 1:1-2:5');

		// Test Serializer options
		$serializer = BfoxRefSerializer::sharedInstance();
		$serializer->setCombineNone();
		$this->test_ref('Gen 1:1,3', 'Genesis 1:1; Genesis 1:3');
		$serializer->setCombineBooks();
		$this->test_ref('Gen 1:1,3', 'Genesis 1:1; 1:3');
		$serializer->setCombineChapters();
		$this->test_ref('Gen 1:1,3', 'Genesis 1:1,3');

		// Test periods
		$this->test_ref('1sam. 1', '1 Samuel 1');
		$this->test_ref('1sam 1.1', '1 Samuel 1:1');
		$this->test_ref('1sam 1.1-5', '1 Samuel 1:1-5');
		$this->test_ref('1sam 1.1-2.5', '1 Samuel 1:1-2:5');
		$this->test_ref('1sam 1.2-2.5', '1 Samuel 1:2-2:5');
		$this->test_ref('1sam 1-2.5', '1 Samuel 1:1-2:5');

		// This test was failing (see bug 21)
		$this->test_ref('Judges 2:6-3:6', 'Judges 2:6-3:6');

		// Test ignore words
		$this->test_ref('Book of Judges 2', 'Judges 2');
		$this->test_ref('First Book of Judges 2', 'error', 'This one should not work, but I dont remember why not...'); // This one should not work!
		$this->test_ref('First Book of Samuel 2', '1 Samuel 2');

		// Test that we can match synonyms with multiple words
		$this->test_ref('Song of Solomon 2', 'Song of Solomon 2');

		// This should be Gen 1:1, 1:3 - 2:3
		$this->test_ref('gen 1:1,3-2:3', 'Genesis 1:1,3-2:3');

		$this->test_ref('gen 1-100', 'Genesis');
		$this->test_ref('gen 2-100', 'Genesis 2-50');
		$this->test_ref('gen 49:1-100', 'Genesis 49');
		$this->test_ref('gen 49:2-100', 'Genesis 49:2-33');
		$this->test_ref('gen 50:1-100', 'Genesis 50');
		$this->test_ref('gen 50:2-100', 'Genesis 50:2-26');
		$this->test_ref('gen 50:1,2-100', 'Genesis 50');
		$this->test_ref('gen 50:1,3-100', 'Genesis 50:1,3-26');

		// Test min/max in Romans 14
		$this->test_ref('rom 14:2-100', 'Romans 14:2-26');
		$this->test_ref('rom 14:1-22', 'Romans 14:1-22');
		$this->test_ref('rom 14:1-23', 'Romans 14');
		$this->test_ref('rom 14:2-23', 'Romans 14:2-26');

		// Test having consecutive books
		$this->test_ref('Gen 2-100, Exodus', 'Genesis 2 - Exodus 40');
		$this->test_ref('Gen 2-100, Exodus, Lev', 'Genesis 2 - Leviticus 27');

		// Test long strings with lots of garbage
		$this->test_ref('hello dude genesis 1,;,2 gen 5 1 sam 4, song ;of song 3', 'Genesis 1-2; 5; 1 Samuel 4; Song of Solomon'); // TODO3: words like song get detected as the entire book Song of Solomon
		$this->test_ref("<xml>
		<p>I like Gen 1.</p>
		<p>What do you think? john. 21 Do you prefer<d><d> ex 2 or 1sam 3 - 4 or 1 th 4? gen 3:4-8:2 gen 3ddd:2 fff- 1 1 3 </p>
		<p>exodus lala yoyo 4:5</p>
		</xml>
		", 'Genesis 1; 3:1-8:2; Exodus; 1 Samuel 3-4; John 21; 1 Thessalonians 4'); // TODO3: 'ex' is not detected because it is only 2 letters

		// Test non-existent chapter
		$this->test_ref('2jhn 2', 'Invalid', 'non-existent chapter');
		$this->test_ref('hag 3', 'Invalid', 'non-existent chapter');
		$this->test_ref('hag 2-3', 'Haggai 2', 'ends with non-existent chapter');
		$this->test_ref('hag 1-3', 'Haggai', 'ends with non-existent chapter');
		$this->test_ref('hag 5-7', 'Invalid', 'non-existent chapters');
		$this->test_ref('hag 1:100-2:4', 'Haggai 2:1-4', 'begins with non-existent verse');

		// This test fails unless parsing in reverse
		$this->test_ref('Genesis 3; 1 Samuel 5', 'Genesis 3; 1 Samuel 5', 'Only works in reverse');

		$end = microtime(true);
		pre("start: $start");
		pre("end: $end");
		pre("Took " . ($end - $start) . " seconds");

		pre("Unexpected: $this->test_ref_unexpected");
		pre("Unequal: $this->test_ref_unequal");
	}

	public function test_sub_ref() {

		$start = 'eze 14,15';
		$subs = array('eze 14:3', 'eze 20; 22', 'eze 14:5-15:2', 'eze 14:1-2', 'eze 14:4', 'eze 15:7-9', 'eze 13-16', 'eze 17; 19');

		$ref = new BfoxRef($start);
		pre("start:" . $ref->get_string());

		foreach ($subs as $sub) {
			$sub_ref = new BfoxRef($sub);
			$is_modified = $ref->sub_ref($sub_ref);
			pre(" - " . $sub_ref->get_string() . " = " . $ref->get_string() . ' modified? ' . ($is_modified ? 'yes' : 'no'));
			//pre($ref);
		}
	}

	public function test_add_ref() {

		$start = 'eze 14:10';
		$adds = array('eze 14:8-9', 'eze 14:6-7', 'eze 14:5-8', 'eze 14:4', 'eze 14:4-11',
			'eze 14:5', 'eze 14:4-11', 'eze 14:4-5', 'eze 14:10-11', // should not modify
			'eze 14:1-2', 'eze 14:3-12',
			'eze 13', 'eze 14:13-22', 'eze 15:1-100', 'eze 14:23');
		//$adds = array('eze 14:10');

		$ref = new BfoxRef($start);
		pre("start:" . $ref->get_string());

		foreach ($adds as $add) {
			$add_ref = new BfoxRef($add);
			$is_modified = $ref->add_ref($add_ref);
			pre(" + " . $add_ref->get_string() . " = " . $ref->get_string() . ' modified? ' . ($is_modified ? 'yes' : 'no'));
			//pre($ref);
		}
	}
}

$test = new BfoxTests();
$test->test_refs();

?>