<?php
/**
 * @package concurrentediting
 */
class ConcurrentEditingLeftAndMain extends LeftAndMainDecorator {
	static $allowed_actions = array(
		'concurrentEditingPing',
		'restoreRemotelyDeleted',
	);
	
	static $edit_timeout = 10;
	static $page_ping_interval = 3;
	static $overwrite_display_duration = 20;
	
	
	function init() {
		parent::init();
		Requirements::javascript('concurrentediting/javascript/ConcurrentEditing.js');
	}
	
	function concurrentEditingPing() {
		if (!isset($_REQUEST['ID'])) die('no id passed');
		
		$page = $this->owner->getRecord($_REQUEST['ID']);
		if (!$page) {
			// Page has not been found
			$return = array('status' => 'not_found');
		} elseif ($page->getIsDeletedFromStage()) {
			// Page has been deleted from stage
			$return = array(
				'status' => 'deleted',
				'restoreDeletedUrl' => 'admin/restoreRemotelyDeleted?ID=' . (int)$page->ID,
				'viewDeletedUrl' => 'admin/show/' . (int)$page->ID,
			);
		} else {
			// Mark me as editing if I'm not already
			$page->UsersCurrentlyEditing()->add(Member::currentUser());
			DB::query("UPDATE \"SiteTree_UsersCurrentlyEditing\" SET \"LastPing\" = '".date('Y-m-d H:i:s')."'
				WHERE \"MemberID\" = ".Member::currentUserID()." AND \"SiteTreeID\" = {$page->ID}");
			
			// Page exists, who else is editing it?
			$names = array();
			foreach($page->UsersCurrentlyEditing() as $user) {
				if ($user->ID == Member::currentUserId()) continue;
				$names[] = trim($user->FirstName . ' ' . $user->Surname);
			}
			$return = array('status' => 'editing', 'names' => $names, 'isLastEditor' => false);
			if ($page->LastEditedByID == Member::currentUserID()) {
				$return['isLastEditor'] = true;
				$lastTwoVersions = $page->allVersions('', '', 2)->toArray();
				if (count($lastTwoVersions) >= 2) {
					$url = "admin/compareversions/{$page->ID}/?From={$lastTwoVersions[1]->Version}&To={$page->Version}";
					$link = "<a href=\"{$url}\">here</a>";
					$return['compareVersionsLink'] = $link;
					$member = DataObject::get_by_id('Member', $lastTwoVersions[1]->LastEditedByID);
					if ($member) {
						$return['lastEditor'] = Member::currentUserID() == $member->ID ? 'myself' : $member->getTitle();
					}
				}
			}
			// Has it been published since the CMS first loaded it?
			$usersSaveCount = isset($_REQUEST['SaveCount']) ? $_REQUEST['SaveCount'] : $page->SaveCount;
			if ($usersSaveCount < $page->SaveCount) {
				$return['status'] = 'not_current_version';
			}
		}
		
		// Delete pings older than *timeout* from the cache...
		DB::query("DELETE FROM \"SiteTree_UsersCurrentlyEditing\" WHERE \"LastPing\" < '".date('Y-m-d H:i:s', time()-self::$edit_timeout)."'");
		
		return Convert::array2json($return);
	}
	
	function restoreRemotelyDeleted() {
		$response = singleton('CMSMain')->restore();
	}
	
	function onAfterSave(&$record) {
		$record->SaveCount++;
		$record->writeWithoutVersion();
		FormResponse::add('CurrentPage.setSaveCount('.$record->SaveCount.');');
	}
}