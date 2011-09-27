<?php
/**
 * @package Authentication
 */

/**
 * An authentication method for the Central Authentication Service (CAS) http://www.jasig.org/cas
 *
 * @package Authentication
 */
class CASAuthentication
	extends AuthenticationAuthority
{
	/**
	 * Class for user objects. Most subclasses will override this
	 * @var string
	 */
	protected $userClass='CASUser';

	/**
	 * Initializes the authority objects based on an associative array of arguments
	 * @param array $args an associate array of arguments. The argument list is dependent on the authority
	 *
	 * General - Required keys:
	 *   TITLE => The human readable title of the AuthorityImage
	 *   INDEX => The tag used to identify this authority @see AuthenticationAuthority::getAuthenticationAuthority
	 *
	 * General - Optional keys:
	 *   LOGGEDIN_IMAGE_URL => a url to an image/badge that is placed next to the user name when logged in
	 *
	 * CAS - Required keys:
	 *   CAS_PROTOCOL => The protocol to use. Should be equivalent to one of the phpCAS constants, e.g. "2.0":
	 *                   CAS_VERSION_1_0 => '1.0', CAS_VERSION_2_0 => '2.0', SAML_VERSION_1_1 => 'S1'
	 *   CAS_HOST => The host name of the CAS server, e.g. "cas.example.edu"
	 *   CAS_PORT => The port the CAS server is listening on, e.g. "443"
	 *   CAS_PATH => The path of the CAS application, e.g. "/cas/"
	 *   CAS_CA_CERT => The filesystem path to a CA certificate that will be used to validate the authenticity
	 *                  of the CAS server, e.g. "/etc/tls/pki/certs/my_ca_cert.crt". If empty, no certificate
	 *                  validation will be performed (not recommended for production).
	 *
	 * CAS - Optional keys:
	 *   ATTRA_EMAIL => Attribute name for the user's email adress, e.g. "email". This only applies if your 
	 *                  CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
	 *   ATTRA_FIRST_NAME => Attribute name for the user's first name, e.g. "givename". This only applies if your 
	 *                       CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
	 *   ATTRA_LAST_NAME => Attribute name for the user's last name, e.g. "surname". This only applies if your 
	 *                      CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
	 *   ATTRA_FULL_NAME => Attribute name for the user's full name, e.g. "displayname". This only applies if your 
	 *                      CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
	 *
	 * NOTE: Any subclass MUST call parent::init($args) to ensure proper operation
	 *
	 */
	public function init($args) {
		parent::init($args);
	
		// include the PHPCAS library
		if (empty($args['CAS_PHPCAS_PATH']))
			require_once('CAS.php');
		else
			require_once($args['CAS_PHPCAS_PATH'].'/CAS.php');
	
		if (empty($args['CAS_PROTOCOL']))
			throw new Exception('CAS_PROTOCOL value not set for ' . $this->AuthorityTitle);
	
		if (empty($args['CAS_HOST']))
			throw new Exception('CAS_HOST value not set for ' . $this->AuthorityTitle);
	
		if (empty($args['CAS_PORT']))
			throw new Exception('CAS_PORT value not set for ' . $this->AuthorityTitle);
	
		if (empty($args['CAS_PATH']))
			throw new Exception('CAS_PATH value not set for ' . $this->AuthorityTitle);
	
		phpCAS::client($args['CAS_PROTOCOL'], $args['CAS_HOST'], intval($args['CAS_PORT']), $args['CAS_PATH'], false);
	
		if (empty($args['CAS_CA_CERT']))
			phpCAS::setNoCasServerValidation();
		else
			phpCAS::setCasServerCACert($args['CAS_CA_CERT']);
		
		// Record any attribute mapping configured.
		if (!empty($args['ATTRA_EMAIL']))
			CASUser::mapAttribute('Email', $args['ATTRA_EMAIL']);
		if (!empty($args['ATTRA_FIRST_NAME']))
			CASUser::mapAttribute('FirstName', $args['ATTRA_FIRST_NAME']);
		if (!empty($args['ATTRA_LAST_NAME']))
			CASUser::mapAttribute('LastName', $args['ATTRA_LAST_NAME']);
		if (!empty($args['ATTRA_FULL_NAME']))
			CASUser::mapAttribute('FullName', $args['ATTRA_FULL_NAME']);
	}
	
	/**
	 * Login a user based on supplied credentials
	 * @param string $login 
	 * @param string $password
	 * @param Module $module 
	 * @see AuthenticationAuthority::reset()
	 * 
	 */
	public function login($login, $password, Session $session, $options)
	{
		phpCAS::forceAuthentication();
		$user = new $this->userClass($this);
		$session->login($user);
		return AUTH_OK;
	}
	
	/**
	 * Attempts to authenticate the user using the included credentials
	 * @param string $login the userid to login (this will be blank for OAUTH based authorities)
	 * @param string $password password (this will be blank for OAUTH based authorities)
	 * @param User &$user This object is passed by reference and should be set to the logged in user upon sucesssful login
	 * @return int should return one of the AUTH_ constants
	 */
	protected function auth($login, $password, &$user) {
		return AUTH_FAILED;
	}

	/**
	 * Retrieves a user object from this authority
	 * @param string $login the userid to retrieve
	 * @return User a valid user object or false if the user could not be found
	 * @see User object
	 */
	public function getUser($login) {
		// don't try if it's empty
		if (empty($login) || !phpCAS::isAuthenticated()) {
			return new AnonymousUser();
		}

		if ($login == phpCAS::getUser()) {
			return new $this->userClass($this);
		}
	}

	/**
	 * Retrieves a group object from this authority. Authorities which do not provide group information
	 * should always return false
	 * @param string $group the shortname of the group to retrieve
	 * @return UserGroup a valid group object or false if the group could not be found
	 * @see UserGroup object
	 */
	public function getGroup($group) {
		return false;
	}

	/**
	 * Validates an authority for connectivity
	 * @return boolean. True if connectivity is established or false if it is not. Authorities may also set an error object to provide more information.
	 */
	public function validate(&$error) {
		return true;
	}

	/**
	  * Returns an array of valid user login types. Subclasses can override this to indicate valid
	  * values
	  * @return array a list of valid user login types
	  */
	protected function validUserLogins() {
		return array('LINK', 'NONE');
	}
}

/**
  * @package Authentication
  */
class CASUser
	extends User
{
	/**
	 * An array of the attribute mapping.
	 * 
	 * @var array $attributeMap
	 */
	private static $attributeMap = array();
	
	/**
	 * Configure attribute mappings.
	 * 
	 * @param string $userProperty
	 * @return string $remoteAttribute
	 */
	public static function mapAttribute ($userProperty, $remoteAttribute) {
		if (empty($userProperty))
			throw new Exception('$userProperty must not be empty.');
		if (isset(self::$attributeMap[$userProperty]))
			throw new Exception('User property '.$userProperty.' is already mapped.');
		if (!method_exists('User', 'set'.$userProperty))
			throw new Exception('Unknown User property '.$userProperty.'.');
		if (empty($remoteAttribute))
			throw new Exception('$remoteAttribute must not be empty.');

		self::$attributeMap[$userProperty] = $remoteAttribute;
	}
	
	/**
	 * Constructor
	 *
	 * @param AuthenticationAuthority $AuthenticationAuthority
	 * @return void
	 */
	public function __construct (AuthenticationAuthority $AuthenticationAuthority) {
		parent::__construct($AuthenticationAuthority);

		if (!phpCAS::isAuthenticated())
			phpCAS::forceAuthentication();

		$this->setUserID(phpCAS::getUser());
		
		if (!method_exists('phpCAS', 'getAttribute'))
			throw new Exception('CASAuthentication attribute mapping requires phpCAS 1.2.0 or greater.');
		
		foreach (self::$attributeMap as $property => $attribute) {
			if (phpCAS::hasAttribute($attribute)) {
				$method = 'set'.$property;
				$this->$method(phpCAS::getAttribute($property));
			}
		}
	}
}