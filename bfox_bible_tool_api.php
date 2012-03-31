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

/**
 * Class for an external Bible Tool on another server
 *
 * Loads content with a simple file_get_contents($url) call.
 *
 * For Javascript based APIs use subclasses:
 * @see BfoxExternalJSBibleToolApi
 * @see BfoxExternalJSONPBibleToolApi
 * @see BfoxExternalCustomJSONPBibleToolApi
 *
 * For embedding an external resource in an iframe use subclass:
 * @see BfoxBibleToolIframeApi
 *
 */
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

/**
 * Loads an external Bible tool inside of an iframe
 *
 */
class BfoxBibleToolIframeApi extends BfoxExternalBibleToolApi {
	function echoContentForUrl($url) {
?>
	<iframe class="bfox-iframe" src="<?php echo $url ?>"></iframe>
<?php
	}
}

/**
 * Loads an external Bible tool by embedding Javascript
 *
 */
class BfoxExternalJSBibleToolApi extends BfoxExternalBibleToolApi {

	function echoContentForUrl($url) {
?>
		<script type="text/javascript" src="<?php echo $url; ?>"></script>

<?php
	}
}

/**
 * Loads an external Bible tool by embedding Javascript using a JSONP API
 *
 * JSONP allows an API call to request the content be wrapped in a callback.
 * This class declares a JS callback that the API then sends the content to,
 * embedding the content in our webpage.
 *
 */
class BfoxExternalJSONPBibleToolApi extends BfoxExternalJSBibleToolApi {
	var $callbackName;
	var $callbackDataVar;

	function __construct($urlTemplate, $callbackName, $callbackDataVar) {
		parent::__construct($urlTemplate);
		$this->callbackName = $callbackName;
		$this->callbackDataVar = $callbackDataVar;
	}

	/**
	 * Creates javascript for declaring all the members of a callback
	 */
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

	/**
	 * Creates javascript for defining the callback function
	 */
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

/**
 * Loads an external Bible tool by embedding Javascript using a JSONP API
 *
 * JSONP allows an API call to request the content be wrapped in a callback.
 * This class declares a custom JS callback that the API then sends the content to,
 * embedding the content in our webpage.
 *
 */
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

/*
 *
 * Sample Bible APIs
 *
 */

/**
 * API used by the great RefTagger plugin, loaded by Javascript
 *
 * A great API with a lot of Bible versions, but it only returns a few verses max
 *
 * @see http://reftagger.com/
 *
 */
class BfoxRefTaggerApi extends BfoxExternalCustomJSONPBibleToolApi {
	function __construct($bible) {
		$this->availableBibles = array('ESV', 'NIV');

		parent::__construct('http://biblia.com/bible/%bible%/%ref%?target=reftagger&callback=%apiCallback%', 'content');

		$this->linker->urlEncodeCallback = 'rawurlencode';
		$this->setBible($bible);
	}
}

/**
 * Official NET Bible API
 *
 * @see http://labs.bible.org/api_web_service
 *
 */
class BfoxNETBibleApi extends BfoxExternalBibleToolApi {
	function __construct($apiKey = '') {
		$this->availableBibles = array('NET');

		parent::__construct('http://labs.bible.org/api/?passage=%ref%&formatting=full', $apiKey);
	}
}

/**
 * Official NET Bible API loaded by Javascript
 *
 * We don't recommend this one because it adds copyright notices to every verse.
 * Instead, we recommend the API that is used by the official NET Bible Tagger plugin,
 * which just puts one copyright notice at the end of all the verses.
 *
 * Use BfoxNETBibleTaggerApi instead
 * @see BfoxNETBibleTaggerApi
 *
 * @see http://labs.bible.org/api_web_service
 *
 */
class BfoxNETBibleJavaScriptApi extends BfoxExternalCustomJSONPBibleToolApi {
	function __construct() {
		$this->availableBibles = array('NET');

		parent::__construct('http://labs.bible.org/api/?passage=%ref%&type=json&callback=%apiCallback%', 'text');
	}
}

/**
 * Official NET Bible API loaded by Javascript, used by their NET Bible Tagger plugin
 *
 * This API is better than the regular NET Bible API because it
 * just puts one copyright notice at the end of all the verses.
 *
 * @see http://labs.bible.org/NETBibleTagger
 *
 */
class BfoxNETBibleTaggerApi extends BfoxExternalJSONPBibleToolApi {
	function __construct() {
		$this->availableBibles = array('net');

		parent::__construct('http://labs.bible.org/api/NETBibleTagger/v2/script_get_verse.php?passage=%ref%&translation=%bible%', 'org.bible.NETBibleTagger.jsonCallback', 'content');

		$this->setBible('net');
	}
}

/**
 * Official ESV API
 *
 * @see http://www.esvapi.org/
 *
 */
class BfoxESVApi extends BfoxExternalBibleToolApi {
	function __construct($apiKey = '') {
		if (empty($apiKey)) $apiKey = 'IP';

		$this->availableBibles = array('esv');

		parent::__construct('http://www.esvapi.org/v2/rest/passageQuery?key=%apiKey%&passage=%ref%', $apiKey);

		$this->setBible('esv');
	}
}

/**
 * Official ESV API loaded by Javascript
 *
 * @see http://www.esvapi.org/
 *
 */
class BfoxESVJavaScriptApi extends BfoxExternalJSBibleToolApi {
	function __construct() {
		$this->availableBibles = array('esv');

		parent::__construct('http://www.gnpcb.org/%bible%/share/js/?action=doPassageQuery&passage=%ref%&include-audio-link=0&include-copyright=true');

		$this->setBible('esv');
	}
}

/**
 * The Biblia API, made by Logos (requires API Key)
 *
 * This API has a lot of available versions, but requires an API key
 *
 * @see http://api.biblia.com/
 *
 */
class BfoxBibliaApi extends BfoxExternalBibleToolApi {
	function __construct($bible, $apiKey) {
		$this->availableBibles = array('LEB');

		parent::__construct('http://api.biblia.com/v1/bible/content/%bible%.html?passage=%ref%&style=fullyFormatted&key=%apiKey%', $apiKey);

		$this->linker->urlEncodeCallback = 'rawurlencode';
		$this->setBible($bible);
	}
}

?>