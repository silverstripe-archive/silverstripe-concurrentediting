<?php


/**
 * Test the class @see ConcurrentEditingLeftAndMain
 * @covers ConcurrentEditingLeftAndMain
 */
class ConcurrentEditingLeftAndMainTest extends FunctionalTest {
	static $fixture_file = 'concurrentediting/tests/ConcurrentEditingLeftAndMainTest.yml';
	
	/**
	 * Test the function @see ConcurrentEditingLeftAndMain::concurrentEditingPing
	 */
	function testConcurrentEditingPing() {
		$this->user1 = $this->objFromFixture('Member', 'user1');
		$this->user2 = $this->objFromFixture('Member', 'user2');

		// Create a new page, make sure nobody is editing it.
		$page = new Page();
		$page->Title = 'Test page';
		$page->URLSegment = 'testpage';
		$page->Content = "Testing";
		$page->write();
		$this->assertEquals($page->UsersCurrentlyEditing()->Count(), 0);

		// Log in as this->user1, and make sure we correctly get a not_found for bad ID
		$this->user1->logIn();
		$url = 'admin/concurrentEditingPing?SaveCount=%d&ID=%d';
		$resp = $this->get(sprintf($url, 0, 9999));
		$json = Convert::json2array($resp->getBody());
		$this->assertEquals('not_found', $json['status'], "Expected not_found, got: " .var_export($json,1));

		// Check that this->user1 is now editing, and nobody else.
		$resp = $this->get(sprintf($url, $page->SaveCount, $page->ID));
		$this->assertEquals($page->UsersCurrentlyEditing()->Count(), 1);
		$json = Convert::json2array($resp->getBody());
		$this->assertEquals('editing', $json['status'], "Expected editing, got: " .var_export($json,1));
		$this->assertEquals(array(), $json['names'], "Expected no other editors, got: " .var_export($json,1));
		$this->assertEquals(false, $json['isLastEditor'], "Expected not to be last editor, got: " .var_export($json,1));

		// Log in as this->user2, check that we're editing the page
		// and that this->user1 is also shown as editing.
		$this->user2->logIn();
		$resp = $this->get(sprintf($url, $page->SaveCount, $page->ID));
		$this->assertEquals($page->UsersCurrentlyEditing()->Count(), 2);
		$json = Convert::json2array($resp->getBody());
		$this->assertEquals('editing', $json['status'], "Expected editing, got: " .var_export($json,1));
		$this->assertEquals(array('Test1 User1'), $json['names'], "Expected Test1 User1 is other editor, got: " .var_export($json,1));
		$this->assertEquals(false, $json['isLastEditor'], "Expected not to be last editor, got: " .var_export($json,1));
		
		// Check that we correctly get not_current_version if our savecount is different
		$resp = $this->get(sprintf($url, $page->SaveCount-1, $page->ID));
		$this->assertEquals($page->UsersCurrentlyEditing()->Count(), 2);
		$json = Convert::json2array($resp->getBody());
		$this->assertEquals('not_current_version', $json['status'], "Expected not_current_version, got: " .var_export($json,1));
		$this->assertEquals(array('Test1 User1'), $json['names'], "Expected Test1 User1 is other editor, got: " .var_export($json,1));
		$this->assertEquals(false, $json['isLastEditor'], "Expected not to be last editor, got: " .var_export($json,1));

		// Log in as this->user1 again and check that this->user2 is shown as editing
		$this->user1->logIn();
		$resp = $this->get(sprintf($url, $page->SaveCount-1, $page->ID));
		$this->assertEquals($page->UsersCurrentlyEditing()->Count(), 2);
		$json = Convert::json2array($resp->getBody());
		$this->assertEquals('not_current_version', $json['status'], "Expected not_current_version, got: " .var_export($json,1));
		$this->assertEquals(array('Test2 User2'), $json['names'], "Expected Test2 User2 is other editor, got: " .var_export($json,1));
		$this->assertEquals(false, $json['isLastEditor'], "Expected not to be last editor, got: " .var_export($json,1));

		// Save the page and check we're shown as lastEditor
		$oldVersion = $page->Version;
		$page->Title = 'new ' . $page->Title;
		$page->write();
		$resp = $this->get(sprintf($url, $page->SaveCount, $page->ID));
		$this->assertEquals($page->UsersCurrentlyEditing()->Count(), 2);
		$json = Convert::json2array($resp->getBody());
 		$this->assertEquals('editing', $json['status'], "Expected not_current_version, got: " .var_export($json,1));
		$this->assertEquals(array('Test2 User2'), $json['names'], "Expected Test2 User2 is other editor, got: " .var_export($json,1));
		$this->assertEquals(true, $json['isLastEditor'], "Expected to be last editor, got: " .var_export($json,1));
		$this->assertEquals("<a href=\"admin/compareversions/" . $page->ID . "/?From={$oldVersion}&To={$page->Version}\">here</a>",
			$json['compareVersionsLink'], "Expected From={$oldVersion}&To={$page->Version}, got: " . var_export($json,1));
	}

}