<?

class PhpcUser {
	var $id;
	var $attempted_login;

	function PhpcUser()
	{
		$db = phpc_get_db();
		$calendar = phpc_get();
		$this->attempted_login = false;

		if(!empty($calendar->session['uid'])) {
			$this->id = $calendar->session['uid'];
			$this->attempted_login = true;
			return;
		}
		
		if(!empty($calendar->vars['username']) &&
				 !empty($calendar->vars['password'])) {
			$result = $db->get_userdata($calendar->vars['username'],
					$calendar->vars['password']);
			$this->attempted_login = true;
			if($result) {
				$this->id = $result['userID'];
				$calendar->session['uid'] = $this->id;
				return;
			}
		}

		// we didn't login
		$this->id = 0;
		$this->username = 'guest';
		$this->password = '';
	}

	function logged_in()
	{
		return $this->id != 0;
	}

	function attempted_login()
	{
		return $this->attempted_login;
	}

	function is_admin()
	{
		return false;
	}
}

function phpc_get_user()
{
	static $user;

	if(!isset($user)) {
		$user = new PhpcUser;
	}

	return $user;
}
