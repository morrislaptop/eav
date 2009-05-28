<?php
class EavAttributeFilesController extends AppController {

	var $name = 'EavAttributeFiles';

	function admin_delete($model, $foreign_key, $attribute_id) {
		$conditions = compact('model', 'foreign_key', 'attribute_id');
		$cascade = true;
		$callbacks = true;
		$this->EavAttributeFile->contain();
		$this->EavAttributeFile->deleteAll($conditions, $cascade, $callbacks);
		exit;
	}

}
?>