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

use ICanBoogie\ActiveRecord\RecordNotFound;
use ICanBoogie\HTTP\Request;
use ICanBoogie\I18n;
use ICanBoogie\I18n\FormattedString;
use ICanBoogie\Operation;

use Brickrouge\Element;
use Brickrouge\Form;
use Brickrouge\Text;

use Icybee\Modules\Comments\Comment;

class Hooks
{
	static private function retrieve_spaminfo(array $params)
	{
		$url = 'http://www.stopforumspam.com/api?' . http_build_query([

			'f' => 'json'

		] + $params, '', '&');

		return json_decode(file_get_contents($url), true);
	}

	/**
	 * Checks if the request was initiated by a spam-bot.
	 *
	 * @param string $ip
	 * @param string $username
	 * @param string $email
	 *
	 * @return ActiveRecord|null Returns the spam trace or null if the request seams legit.
	 */
	static private function check($ip, $username=null, $email=null)
	{
		global $core;

		$model = $core->models['stop-spam'];
		$record = $model->filter_by_ip($ip)->one;
		$threshold = $core->registry['stop_spam.threshold'] ?: 60;

		if ($record)
		{
			$record->count++;
			$record->date = date('Y-m-d');
			$record->save();

			return $record;
		}

		$spaminfo = self::retrieve_spaminfo([

			'ip' => $ip,
			'username' => $username,
			'email' => $email

		]);

		$frequency = 0;
		$confidence = 0;

		if (!empty($spaminfo['success']))
		{
			foreach ($spaminfo as $type => $info)
			{
				if (!is_array($info) || !$info['appears'])
				{
					continue;
				}

				$f = $info['frequency'];
				$c = $info['confidence'];

				$frequency += $f;
				$confidence += $f * $c;
			}
		}

		if ($frequency)
		{
			$confidence = $confidence / $frequency;
		}

		if ($frequency < 10 || $confidence < $threshold)
		{
			return;
		}

		$model->insert([

			'ip' => $ip,
			'confidence' => $confidence,
			'date' => date('Y-m-d'),
			'count' => 1

		]);

		return $model->filter_by_ip($ip)->one;
	}

	public static function before_comments_save(Operation\BeforeProcessEvent $event, Operation $operation)
	{
		global $core;

		if ($core->user->has_permission(Module::PERMISSION_ADMINISTER, 'comments'))
		{
			return;
		}

		$request = $event->request;
		$ip = $request->ip;
		$record = self::check($ip, $request[Comment::AUTHOR], $request[Comment::AUTHOR_EMAIL]);

		if (!$record)
		{
			return;
		}

		$event->response->errors[] = new FormattedString
		(
			'The likelihood that you are a spambot is about :confidence%. (Your IP: %ip)', array
			(
				'confidence' => round($record->confidence),
				'ip' => $ip
			)
		);
	}

	static public function before_forms_post(Operation\BeforeProcessEvent $event, Operation $operation)
	{
		global $core;

		$request = $event->request;
		$ip = $request->ip;
		$record = self::check($ip, null, $request['email']);

		if (!$record)
		{
			return;
		}

		$errors = $event->response->errors;
		$errors[] = $errors->format('The likelihood that you are a spambot is about :confidence%. (Your IP: %ip)', [

			'confidence' => round($record->confidence),
			'ip' => $ip

		]);

		$model = $core->models['stop-spam/forms'];
		$formid = $request[\Icybee\Modules\Forms\Module::OPERATION_POST_ID];

		try
		{
			$form_record = $model[$formid];

			$form_record->count++;
			$form_record->save();
		}
		catch (RecordNotFound $e)
		{
			$model->save(array('formid' => $formid, 'count' => 1));
		}
	}

	static public function on_comments_configblock_alter_children(\ICanBoogie\Event $event, \Icybee\Modules\Comments\ConfigBlock $block)
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
	 * Alters the "manage" block of the Forms module (forms) to add the "Spam caught" column.
	 *
	 * @param Event $event
	 * @param \Icybee\Modules\Forms\ManageBlock $block
	 */
	static public function on_forms_manageblock_alter_columns(\Icybee\ManageBlock\AlterColumnsEvent $event, \Icybee\Modules\Forms\ManageBlock $target)
	{
		$event->add(new ManageBlock\SpamCaughtColumn($target, 'spam_caught'));
	}
}

namespace ICanBoogie\Modules\StopSpam\ManageBlock;

use ICanBoogie\I18n;

use Icybee\ManageBlock\Column;

class SpamCaughtColumn extends Column
{
	public function __construct($manager, $id)
	{
		parent::__construct
		(
			$manager, $id, array
			(
				'class' => 'pull-right',
				'title' => "Spam caught",
				'orderable' => false,
				'default_order' => 1,
				'discreet' => true
			)
		);
	}

	public function render_cell($record)
	{
		global $core;

		$model = $core->models['stop-spam/forms'];

		$count = $model->select('`count`')->filter_by_formid($record->nid)->rc;
		$label = I18n\t(':count spam caught', array(':count' => $count));

		if (!$count)
		{
			return '<em class="small">' . $label . '</em>';
		}

		return $label;
	}
}