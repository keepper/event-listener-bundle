<?php
namespace Keepper\EventListenerBundle\Tests\Fixtures;

use Keepper\EventListener\Tests\Fixtures\SomeListenerInterface;

class SomeListener implements SomeListenerInterface {

	static public $inited = false;

	public function __construct() {
		self::$inited = true;
	}

	public $called = [];

	public function onSomeEvent() {
		$this->called[] = 1;
	}
}