<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

class CJmmView
{
	function showInstallMessage( $message, $title, $url ) 
	{
		global $PHP_SELF;
		?>
		<table class="adminheading">
		<tr>
			<th class="install">
			<?php echo $title; ?>
			</th>
		</tr>
		</table>
		
		<table class="adminform">
		<tr>
			<td align="left">
			<strong><?php echo $message; ?></strong>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
			[&nbsp;<a href="<?php echo $url;?>" style="font-size: 16px; font-weight: bold"><?php echo  JText::_('CONTINUE');?></a>&nbsp;]
			</td>
		</tr>
		</table>
		<?php
	}

	/**
	* @param array An array of data objects
	* @param object A page navigation object
	* @param string The option
	*/
	function showPlugins(& $rows, $option)
	{
		global $mainframe;

		$user = & JFactory :: getUser();

?>
		<form action="index.php" method="post" name="adminForm">

			<table class="adminlist">
			<thead>
				<tr>
					<th width="5" class="title">
					</th>
					<th class="title" >
						<?php echo JText::_('PLUGIN_NAME'); ?>
					</th>
					<th width="5" class="title">
					</th>
					<th width="10%" align="center">
						<?php echo JText::_( 'VERSION' ); ?>
					</th>
					<th width="25%"  class="title">
						<?php echo JText::_( 'AUTHOR' ); ?>
					</th>
					<th width="25%"  class="title">
						<?php echo JText::_( 'AUTHORMAIL' ); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="8">
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php

		$k = 0;
		for ($i = 0, $n = count($rows); $i < $n; $i++) {
			$row = & $rows[$i];
?>
				<tr class="<?php echo 'row'. $k; ?>">
					<td width="5">
					<input type="radio" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row->id; ?>" onclick="isChecked(this.checked);" />
					</td>
					<td>
						<?php echo $row->name;?>
					</td>
					
					<td align="center">
							<?php

					if ($row->status == "1") 
					{
?>
							<img src="templates/khepri/images/menu/icon-16-default.png" alt="<?php echo JText::_( 'PUBLISHED' ); ?>" />
								<?php

					}
					else 
					{
?>
							&nbsp;
<?php
					}
?>
					</td>
						
					<td align="center">
						<?php echo $row->version; ?>
					</td>
					<td>
						<?php echo @$row->author != '' ? $row->author : '&nbsp;'; ?>
					</td>
					<td>
						<?php echo @$row->authoremail != '' ? $row->authoremail : '&nbsp;'; ?>
					</td>
				</tr>
<?php
			}
?>
			</tbody>
			</table>

	<input type="hidden" name="option" value="<?php echo $option;?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHTML::_( 'form.token' ); ?>
	</form>
	
	<?php
	}
}