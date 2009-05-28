<?php
class EavBehavior extends ModelBehavior
{
	var $typeModels = array(
		'EavAttributeVarchar',
		'EavAttributeDatetime',
		'EavAttributeText',
		'EavAttributeBoolean',
		'EavAttributeNumber',
		'EavAttributeFile',
	);
	var $typeToModel = array(
		'text' => 'varchar',
		'datetime' => 'datetime',
		'wysiwyg' => 'text',
		'image' => 'file',
		'textarea' => 'varchar',
		'url' => 'varchar',
		'boolean' => 'boolean',
		'checkbox' => 'boolean',
		'flash' => 'file',
		'number' => 'number',
	);
	var $typeToDbType = array(
		'text' => 'string',
		'datetime' => 'datetime',
		'wysiwyg' => 'text',
		'image' => 'string',
		'textarea' => 'text',
		'url' => 'string',
		'boolean' => 'boolean',
		'checkbox' => 'boolean',
		'flash' => 'string',
		'number' => 'string',
	);

	var $settings = array();
	var $runtime = array();
	var $cache = array();

	function setup(&$model, $settings = array()) {
		if ( !isset($this->settings[$model->alias]) ) {
			$this->settings[$model->alias] = array(
				'appendToEavModel' => false,
			);
		}
		if ( !isset($this->runtime[$model->alias]) ) {
			$this->runtime[$model->alias] = array();
		}
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], $settings);
	}

	/**
	* Returns the model alias that is used to retrieve the attributes and attribute values.
	*
	* @param mixed $model
	* @param mixed $data
	*/
	function eavModel($model, $data = null)
	{
		$settings = $this->settings[$model->alias];
		$append = $settings['appendToEavModel'];

		if ( !$append ) {
			return $model->alias;
		}
		if ( is_string($append) ) {
			$append = array($append);
		}

		$eavModel = $model->alias;
		foreach ($append as $field) {
			// get the data
			if ( !empty($data[$model->alias][$field]) ) {
				$value = $data[$model->alias][$field];
			}
			else {
				$value = $model->field($field);
			}

			// make it friendly.
			$value = str_replace(' ', '', ucwords(str_replace(array('/', '\\'), ' ', $value)));

			// add to our eav model.
			$eavModel .= $value;
		}

		return $eavModel;
	}

	/**
	* Appends to model->_schema with the custom attributes.
	*
	* @param mixed $model
	* @param mixed $eavModel
	*/
	function refreshSchema($model, $eavModel)
	{
		$attributes = $this->attributes($model, $eavModel);
		foreach ($attributes as $attribute) {
			$field = array(
				'null' => true,
				'default' => null,
				'length' => null
			);
			$field['type'] = $this->typeToDbType[$attribute['EavAttribute']['type']];
			$model->_schema[$attribute['EavAttribute']['name']] = $field;
		}
	}

	function attributes($model, $eavModel) {
		$this->bindAttributes($model, $eavModel);
		$conditions = array(
			'model' => $eavModel,
		);
		$callbacks = false;
		$attributes = $model->EavAttribute->find('all', compact('conditions', 'callbacks'));
		return $attributes;
	}

	function beforeFind($model, $queryData) {
		if ( isset($queryData['eav']) ) {
			$this->runtime[$model->alias]['eav'] = $queryData['eav'];
		}
		if ( is_null($queryData['fields']) ) {
			$queryData['fields'] = $model->alias . '.*';
		}
		return $queryData;
	}

	function afterFind($model, $results) {
		if ( !empty($this->runtime[$model->alias]['eav']) ) {
			$results = $this->mergeAttributeValues($model, $results);
		}

		// reset runtime
		$this->runtime[$model->alias] = array();

		return $results;
	}

	function mergeAttributeValues($model, $results)
	{
		if ( !$results ) {
			return $results;
		}

		foreach ($results as $key => $result)
		{
			// Get eav model for this result.
			$eavModel = $this->eavModel($model, $result);
			$this->refreshSchema($model, $eavModel);

			foreach ($this->typeModels as $typeModel) {
				// first bind
				$this->bindAttributeValue($model, $eavModel, $typeModel);

				// then FIND
				$conditions = array(
					$typeModel . '.model' => $eavModel,
					$typeModel . '.foreign_key' => $result[$model->alias][$model->primaryKey]
				);
				#$fields = array($typeModel . '.' . 'value', 'EavAttribute.name');
				$values = $model->$typeModel->find('all', compact('conditions', 'fields'));
				foreach ($values as $value) {
					// give file attributes the whole array of data,
					if ( 'EavAttributeFile' == $typeModel ) {
						$results[$key][$model->alias][$value['EavAttribute']['name']] =  $value[$typeModel];
					}
					else {
						$results[$key][$model->alias][$value['EavAttribute']['name']] = $value[$typeModel]['value'];
					}
				}
			}
		}

		return $results;
	}

	function afterSave($model, $created)
	{
		$eavModel = $this->eavModel($model, $model->data);
		$this->refreshSchema($model, $eavModel);

		// Bind attribute values to the model so we can fetch all related data
		foreach ($this->typeModels as $typeModel)
		{
			// bind them.
			$this->bindAttributeValue($model, $eavModel, $typeModel);
		}

		// Go through attributes and save them in appropriate tables.
		$attributes = $this->attributes($model, $eavModel);
		foreach ($attributes as $attribute)
		{
			$field = $attribute['EavAttribute']['name'];
			if ( !isset($model->data[$model->alias][$field]) ) {
				continue;
			}
			$valueModel = 'EavAttribute' . ucwords($this->typeToModel[$attribute['EavAttribute']['type']]);
			$model->$valueModel->create();
			$value = $model->data[$model->alias][$field];

			$valueData = array(
				'attribute_id' => $attribute['EavAttribute']['id'],
				'model' => $eavModel,
				'foreign_key' => $model->id,
				'value' => $value
			);
			$model->$valueModel->save($valueData);
		}
	}

	function bindAttributeValue($model, $eavModel, $valueModel) {
		$eavValueModel = 'Eav.' . $valueModel;
		$associations = array(
			$valueModel => array(
				'conditions' => array(
					'model' => $eavModel,
				),
				'className' => $eavValueModel,
				'foreignKey' => 'foreign_key'
			)
		);
		$model->bindModel(array('hasMany' => $associations));
	}

	function bindAttributes(&$model, $eavModel, $reset = true)
	{
		$associations = array(
			'EavAttribute' => array(
				'conditions' => array(
					'model' => $eavModel,
				),
				'foreignKey' => false
			)
		);
		$model->bindModel(array('hasMany' => $associations), $reset);
	}
}
?>