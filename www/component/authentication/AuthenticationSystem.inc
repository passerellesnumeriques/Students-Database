<?php
abstract class AuthenticationSystem {

	/**
	 * @return string a token for subsequent operations, empty string if no other operation is supported but the authentication succeed, or null if it failed
	 * @param string $username
	 * @param string $password
	 */
	public abstract function authenticate($username, $password, &$error);

	/**
	 * @param string $token from the authenticate method
	 */
	public function close_session($token) {}

	/**
	 * @return array of (username,array(people_info_key=>people_info_value)), or NULL if this functionality is not available
	 */
	public function get_user_list($token) { return null; }

	/**
	 * @param string $token from the authenticate method
	 * @param string $username
	 * @param string $password
	 * @return boolean true if successful, false if it fails, null if the functionality is not available
	 */
	public function create_user($token, $username, $password) { return null; }

	/**
	 * @param string $token from the authenticate method
	 * @param string $username
	 * @return boolean true if successful, false if it fails, null if the functionality is not available
	 */
	public function remove_user($token, $username) { return null; }

	/**
	 * @param string $token from the authenticate method
	 * @param string $username
	 * @param string $new_username
	 * @return boolean true if successful, false if it fails, null if the functionality is not available
	 */
	public function rename_user($token, $username, $new_username) { return null; }

	/**
	 * @param string $token from the authenticate method
	 * @param string $username
	 * @param string $password
	 * @return boolean true if successful, false if it fails, null if the functionality is not available
	 */
	public function reset_password($token, $username, $new_username) { return null; }
}
?>