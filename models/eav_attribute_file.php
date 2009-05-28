<?php
	class EavAttributeFile extends EavAppModel {

		var $actsAs = array(
			// meio upload will save files AND delete them :D
			'MeioUpload' => array(
				'value' => array(
					'dir' => 'files{DS}{model}{DS}{field}'
				)
			)
		);

	}
?>