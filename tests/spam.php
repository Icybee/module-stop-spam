<?php

use Brickrouge\Form;

require_once $_SERVER['DOCUMENT_ROOT'] . '/Icybee/startup.php';

if (!$core->user->is_guest)
{
	$core->user->logout();
}

$form = new Form();
$form->save();
$form_key = $form->hiddens[Form::STORED_KEY_NAME];

$request = ICanBoogie\HTTP\Request::from
(
	array
	(
		'ip' => '199.15.234.222',
		'path_info' => '/api/comments/save',
		'params' => array
		(
			Form::STORED_KEY_NAME => $form_key,

			'nid' => 58,
			'author' => 'Lady Gaga',
			'author_email' => 'cryday767@gmail.com',
			'notify' => 'no',
			'contents' => <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse bibendum, nulla eu bibendum
ultrices, sem elit vulputate neque, eu aliquam dui metus quis massa. Donec sagittis, mi nec
euismod accumsan, diam turpis tincidunt tellus, quis tristique felis nunc nec justo. Pellentesque
tempor consectetur est, id ullamcorper lectus gravida eget. Aenean et augue tellus.
EOT
		)
	)
);

$response = $request->post();

echo '<h3>Errors</h3>';

foreach($response->errors as $error)
{
	echo $error . '<br />';
}