<?php

/**
 * @package    WickedTeamBirthdays
 * @author     Heinl Christian <heinchrs@gmail.com>
 * @copyright  (C) 2015-2025 Heinl Christian
 * @license    GNU General Public License version 2 or later
 */

// -- No direct access
defined('_JEXEC') or die;
?>


<!-- Beginn WickedTeam Birthdays -->
<div id="wickedteam_birthdays_container">
<?php
if (count($data) > 0)
{
	foreach ($data as $entry)
	{
?>
	<div class="birthday_entry">
		<div class="birthday_date"><?php echo $entry['value']; ?> (<?php echo $entry['wird_x_jahre']; ?>)</div>
		<div class="birthday_name"><?php echo $entry['title']; ?></div>
	</div>
<?php
	};
}
else
{
	echo '<div class="no_data">' . JText::_('MOD_WICKEDTEAM_BIRTHDAYS_NO_BIRTHDAYS') . '</div>';
}
?>
</div>
<!-- Ende WickedTeam Birthdays -->
