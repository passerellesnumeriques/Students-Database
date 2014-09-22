<?php
/**
 * Abstract class defining the functionalities an authentication system should provide.
 */
abstract class AuthenticationSystem {

	/** Authenticate a user, and open a session on the authentication system.
	 * @return string a token for subsequent operations, empty string if no other operation is supported but the authentication succeed, or null if it failed
	 * @param string $username username
	 * @param string $password password
	 * @param string $error if the authentication fails, this variable is filled with the error message.
	 */
	public abstract function authenticate($username, $password, &$error);

	/** <i>Optional.</i> Close a session on the authentication system. 
	 * @param string $token from the authenticate method
	 */
	public function closeSession($token) {}
	
	public function extendTokenExpiration($token) {}
	
}

interface AuthenticationSystem_UserList {

	/** <i>Optional.</i> Retrieve the list of users existing on the authentication system.
	 * @param string $token from the authenticate method
	 * @return array of (username,array(people_info_key=>people_info_value)), or NULL if this functionality is not available
	 */
	public function getUserList($token);
	
}

interface AuthenticationSystem_ManageUsers {

	/** <i>Optional.</i> Create a new user on the authentication system.
	 * @param string $token from the authenticate method
	 * @param string $username username
	 * @param string $password password
	 * @return boolean true if successful, false if it fails, null if the functionality is not available
	 */
	public function createUser($token, $username, $password);

	/** <i>Optional.</i> Remove a user on the authentication system.
	 * @param string $token from the authenticate method
	 * @param string $username username
	 * @return boolean true if successful, false if it fails, null if the functionality is not available
	 */
	public function removeUser($token, $username);

	/** <i>Optional.</i> Change a username.
	 * @param string $token from the authenticate method
	 * @param string $username current username
	 * @param string $new_username new username
	 * @return boolean true if successful, false if it fails, null if the functionality is not available
	 */
	public function renameUser($token, $username, $new_username);
	
}

interface AuthenticationSystem_ResetPassword {

	/** <i>Optional.</i> Change the password of a user.
	 * @param string $token from the authenticate method
	 * @param string $username username
	 * @param string $password new password
	 * @return boolean|string|NULL true if successful, string with error message if it fails, null if the functionality is not available
	 */
	public function resetPassword($token, $username, $password);
}
?>