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
			'defaults' => array(
				'SaveCount' => '0'
			),
			'many_many' => array(
				'UsersCurrentlyEditing' => 'Member'
			),
			'many_many_extraFields' => array(
				'UsersCurrentlyEditing' => array(
					'LastPing' => 'SSDatetime'
				)
			)
		);
	}
	
	function updateCMSFields(&$fields) {
		$alert = new LiteralField("SiteTree_Alert", '<div deletedfromstage="'.((int) $this->owner->getIsDeletedFromStage()).'" savecount="'.$this->owner->SaveCount.'" id="SiteTree_Alert"></div>');
		$fields->insertBefore($alert, 'Root');
	}
}