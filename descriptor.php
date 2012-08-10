<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ICanBoogie\Module;
use ICanBoogie\ActiveRecord\Model;

return array
(
	Module::T_CATEGORY => 'feedback',
	Module::T_DESCRIPTION => "Helps to fight against spam",
	Module::T_MODELS => array
	(
		'primary' => array
		(
// 			Model::T_CONNECTION => 'local',
			Model::T_NAME => 'stopspam',
			Model::T_SCHEMA => array
			(
				'fields' => array
				(
					'ip' => array('varchar', 40, 'primary' => true),
					'confidence' => 'float',
					'date' => 'date',
					'count' => array('integer', 'unsigned' => true)
				)
			)
		),

		'forms' => array
		(
// 			Model::T_CONNECTION => 'local',
			Model::T_NAME => 'stopspam__forms',
			Model::T_SCHEMA => array
			(
				'fields' => array
				(
					'formid' => array('foreign', 'primary' => true),
					'count' => array('integer', 'unsigned' => true)
				)
			)
		)
	),

	Module::T_PERMISSION => null,
	Module::T_TITLE => 'Stop spam'
);