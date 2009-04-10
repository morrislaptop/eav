<?php
class EavAttributesController extends EavAppController
{
	function admin_index() {
		$this->EavAttribute->recursive = 0;
		$this->set('eav_attributes', $this->paginate());
	}
	
	function admin_view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid EavAttribute.', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->set('eav_attributes', $this->EavAttribute->read(null, $id));
	}

	function admin_add() {
		if (!empty($this->data)) {
			$this->EavAttribute->create();
			if ($this->EavAttribute->save($this->data)) {
				$this->Session->setFlash(__('The EavAttribute has been saved', true));
				$this->redirect(array('action'=>'index'));
			} else {
				$this->Session->setFlash(__('The EavAttribute could not be saved. Please, try again.', true));
			}
		}
		$this->_setFormData();
	}

	function admin_edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid EavAttribute', true));
			$this->redirect(array('action'=>'index'));
		}
		if (!empty($this->data)) {
			if ($this->EavAttribute->save($this->data)) {
				$this->Session->setFlash(__('The EavAttribute has been saved', true));
				$this->redirect(array('action'=>'index'));
			} else {
				$this->Session->setFlash(__('The EavAttribute could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->EavAttribute->read(null, $id);
		}
		$this->_setFormData();
	}
	
	function _setFormData()
	{
		$models = $this->_getModels();
		$models = array_combine($models, $models);
		$types = array('text', 'dropdown', 'image', 'file', 'checkboxes', 'wysiwyg', 'textarea');
		$types = array_combine($types, $types);
		$this->set(compact('models', 'types'));
	}
	
	function _getModels()
	{
		$sql = 'SHOW tables';
		$db = ConnectionManager::getDataSource('default');
		$tables = $db->query($sql);
		$tables = Set::extract('/TABLE_NAMES/Tables_in_cms_baked_simpler', $tables);
		$models = array();
		
		// find the eav tables and remove them from the set.
		foreach ($tables as $key => $value) {
			if ( 'eav_' == substr($value, 0, 4) ) {
				unset($tables[$key]);
			}
		}
		
		// get lift of files in the models directory.
		uses('Folder');
		$folder = new Folder(MODELS);
		$files = $folder->ls();
		foreach ($files[1] as $file)
		{
			$modelName = ucwords(substr($file, 0, strpos($file, '.')));
			$table = Inflector::tableize($modelName);
			$model = ClassRegistry::init($modelName, 'Model');
			
			// replace the table in the tables array with the model name.
			foreach ($tables as $key => $value) {
				if ( $value == $model->useTable && $value != $table ) {
					$tables[$key] = $modelName;
				}
			}
		}
		
		// now convert table names into models.
		$models = array();
		foreach ($tables as $value) {
			$models[] = Inflector::classify($value);
		}
		
		return $models;
	}

	function admin_delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for EavAttribute', true));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->EavAttribute->del($id)) {
			$this->Session->setFlash(__('EavAttribute deleted', true));
			$this->redirect(array('action'=>'index'));
		}
	}

}