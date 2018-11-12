
<?php

require __DIR__.'/vendor/autoload.php';

use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Session;


//$driver = new GoutteDriver();
$driver = new Selenium2Driver('chrome');

$session = new Session($driver);
$session->start();

$session->visit('http://jurassicpark.wikia.com');

//echo "Status code: " . $session->getStatusCode() . "\n";
echo "Current URL: " . $session->getCurrentUrl() . "\n";

// DocumentElement
$page = $session->getPage();
echo "First 75 chars: " . substr($page->getText(), 0, 75) . "\n";

$header = $page->find('css', '.wds-community-header__sitename a');
echo "The wiki site name is: " . $header->getText()."\n";

$nav = $page->find('css', '.wds-tabs');
$linkEl = $nav->find('css', 'li a');
echo "The link text is: " . $linkEl->getText() . "\n";

$selectorHandler = $session->getSelectorsHandler();
$linkEl = $page->findLink('Wiki Activity');

$linkEl->click();
echo "Page URL after click: " . $session->getCurrentUrl() . "\n";

$session->stop();
