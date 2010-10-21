<?php
/**
 * @package concurrentediting
 */
class ConcurrentEditingSiteTree extends DataObjectDecorator {
	function extraStatics() {
		return array(
			'db' => array(
				'SaveCount' => 'Int'
			),
			'has_one' => array(
				'LastEditedBy' => 'Member'
			),
			'defaults' => array(
				'SaveCount' => '0'
			),
			'many_many' => array(
				'UsersCurrentlyEditing' => 'Member'
			),
			'many_many_extraFields' => array(
				'UsersCurrentlyEditing' => array(
					'LastPing' => 'SS_Datetime'
				)
			)
		);
	}
	
	function updateCMSFields(&$fields) {
		$alert = new LiteralField("SiteTree_Alert", '<div deletedfromstage="'.((int) $this->owner->getIsDeletedFromStage()).'" savecount="'.$this->owner->SaveCount.'" id="SiteTree_Alert"></div>');
		$fields->insertBefore($alert, 'Root');
	}

	function onBeforeWrite() {
		if($this->owner->isChanged()) $this->owner->LastEditedByID = Member::currentUserID();
	}
}
