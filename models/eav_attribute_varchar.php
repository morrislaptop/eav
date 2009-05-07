<?php
	class EavAttributeVarchar extends EavAppModel {

		var $fileTypes = array('image', 'flash', 'file');

		function save($data = null, $validate = true, $fieldList = array()) {
			// find the attribute for this varchar and determine if its a file upload
			$conditions = array(
				'id' => $data['attribute_id']
			);
			$type = $this->EavAttribute->field('type', $conditions);

			if ( in_array($type, $this->fileTypes) ) {
				$this->Behaviors->attach('MeioUpload', array('value' => array(
					'dir' => 'files{DS}{model}{DS}{field}'
				)));
			}

			$return = parent::save($data, $validate, $fieldList);
			return $return;
		}
	}
?>