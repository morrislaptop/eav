<?php
class EavBehavior extends ModelBehavior
{
	var $typeModels = array(
		'EavAttributeVarchar',
		'EavAttributeDatetime',
		'EavAttributeText',
	);
	var $typeToModel = array(
		'varchar' => 'varchar',
		'text' => 'text',
		'datetime' => 'datetime',
		'wysiwyg' => 'text'
	);
	var $typeToType = array(
		'varchar' => 'string',
		'text' => 'text',
		'datetime' => 'datetime',
		'wysiwyg' => 'text'
	);
	
	function setup(&$model, $settings = array()) {
		if (!isset($this->settings[$model->alias])) {
			$this->settings[$model->alias] = array(
				'alias' => false, 
				'cache' => array(
					'storedAlias' => null,
					'attributes' => null
				)
			);
		}
		if (!is_array($settings)) {
			$settings = array();
		}
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], $settings);
		
		// if the alias doesn't depend on a field, bind it straight away
		if ( !$this->settings[$model->alias]['alias'] ) {
			$this->bindAttributes($model, false);	
		}
	}
	
	function alias($model) {
		if ( $this->settings[$model->alias]['cache']['storedAlias'] === null ) {
			$this->settings[$model->alias]['cache']['storedAlias'] = $this->_alias($model);
		}
		return $this->settings[$model->alias]['cache']['storedAlias'];
	}

	function _alias($model)
	{
		$settings = $this->settings[$model->alias];
		$alias = $settings['alias'];
		
		if ( !$alias ) {
			return $model->alias;
		}
		if ( method_exists($model, $alias) ) {
			return $model->$alias();
		}
		if ( is_string($alias) ) {
			return $this->quietField($model, $alias);
		}
		if ( is_array($alias) ) {
			$alias = '';
			foreach ($alias as $alia) {
				$alias .= $this->quietField($model, $alia);
			}
			return $alias;
		}
		trigger_error('Could not compute alias for ' . $model->alias);
		return false;
	}
	
	function attributes($model)
	{
		if ( $this->settings[$model->alias]['cache']['attributes'] === null ) {
			$this->settings[$model->alias]['cache']['attributes'] = $this->_attributes($model);
		}
		return $this->settings[$model->alias]['cache']['attributes'];
	}
	
	function _attributes($model) {
		$eavModel = $this->alias($model);
		$this->bindAttributes($model);
		$conditions = array(
			'model' => $eavModel,
		);
		$callbacks = false;
		$attributes = $model->EavAttribute->find('all', compact('conditions', 'callbacks'));
		$attributesKeyed = array();
		
		foreach ($attributes as $attribute) {
			$attributesKeyed[$attribute['EavAttribute']['name']] = $attribute['EavAttribute'];
		}
		
		return $attributesKeyed;
	}
	
	function afterFind($model, $results)
	{
		if ( count($results) > 1 ) {
			return $results;
		}
		
		$eavModel = $this->alias($model);
		
		// bind the badboys up
		foreach ($this->typeModels as $typeModel) {
			$this->bindAttributeValue($model, $typeModel);
		}
		
		foreach ($results as $key => $result)
		{
			if ( empty($result[$model->alias][$model->primaryKey]) ) {
				continue;
			}
			
			foreach ($this->typeModels as $typeModel) {
				$conditions = array(
					$typeModel . '.model' => $eavModel,
					$typeModel . '.foreign_key' => $result[$model->alias][$model->primaryKey]
				);
				$fields = array($typeModel . '.' . 'value', 'EavAttribute.name');
				$values = $model->$typeModel->find('all', compact('conditions', 'fields'));
				
				foreach ($values as $value) {
					$results[$key][$model->alias][$value['EavAttribute']['name']] = $value[$typeModel]['value'];
				}
			}
		}
		
		return $results;
	}
	
	function quietField($model, $field) {
		if ( !empty($model->data[$model->alias][$field]) ) {
			return $model->data[$model->alias][$field];
		}
		if ( !$model->hasField($field) ) {
			trigger_error($model->alias . ' does not have a ' . $field . ' field');
			return false;
		}
		else if ( $model->id ) {
			// quietly get the needed field, without waking up the nasty afterFind callbacks.
			if ( 0 ) {
				$conditions = array('id' => $model->id);
				$fields = $field;
				$callbacks = false;
				$row = $model->find('first', compact('conditions', 'fields', 'callbacks'));
				return $row[$model->alias][$field];
			}
			else {
				$db = ConnectionManager::getDataSource($model->useDbConfig);
				$primaryKey = $model->primaryKey;
				$sql = "SELECT {$field} FROM {$model->table} as {$model->alias} WHERE {$primaryKey} = {$model->$primaryKey}";
				$result = $db->query($sql);
				return $result[0][$model->alias][$field];
			}
		}
		trigger_error('Couldnt get data for ' . $model->alias . '->' . $field);
	}
	
	function afterSave($model, $created) 
	{
		$eavModel = $this->alias($model);
		
		// Delete existing data for entity
		foreach ($this->typeModels as $typeModel) 
		{
			// bind them.
			$this->bindAttributeValue($model, $typeModel);
			
			// delete the existing data
			$conditions = array(
				$typeModel . '.model' => $eavModel,
				$typeModel . '.foreign_key' => $model->id
			);
			$model->$typeModel->deleteAll($conditions);
		}
		
		// Go through attributes and save them in appropriate tables.
		$attributes = $this->attributes($model);
		foreach ($attributes as $field => $attribute)
		{
			if ( !isset($model->data[$model->alias][$field]) ) {
				continue;
			}
			$valueModel = 'EavAttribute' . ucwords($this->typeToModel[$attribute['type']]);
			$model->$valueModel->create();
			$value = $model->data[$model->alias][$field];
			$valueData = array(
				'attribute_id' => $attribute['id'],
				'model' => $eavModel,
				'foreign_key' => $model->id,
				'value' => $value
			);
			$model->$valueModel->save($valueData);
		}
	}
	
	function bindAttributeValue($model, $valueModel) {
		$eavValueModel = 'Eav.' . $valueModel;
		$associations = array(
			$valueModel => array(
				'conditions' => array(
					'model' => $this->alias($model),
				),
				'className' => $eavValueModel,
				'foreignKey' => 'foreign_key'
			)
		);

		$model->bindModel(array('hasMany' => $associations));
	}
	
	function bindAttributes(&$model, $reset = true)
	{
		$associations = array(
			'EavAttribute' => array(
				'conditions' => array(
					'model' => $this->alias($model),
				),
				'foreignKey' => false
			)
		);
		$model->bindModel(array('hasMany' => $associations), $reset);
	}
	
	function getColumnType($model, $column) {
		$attributes = $this->attributes($model);
		if ( isset($attributes[$column]) ) {
			return $this->typeToType[$attributes[$column]['type']];
		}
	}
}
?>