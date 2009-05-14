<?php
	class EavAttributeVarchar extends EavAppModel {

		var $fileTypes = array('image', 'flash', 'file');

		/**
		* Attaches an upload behavior to save file uploads automatically.
		*
		* @param mixed $data
		* @param mixed $validate
		* @param mixed $fieldList
		* @return array
		*/
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

		/**
		* Modifys paths to files so they are web friendly.
		*
		* @param mixed $results
		* @return mixed
		*/
		function afterFind($results) {
			foreach ($results as &$result) {
				if ( isset($result['EavAttributeVarchar']['value']) ) {
					$result['EavAttributeVarchar']['value'] = str_replace('\\', '/', $result['EavAttributeVarchar']['value']);
				}
			}
			return $results;
		}

	}
?>