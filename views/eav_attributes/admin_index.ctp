<div class="eav_attributes index">
<h2><?php __('EAV Attributes');?></h2>
<p>
<?php
echo $paginator->counter(array(
'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
));
?></p>
<table cellpadding="0" cellspacing="0">
<tr>
	<th><?php echo $paginator->sort('id');?></th>
	<th><?php echo $paginator->sort('model');?></th>
	<th><?php echo $paginator->sort('name');?></th>
	<th><?php echo $paginator->sort('type');?></th>
	<th><?php echo $paginator->sort('options');?></th>
	<th><?php echo $paginator->sort('created');?></th>
	<th><?php echo $paginator->sort('modified');?></th>
	<th class="actions"><?php __('Actions');?></th>
</tr>
<?php
$i = 0;
foreach ($eav_attributes as $eav_attribute):
	$class = null;
	if ($i++ % 2 == 0) {
		$class = ' class="altrow"';
	}
?>
	<tr<?php echo $class;?>>
		<td>
			<?php echo $eav_attribute['EavAttribute']['id']; ?>
		</td>
		<td>
			<?php echo $eav_attribute['EavAttribute']['model']; ?>
		</td>
		<td>
			<?php echo $eav_attribute['EavAttribute']['name']; ?>
		</td>
		<td>
			<?php echo $eav_attribute['EavAttribute']['type']; ?>
		</td>
		<td>
			<?php echo $eav_attribute['EavAttribute']['options']; ?>
		</td>
		<td>
			<?php echo $eav_attribute['EavAttribute']['created']; ?>
		</td>
		<td>
			<?php echo $eav_attribute['EavAttribute']['modified']; ?>
		</td>
		<td class="actions">
			<?php echo $html->link(__('View', true), array('action'=>'view', $eav_attribute['EavAttribute']['id'])); ?>
			<?php echo $html->link(__('Edit', true), array('action'=>'edit', $eav_attribute['EavAttribute']['id'])); ?>
			<?php echo $html->link(__('Delete', true), array('action'=>'delete', $eav_attribute['EavAttribute']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $eav_attribute['EavAttribute']['id'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
</table>
</div>
<div class="paging">
	<?php echo $paginator->prev('<< '.__('previous', true), array(), null, array('class'=>'disabled'));?>
 | 	<?php echo $paginator->numbers();?>
	<?php echo $paginator->next(__('next', true).' >>', array(), null, array('class'=>'disabled'));?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('New EAV Attribute', true), array('action'=>'add')); ?></li>
	</ul>
</div>
