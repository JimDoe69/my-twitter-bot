<?php
// Twitter autoreply
// By Daniel15 <http://dan.cx/>
// Modified 2013-06-13 to support Twitter API 1.1
class TwitterAutoReply
{
	// Constants
	const SEARCH_URL = 'https://api.twitter.com/1.1/search/tweets.json?q=%s&since_id=%s';
	const UPDATE_URL = 'https://api.twitter.com/1.1/statuses/update.json';
	const VERIFY_URL = 'https://api.twitter.com/1.1/account/verify_credentials.json';
	const REQUEST_TOKEN_URL = 'https://twitter.com/oauth/request_token';
	const ACCESS_TOKEN_URL = 'https://twitter.com/oauth/access_token';
	const AUTH_URL = 'http://twitter.com/oauth/authorize';
	
	// Variables
	private $_replies = array();
	private $_oauth;
	private $_screenName;
	
	// Methods
	public function __construct($consumer_key, $consumer_secret)
	{
		$this->_oauth = new OAuth($consumer_key, $consumer_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
		$this->_oauth->disableSSLChecks();
	}
	
	public function setToken($token, $secret)
	{
		$this->_oauth->setToken($token, $secret);
	}
	
	public function addReply($term, $reply)
	{
		$this->_replies[$term] = $reply;
	}
	
	public function run()
	{
		echo '========= ', date('Y-m-d g:i:s A'), " - Started =========\n";
		// Get the last ID we replied to
		$since_id = @file_get_contents('since_id');
		if ($since_id == null)
			$since_id = 0;
			
		// This will store the ID of the last tweet we get
		$max_id = $since_id;
		
		// Verify their Twitter account is valid
		if (!$this->verifyAccountWorks())
		{
			// Get a request token and show instructions
			$this->doAuth();
			die();
		}
		
		// Go through each reply
		foreach ($this->_replies as $term => $reply)
		{
			echo 'Performing search for "', $term, '"... ';
			$this->_oauth->fetch(sprintf(self::SEARCH_URL, urlencode($term), $since_id));
			$search = json_decode($this->_oauth->getLastResponse());
			echo 'Done, ', count($search->statuses), " results.\n";
			// Store the max ID
			if ($search->search_metadata->max_id_str > $max_id)
				$max_id = $search->search_metadata->max_id_str;
			
			// Now let's go through the results
			foreach ($search->statuses as $tweet)
			{
				// Ensure we don't reply to ourself!
				if ($tweet->user->screen_name == $this->_screenName)
					continue;
				$this->sendReply($tweet, $reply);
				sleep(1);
			}
		}
		
		file_put_contents('since_id', $max_id);
		echo '========= ', date('Y-m-d g:i:s A'), " - Finished =========\n";
	}
	
	private function sendReply($tweet, $reply)
	{
		try
		{
			echo '@', $tweet->user->screen_name, ' said: ', $tweet->text, "\n";
			$this->_oauth->fetch(self::UPDATE_URL, array(
				'status' => '@' . $tweet->user->screen_name . ' ' . $reply,
				'in_reply_to_status_id' => $tweet->id_str,
			), OAUTH_HTTP_METHOD_POST);
		}
		catch (OAuthException $ex) 
		{
				echo 'ERROR: ' . $ex->lastResponse;
				die();
		}
	}
	
	private function verifyAccountWorks()
	{
		try
		{
			$this->_oauth->fetch(self::VERIFY_URL, array(), OAUTH_HTTP_METHOD_GET);
			$response = json_decode($this->_oauth->getLastResponse());
			$this->_screenName = $response->screen_name;
			return true;
		}
		catch (Exception $ex)
		{
			return false;
		}
	}
	
	private function doAuth()
	{
		// First get a request token, and prompt them to go to the URL
		$request_token_info = $this->_oauth->getRequestToken(self::REQUEST_TOKEN_URL);
		echo 'Please navigate to the following URL to get an authentication token:', "\n";
		echo self::AUTH_URL, '?oauth_token=', $request_token_info['oauth_token'], "\n";
		echo 'Once done (and you have a PIN number), press ENTER.';
		fread(STDIN, 10);
		
		echo 'PIN Number: ';
		$pin = trim(fread(STDIN, 10));
		
		// Now let's swap that for an access token
		$this->_oauth->setToken($request_token_info['oauth_token'], $request_token_info['oauth_token_secret']);
		$access_token_info = $this->_oauth->getAccessToken(self::ACCESS_TOKEN_URL, null, $pin);
		
		echo 'Success, ', $access_token_info['screen_name'], ' has authorized the application. Please change your setToken line to something like the following:', "\n";
		echo '$twitter->setToken(\'', $access_token_info['oauth_token'], '\', \'', $access_token_info['oauth_token_secret'], '\');';
		die();
	}
	
	public function testTweet()
	{
		$this->_oauth->fetch(self::UPDATE_URL, array(
			'status' => 'Test from TwitterAutoReply',
		), OAUTH_HTTP_METHOD_POST);
	}
}
