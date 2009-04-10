<?php
class EavAppModel extends AppModel {
	var $belongsTo = array(
		'EavAttribute' => array(
			'foreignKey' => 'attribute_id'
		)
	);
}
?>