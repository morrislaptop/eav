<div class="nodes form">
<?php echo $form->create('EavAttribute');?>
	<fieldset>
 		<legend><?php __('Edit EAV Attribute');?></legend>
		<?php
			echo $uniform->input('id');
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
		<li><?php echo $html->link(__('Delete', true), array('action'=>'delete', $form->value('EavAttribute.id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $form->value('Node.id'))); ?></li>
		<li><?php echo $html->link(__('List EAV Attributes', true), array('action'=>'index'));?></li>
	</ul>
</div>
