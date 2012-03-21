<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Modules\StopSpam\Hooks;

return array
(
	'events' => array
	(
		'ICanBoogie\Modules\Comments\SaveOperation::process:before' => __NAMESPACE__ . '::before_comments_save',
		'ICanBoogie\Modules\Comments\ConfigBlock::alter_children' => __NAMESPACE__ . '::on_comments_configblock_alter_children',
		'ICanBoogie\Modules\Forms\PostOperation::process:before' => __NAMESPACE__ . '::before_forms_post',
		'ICanBoogie\Modules\Forms\ManageBlock::alter_columns' => __NAMESPACE__ . '::on_forms_manageblock_alter_columns'
	)
);
