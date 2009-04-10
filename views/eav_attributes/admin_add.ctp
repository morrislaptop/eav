<div class="nodes form">
<?php echo $form->create('EavAttribute');?>
	<fieldset>
 		<legend><?php __('Add EAV Attribute');?></legend>
		<?php
			echo $form->input('name');
			echo $form->input('model');
			echo $form->input('type');
			echo $form->input('options');
		?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('List EAV Attributes', true), array('action'=>'index'));?></li>
	</ul>
</div>
