<?php
class EavHelper extends AppHelper
{
	var $helpers = array('Uniform.Uniform', 'Html', 'Javascript');

	var $typeMap = array(
		'wysiwyg' => 'text',
		'image' => 'varchar',
		'file' => 'varchar',
		'varchar' => 'varchar',
		'text' => 'text',
		'datetime' => 'datetime',
		'textarea' => 'text',
		'url' => 'text',
		'boolean' => 'boolean',
		'flash' => 'varchar'
	);

	/**
	* Stores the original model that we are using.
	*
	* @var mixed
	*/
	var $entity;

	function inputs($fields = null, $blacklist = null)
	{
		$fieldset = $legend = true;

		if (is_array($fields)) {
			if (array_key_exists('legend', $fields)) {
				$legend = $fields['legend'];
				unset($fields['legend']);
			}

			if (isset($fields['fieldset'])) {
				$fieldset = $fields['fieldset'];
				unset($fields['fieldset']);
			}
		} elseif ($fields !== null) {
			$fieldset = $legend = $fields;
			if (!is_bool($fieldset)) {
				$fieldset = true;
			}
			$fields = array();
		}

		if (empty($fields)) {
			$fields = array_keys($this->fieldset['fields']);
		}

		if ($legend === true) {
			$actionName = __('New', true);
			$isEdit = (
				strpos($this->action, 'update') !== false ||
				strpos($this->action, 'edit') !== false
			);
			if ($isEdit) {
				$actionName = __('Edit', true);
			}
			$modelName = Inflector::humanize(Inflector::underscore($this->model()));
			$legend = $actionName .' '. __($modelName, true);
		}

		$out = null;
		foreach ($fields as $name => $options) {
			if (is_numeric($name) && !is_array($options)) {
				$name = $options;
				$options = array();
			}
			$entity = explode('.', $name);
			$blacklisted = (
				is_array($blacklist) &&
				(in_array($name, $blacklist) || in_array(end($entity), $blacklist))
			);
			if ($blacklisted) {
				continue;
			}
			$out .= $this->input($options);
		}

		if (is_string($fieldset)) {
			$fieldsetClass = sprintf(' class="%s"', $fieldset);
		} else {
			$fieldsetClass = '';
		}

		if ($fieldset && $legend) {
			return sprintf(
				$this->Html->tags['fieldset'],
				$fieldsetClass,
				sprintf($this->Html->tags['legend'], $legend) . $out
			);
		} elseif ($fieldset) {
			return sprintf($this->Html->tags['fieldset'], $fieldsetClass, $out);
		} else {
			return $out;
		}
	}

	function input($attribute)
	{
		// get model and load it so the form will work automagically
		$model = 'EavAttribute' . ucwords($this->typeMap[$attribute['type']]);
		ClassRegistry::init($model, 'Model');

		// Store the original model once, as the next time it will be replaced with EavAtttributeXxxxx
		if ( !$this->entity ) {
			$this->entity = $this->model();
		}

		// switch the label and the data so it's programmer friendly
		$options = array(
			'label' => Inflector::humanize($attribute['name']),
			#'name' => 'data[' . $model . '][' . Inflector::underscore($attribute['name']) . ']'
		);

		// put value in...
		if ( isset($this->data[$this->entity][Inflector::underscore($attribute['name'])]) ) {
			$options['value'] = $this->data[$this->entity][Inflector::underscore($attribute['name'])];
		}

		return $this->Uniform->input($attribute['name'], $options);
	}
}
?>