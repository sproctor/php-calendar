<?

class PhpcUser {
	var $id;
	var $username;
	var $password;

	function PhpcUser()
	{
		$db = phpc_get_db();
		$calendar = phpc_get();

		if(!empty($calendar->session['uid'])
				&& !empty($calendar->session['username'])
				&& !empty($calendar->session['password'])) {
			$this->id = $calendar->session['uid'];
			$this->username = $calendar->session['username'];
			$this->password = $calendar->session['password'];
		} elseif($result = $db->get_userdata
				($calendar->vars['username'],
				 $calendar->vars['password'])) {
			$this->id = $result['id'];
			$calendar->session['uid'] = $this->id;
			$this->username = $result['username'];
			$calendar->session['username'] = $this->username;
			$this->password = $result['password'];
			$calendar->session['password'] = $this->password;
		} else {
			$this->id = 0;
			$this->username = 'guest';
			$this->password = '';
		}
	}

	function logged_in()
	{
		return false;
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
