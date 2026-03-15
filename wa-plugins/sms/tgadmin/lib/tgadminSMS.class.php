<?php

class tgadminSMS extends waSMSAdapter
{
	public function getControls()
	{
		return array(
			'bottoken' => array(
				'title'       => 'Bot Token',
				'description' => 'Введите токен бота полученный у BotFather',
			),
		);
	}
	
    public function send($to, $text, $from = null)
    {
    	$context = stream_context_create(array(
    			'http' => array(
    					'method' => 'POST', // HTTP request method
    					'header' => "Content-type: application/x-www-form-urlencoded\r\n",
    			        'content' => http_build_query(array('text' => $text, 'chat_id' => $to), '', '&'),
    					'timeout' => 5,  // Request timeout in seconds
    			),
    	));
    	$request = file_get_contents('https://api.telegram.org/bot'.$this->getOption('bottoken').'/sendmessage', false, $context);
		return true;
    }

}