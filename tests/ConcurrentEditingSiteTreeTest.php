<?php


/**
 * Test the class @see ConcurrentEditingSiteTree
 * @covers ConcurrentEditingSiteTree
 */
class ConcurrentEditingSiteTreeTest extends SapphireTest {
	// static $fixture_file = '.yml';

	// /**
	//  * Setup any fixture items, etc.
	//  */
	// public function setUp() {
	// 	parent::setUp();
	// }

	/**
	 * Test the function @see ConcurrentEditingSiteTree::onBeforeWrite
	 */
	function testOnBeforeWrite() {
		$page = new Page();
		$page->LastEditedByID = 123;
		$page->Title = 'test';
		$page->URLSegment = 'test';
		$page->write();
		$this->assertEquals(Member::currentUserID(), $page->LastEditedByID, "LastEditedByID was not that of current user");
	}

}



?>
