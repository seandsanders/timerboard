<?php

class LoginTestController extends BaseController {

	const LAYOUT = 'layouts.login';

	private $api = null;
	private $edit_tags = array('admin', 'cron.admin', 'diplo', 'diplo.guest', 'fc', 'fc.guest');

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	public function __construct()
	{
		$this->api = new Brave\API(Config::get('braveapi.application-endpoint'), Config::get('braveapi.application-identifier'), Config::get('braveapi.local-private-key'), Config::get('braveapi.remote-public-key'));
	}

	public function loginView()
	{
		$this->layout = self::LAYOUT;
		$view = View::make(self::LAYOUT)
		        ->nest('navigation', 'navigation')
		        ->nest('footer', 'parts/footer')
		        ->nest('page_content', 'logintest');

		return $view;
	}

	public function loginAction()
	{
		// API Call Args
		$info_data = array(
			'success' => route('infotest'),
			'failure' => route('infotest')
		);
		$result = $this->api->core->authorize($info_data);

		return Redirect::to($result->location);
	}

	public function infoAction()
	{
		$token = Input::get('token', false);
		if($token == false)
		{
			return Redirect::route('logintest')
			               ->with('flash_notice', 'Login Failed');
		}

		// ----------------
		$return = $this->api->core->info(array('token' => $token));

		if(!isset($return->character->name))
		{

		}

		// validate permissions
		$permission = 0;
		foreach($this->edit_tags as $tag)
		{
			if(in_array($tag, $return->tags) and $permission == 0) // check for special group
			{
				$permission = 1;
			}
		}

		if(ApiUser::find($return->character->id) == false)
		{
			ApiUser::create(array(
				                'id' => $return->character->id,
				                'token' => $token,
				                'character_name' => $return->character->name,
				                'alliance_id' => $return->alliance->id,
				                'alliance_name' => $return->alliance->name,
				                'tags' => json_encode($return->tags),
				                'status' => 1,
				                'permission' => $permission
			                ));
		}

		return 'It Worked!!! ('.$return->character->id.': '.$return->character->name.', '.$return->alliance->name.')';
	}

	public function logoutAction()
	{
		Auth::logout();

		return Redirect::route('home')
		               ->with('flash_notice', 'You are successfully logged out.');
	}

}