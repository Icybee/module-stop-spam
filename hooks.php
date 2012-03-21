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

use ICanBoogie\ActiveRecord\Comment;
use ICanBoogie\Operation;

use Brickrouge\Element;
use Brickrouge\Form;
use Brickrouge\Text;

class Hooks
{
	public static function before_comments_save(Operation\BeforeProcessEvent $event, Operation $operation)
	{
		global $core;

		$request = $event->request;
		$ip = $request->ip;
		$model = $core->models['stop-spam'];
		$record = $model->find_by_ip($ip)->one;
		$threshold = $core->registry['stop_spam.threshold'] ?: 60;

		if ($record)
		{
			$confidence = $record->confidence;
		}
		else
		{
			$spam_info = json_decode(file_get_contents
			(
				'http://www.stopforumspam.com/api?' . http_build_query
				(
					array
					(
						'ip' => $ip,
						'username' => $request[Comment::AUTHOR],
						'email' => $request[Comment::AUTHOR_EMAIL],
						'f' => 'json'
					),

					'', '&'
				)
			), true);

			if (!$spam_info)
			{
				return;
			}

			$confidence = 0;

			if (isset($spam_info['ip']['confidence']))
			{
				$confidence = $spam_info['ip']['confidence'];
			}
			else if (isset($spam_info['username']['confidence']))
			{
				$confidence = $spam_info['username']['confidence'];
			}

			if ($confidence < $threshold)
			{
				return;
			}
		}

		$event->response->errors[] = t
		(
			'The likelihood that you are a spambot is above :threshold%. You scored: :confidence%.', array
			(
				'threshold' => $threshold,
				'confidence' => $confidence
			)
		);

		$core->registry['stop_spam.caught'] = $core->registry['stop_spam.caught'] + 1;

		if ($record)
		{
			$record->count++;
			$record->date = date('Y-m-d');
			$record->save();
		}
		else
		{
			$model->insert(array(
				'ip' => $ip,
				'confidence' => $confidence,
				'date' => date('Y-m-d'),
				'count' => 1
			));
		}
	}

	public static function before_forms_post(Operation\BeforeProcessEvent $event, Operation $operation)
	{

	}

	public static function on_comments_configblock_alter_children(\ICanBoogie\Event $event, \ICanBoogie\Modules\Comments\ConfigBlock $block)
	{
		$event->children['global[stop_spam.threshold]'] = new Text
		(
			array
			(
				Form::LABEL => 'Confidence threshold',
				Text::ADDON => '%',
				Element::GROUP => 'spam',
				Element::DEFAULT_VALUE => 60,

				'class' => 'measure',
				'size' => 3
			)
		);
	}

	/**
	 * Alters the "manage" block of the Forms module (`forms`) to add the "Submitted" column.
	 *
	 * @param Event $event
	 * @param \ICanBoogie\Modules\Forms\ManageBlock $block
	 */
	public static function on_forms_manageblock_alter_columns(\ICanBoogie\Event $event, \ICanBoogie\Modules\Forms\ManageBlock $block)
	{
		global $core;

 		$event->columns = \ICanBoogie\array_insert
 		(
 			$event->columns, 'modelid', array
 			(
 				'label' => "Spam caught",
 				'class' => null,
 				'hook' => function($record, $property)
 				{
 					global $core;

 					$nid = $record->nid;
 					$count = $core->registry['stop_spam.caught'];
 					$label = t(':count spam caught', array(':count' => $count));

 					if (!$count)
 					{
 						return '<em class="small">' . $label . '</em>';
 					}

 					return $label;
 				},

 				'filters' => null,
 				'filtering' => false,
 				'reset' => null,
 				'orderable' => false,
 				'order' => null,
 				'default_order' => 1,
 				'discreet' => true
 			),

 			'spam_caught'
 		);
	}
}