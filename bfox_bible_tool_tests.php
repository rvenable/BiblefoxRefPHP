<?php

require_once 'biblefox-ref.php';
require_once 'bfox_bible_tool_link.php';

function urlTemplateAssert($link, $template, $target) {
	$url = $link->urlForTemplate($template);
	if (!assert($url == $target)) {
		echo "'$url' != '$target'\n";
	}
}

$link = new BfoxBibleToolLink;
$link->setRef(new BfoxRef('Gen 1'));
urlTemplateAssert($link, 'http://www.biblegateway.com/passage/?search=%ref%&version=NIV', 'http://www.biblegateway.com/passage/?search=Genesis+1&version=NIV');
urlTemplateAssert($link, 'http://www.biblegateway.com/passage/?search=%ref%&version=NIV&interface=print', 'http://www.biblegateway.com/passage/?search=Genesis+1&version=NIV&interface=print');

?>