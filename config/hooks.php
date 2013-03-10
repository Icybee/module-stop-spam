<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Modules\StopSpam;

$hooks = __NAMESPACE__ . '\Hooks::';

return array
(
	'events' => array
	(
		'Icybee\Modules\Comments\SaveOperation::process:before' => $hooks . 'before_comments_save',
		'Icybee\Modules\Comments\ConfigBlock::alter_children' => $hooks . 'on_comments_configblock_alter_children',
		'Icybee\Modules\Forms\PostOperation::process:before' => $hooks . 'before_forms_post',
		'Icybee\Modules\Forms\ManageBlock::alter_columns' => $hooks . 'on_forms_manageblock_alter_columns'
	)
);
