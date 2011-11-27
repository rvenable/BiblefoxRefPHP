<?php

/**
 * Abstract base class for Bible Tool APIs
 *
 */
abstract class BfoxBibleToolApi {
	/**
	 * Echos the content associated with a Bible reference
	 * @param BfoxRef $ref
	 */
	abstract function echoContentForRef(BfoxRef $ref);
}

/**
 * Abstract base class for Bible Tools that store their data in a local relational database
 *
 * Subclasses must implement rowsForRef() to return the row data for a given BfoxRef
 *
 */
abstract class BfoxLocalBibleToolApi extends BfoxBibleToolApi {
	var $tableName;
	var $contentCol;
	var $indexCol;
	var $indexCol2;

	function __construct($tableName, $contentCol, $indexCol, $indexCol2 = '') {
		$this->tableName = $tableName;
		$this->contentCol = $contentCol;
		$this->indexCol = $indexCol;
		$this->indexCol2 = $indexCol2;
	}

	abstract function rowsForRef(BfoxRef $ref);

	function contentForRow($row) {
		$contentCol = $this->contentCol;
		return $row->$contentCol;
	}

	function contentForRef(BfoxRef $ref) {
		$rows = $this->rowsForRef($ref);

		$content = '';
		foreach ($rows as &$row) {
			$content .= $this->contentForRow(&$row);
		}
		return $content;
	}

	function echoContentForRef(BfoxRef $ref) {
		echo $this->contentForRef($ref);
	}
}

class BfoxExternalBibleToolApi extends BfoxBibleToolApi {
	var $urlTemplate;
	var $linker;
	var $availableBibles = array();
	var $bible;
	var $apiKey;

	function __construct($urlTemplate, $apiKey = '') {
		$this->urlTemplate = $urlTemplate;
		$this->apiKey = $apiKey;
		$this->linker = new BfoxBibleToolLink();
		$this->linker->varValues['%apiKey%'] = $this->apiKey;
	}

	function setBible($bible) {
		$this->bible = $bible;
		$this->linker->varValues['%bible%'] = $this->bible;
	}

	function urlForRef(BfoxRef $ref) {
		$this->linker->setRef($ref);
		return $this->linker->urlForTemplate($this->urlTemplate);
	}

	function echoContentForRef(BfoxRef $ref) {
		$this->echoContentForUrl($this->urlForRef($ref));
	}

	function echoContentForUrl($url) {
		echo file_get_contents($url);
	}
}

class BfoxBibleToolIframeApi extends BfoxExternalBibleToolApi {
	function echoContentForUrl($url) {
?>
	<iframe class="bfox-iframe" src="<?php echo $url ?>"></iframe>
<?php
	}
}

class BfoxExternalJSBibleToolApi extends BfoxExternalBibleToolApi {

	function echoContentForUrl($url) {
?>
		<script type="text/javascript" src="<?php echo $url; ?>"></script>

<?php
	}
}

class BfoxExternalJSONPBibleToolApi extends BfoxExternalJSBibleToolApi {
	var $callbackName;
	var $callbackDataVar;

	function __construct($urlTemplate, $callbackName, $callbackDataVar) {
		parent::__construct($urlTemplate);
		$this->callbackName = $callbackName;
		$this->callbackDataVar = $callbackDataVar;
	}

	// Creates javascript for declaring all the members of a callback
	function declareCallbackObjects() {
		$callbackObjects = explode('.', $this->callbackName);
		$allObjects = '';

		// Make a var statement with the first callback object
?>
			var <?php echo $callbackObjects[0]; ?>;
<?php

		// Create each callback object
		for ($index = 0; $index < count($callbackObjects) - 1; $index++) {
			$cb_obj = $callbackObjects[$index];
			if (!empty($allObjects)) {
				$allObjects .= '.';
			}
			$allObjects .= $cb_obj;
?>
			if (!<?php echo $allObjects; ?>) <?php echo $allObjects; ?> = {};
<?php
		}
	}

	// Creates javascript for defining the callback function
	function defineCallback() {
?>
			<?php echo $this->callbackName; ?> = function(data) {
				if (Object.prototype.toString.call(data) === '[object Array]') {
					for (var i = 0; i < data.length; i++) {
						document.write(data[i].<?php echo $this->callbackDataVar; ?>);
					}
				}
				else {
					document.write(data.<?php echo $this->callbackDataVar; ?>);
				}
			};

<?php
	}

	function echoContentForUrl($url) {
?>
		<script type="text/javascript">
<?php $this->declareCallbackObjects(); ?>
<?php $this->defineCallback(); ?>
		</script>
<?php

		parent::echoContentForUrl($url);
	}
}

class BfoxExternalCustomJSONPBibleToolApi extends BfoxExternalJSONPBibleToolApi {

	function __construct($urlTemplate, $callbackDataVar) {
		// No callback name because a new one is created with each echoContentForRef
		parent::__construct($urlTemplate, '', $callbackDataVar);
	}

	static private $callbackCreationCount = 0;
	private static function createCallbackName($base = 'bfoxExternalCustomJSONPBibleToolApiCallback') {
		$base .= self::$callbackCreationCount;
		self::$callbackCreationCount++;
		return $base;
	}

	function callbackName() {
		return $this->callbackName;
	}

	function urlForRef(BfoxRef $ref) {
		$this->linker->setRef($ref);
		return $this->linker->urlForTemplate($this->urlTemplate);
	}

	function echoContentForRef(BfoxRef $ref) {
		$this->callbackName = self::createCallbackName();
		$this->linker->varValues['%apiCallback%'] = $this->callbackName;
		parent::echoContentForRef($ref);
	}
}

class BfoxRefTaggerApi extends BfoxExternalCustomJSONPBibleToolApi {
	function __construct($bible) {
		$this->availableBibles = array('ESV', 'NIV');

		parent::__construct('http://biblia.com/bible/%bible%/%ref%?target=reftagger&callback=%apiCallback%', 'content');

		$this->linker->urlEncodeCallback = 'rawurlencode';
		$this->setBible($bible);
	}
}

class BfoxNETBibleApi extends BfoxExternalCustomJSONPBibleToolApi {
	function __construct() {
		$this->availableBibles = array('NET');

		parent::__construct('http://labs.bible.org/api/?passage=%ref%&type=json&callback=%apiCallback%', 'text');
	}
}

class BfoxNETBibleTaggerApi extends BfoxExternalJSONPBibleToolApi {
	function __construct() {
		$this->availableBibles = array('net');

		parent::__construct('http://labs.bible.org/api/NETBibleTagger/v2/script_get_verse.php?passage=%ref%&translation=%bible%', 'org.bible.NETBibleTagger.jsonCallback', 'content');

		$this->setBible('net');
	}
}

class BfoxESVJavaScriptApi extends BfoxExternalJSBibleToolApi {
	function __construct() {
		$this->availableBibles = array('esv');

		parent::__construct('http://www.gnpcb.org/%bible%/share/js/?action=doPassageQuery&passage=%ref%&include-audio-link=0&include-copyright=true');

		$this->setBible('esv');
	}
}

class BfoxBibliaApi extends BfoxExternalBibleToolApi {
	function __construct($bible, $apiKey) {
		$this->availableBibles = array('LEB');

		parent::__construct('http://api.biblia.com/v1/bible/content/%bible%.html?passage=%ref%&style=fullyFormatted&key=%apiKey%', $apiKey);

		$this->linker->urlEncodeCallback = 'rawurlencode';
		$this->setBible($bible);
	}
}

?>