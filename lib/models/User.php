<?php

/**
 * User
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6508 2009-10-14 06:28:49Z jwage $
 */
class Bal_User extends Base_Bal_User {


	# ========================
	# CONSTRUCTORS
	
	
	/**
	 * Apply accessors and modifiers
	 * @version 1.1, April 12, 2010
	 * @return
	 */
	public function setUp ( ) {
		$this->hasMutator('Avatar', 'setAvatar');
		$this->hasMutator('password', 'setPassword');
		return parent::setUp();
	}
	
	
	# ========================
	# MISC
	
	
	/**
	 * Set a File Attachment
	 * @version 1.1, April 12, 2010
	 * @return string
	 */
	protected function setFileAttachment ( $what, $file ) {
		$value = Bal_Doctrine_Core::presetFileAttachment($this,$what,$file);
		return $value === false ? null : $this->_set('Avatar',$value,false);
	}
	
	/**
	 * Set the User's Avatar
	 * @version 1.1, April 12, 2010
	 * @return string
	 */
	public function setAvatar ( $value ) {
		return $this->setFileAttachment('Avatar', $value);
	}
	
	/**
	 * Prepare a User's Password
	 * @version 1.1, April 12, 2010
	 * @return
	 */
	public function preparePassword ( $value ) {
		$password = md5($value);
		return $password;
	}
	
	/**
	 * Reset the User's Password
	 * @version 1.1, April 12, 2010
	 * @return
	 */
	public function resetPassword ( ) {
		# Reset Password
		$password = generate_password();
		$this->password = $password;
		
		# Create Welcome Message
		$Message = new Message();
		$Message->UserFor = $this;
		$Message->useTemplate('user-password-reset',compact('password'));
		$Message->save();
		
		# Chain
		return $this;
	}
	
	/**
	 * Set the User's Password
	 * @version 1.1, April 12, 2010
	 * @return
	 */
	public function setPassword ( $value ) {
		$password = $this->preparePassword($value);
		return $this->_set('password',$password);
	}
	
	/**
	 * Compare the User's Credentials with passed
	 * @version 1.1, April 12, 2010
	 * @return boolean
	 */
	public function compareCredentials ( $username, $password ) {
		return $this->username === $username && ($this->password === $password || $this->password === $this->preparePassword($password));
	}
	
	/**
	 * Set the Role(s) for a User (clear others)
	 * @version 1.1, April 12, 2010
	 * @param mixed $role
	 */
	public function setRole ( $role ) {
		$this->unlink('Roles');
		$this->link('Roles', $role);
		return true;
	}

	/**
	 * Add a Role(s) to the User
	 * @version 1.1, April 12, 2010
	 * @param mixed $role
	 */
	public function addRole ( $role ) {
		$this->link('Roles', $role);
		return true;
	}

	/**
	 * Does user have Role?
	 * @version 1.1, April 12, 2010
	 * @param mixed $permission
	 */
	public function hasRole ( $role ) {
		// Prepare
		if ( is_object($role) ) {
			$role = $role->code;
		} elseif ( is_array($role) ) {
			$role = $role['code'];
		}
		// Search
		$List = $this->Roles;
		foreach ( $List as $Role ) {
			if ( $role === $Role->code ) {
				$result = true;
				break;
			}
		}
		// Done
		return $result;
	}
	
	/**
	 * Does user have Permission?
	 * @version 1.1, April 12, 2010
	 * @param mixed $permission
	 */
	public function hasPermission ( $permission ) {
		// Prepare
		if ( is_object($permission) ) {
			$permission = $permission->code;
		} elseif ( is_array($permission) ) {
			$permission = $permission['code'];
		}
		// Search
		$List = $this->Permissions;
		foreach ( $List as $Permission ) {
			if ( $permission === $Permission->code ) {
				$result = true;
				break;
			}
		}
		// Done
		return $result;
	}
	
	/**
	 * Activate this User
	 * @version 1.1, April 12, 2010
	 * @return string
	 */
	public function activate ( ) {
		# Proceed
		$this->status = 'published';
		
		# Done
		return true;
	}
	
	/**
	 * Has the user been activated?
	 * @version 1.1, April 12, 2010
	 * @return string
	 */
	public function isActive ( ) {
		return $this->status === 'published';
	}
	
	
	# ========================
	# ENSURES
	
	
	/**
	 * Ensure Uid
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return bool
	 */
	public function ensureUid ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave','postSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$User = $Event->getInvoker();
		
		# Is it different?
		$uid = md5($this->username.$this->email);
		if ( $User->get('uid') != $uid ) {
			$User->set('uid', $uid, false);
			$save = true;
		}
		
		# Return
		return $save;
	}
	
	/**
	 * Ensure Fullname
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensureFullname ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave','postSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$User = $Event->getInvoker();
		
		# Fullname
		$fullname = implode(' ', array($User->title, $User->firstname, $User->lastname));
		if ( $User->get('fullname') !== $fullname ) {
			$User->set('fullname', $fullname, false); // false at end to prevent comparison
			$save = true;
		}
		
		# Return
		return $save;
	}
	
	/**
	 * Ensure Code
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensureCode ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$User = $Event->getInvoker();
		
		# Fullname
		if ( !$User->get('code') ) {
			$User->set('code', $User->username, false); // false at end to prevent comparison
			$save = true;
		}
		
		# Return
		return $save;
	}
	
	/**
	 * Ensure Displayname
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensureDisplayname ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$User = $Event->getInvoker();
		
		# Fullname
		if ( !$User->get('displayname') ) {
			$User->set('displayname', $User->username, false); // false at end to prevent comparison
			$save = true;
		}
		
		# Return
		return $save;
	}
	
	/**
	 * Ensure Username
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensureUsername ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$User = $Event->getInvoker();
		
		# Fullname
		if ( !$User->get('username') ) {
			$User->set('username', $User->email, false); // false at end to prevent comparison
			$save = true;
		}
		
		# Return
		return $save;
	}
	
	/**
	 * Ensure Tags
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return bool
	 */
	public function ensureSubscriptionTags ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('preSave','postSave')) ) {
			# Not designed for these events
			return null;
		}
		
		# Handle
		$save = Bal_Doctrine_Core::ensureTags($Event,'SubscriptionTags','subscriptions');
		
		# Return save
		return $save;
	}
	
	/**
	 * Ensure Messages
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @param string $Event_type
	 * @return boolean	wheter or not to save
	 */
	public function ensureMessages ( $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('postInsert')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$User = $Event->getInvoker();
		
		# Create Welcome Message
		$Message = new Message();
		$Message->UserFor = $User;
		$Message->useTemplate('user-insert');
		$Message->save();
		
		# Return save
		return $save;
	}
	
	/**
	 * Ensure Consistency
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensure ( $Event, $Event_type ){
		return Bal_Doctrine_Core::ensure($Event,$Event_type,array(
			'ensureCode',
			'ensureUid',
			'ensureFullname',
			'ensureUsername',
			'ensureDisplayname',
			'ensureSubscriptionTags',
			'ensureMessages'
		));
	}
	
	
	# ========================
	# CHECKS
	
	
	/**
	 * Ensure the current Identity has sufficient access to perform the operation
	 * @version 1.0, April 12, 2010
	 * @param User $Identity
	 * @param string $action [optional]
	 * @param bool $throw [optional]
	 * @return bool	whether or not the check passed
	 */
	public static function checkAccess ( User $Identity, $action = null, $throw = true ) {
		# Check
		if ( !in_array($Event_type,array('postInsert')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare result
		$result = true;
		
		# Prepare action
		if ( !$action ) {
			# Default
			$action = !delve($User,'id') ? 'create' : 'update';
		}
		
		# Check Permission
		if ( !$Identity->hasPermission('user') ) {
			# Does not have CRUD permissions
			# Fallback Checks
		
			# Check Ownership
			if ( $action === 'update' && delve($this,'id') !== $Identity->id ) {
				if ( $throw ) {
					# Throw Exception
					throw new Doctrine_Exception('error-user-access');
				}
				# Fail result
				$result = false;
			}
		}
		
		# Return result
		return $result;
	}
	
	
	# ========================
	# EVENTS
	
	
	/**
	 * preSave Event
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return
	 */
	public function preSave ( $Event ) {
		# Prepare
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			// no need
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * postSave Event
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return
	 */
	public function postSave ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$result = true;
		
		# Ensure
		$save = self::ensure($Event, __FUNCTION__);
		if ( $save ) {
			$Invoker->save();
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * Post Insert Event
	 * @version 1.1, April 12, 2010
	 * @param Doctrine_Event $Event
	 * @return string
	 */
	public function postInsert ( $Event ) {
		# Prepare
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			// no need
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	
	# ========================
	# CRUD HELPERS
	
	
	/**
	 * Fetch all the records for public access
	 * @version 1.0, April 12, 2010
	 * @return mixed
	 */
	public static function fetch ( array $params = array() ) {
		# Prepare
		Bal_Doctrine_Core::prepareFetchParams($params,array('fetch','Identity','User','UserFor','UserFrom'));
		extract($params);
		
		# Query
		$Query = Doctrine_Query::create();
		
		# Handle
		switch ( $fetch ) {
			case 'Subscribers':
				$Query
					->select('User.id, User.displayname, User.fullname, User.username, User.created_at, User.email, User.type, User.status, User.created_at, Avatar.url')
					->addSelect('User.subscriptions, SubscriptionTag.name, COUNT(MessagesPublishedFor.id) as subscription_published_count')
					->from('User.SubscriptionTags SubscriptionTag')
					->where('User.status = ?', 'published')
					->andWhere('User.subscriptions != ?', '')
					->orderBy('User.email ASC')
					->leftJoin('User.MessagesFor MessagesPublishedFor WITH MessagesPublishedFor.template = ? AND MessagesPublishedFor.status = ?', array('content-subscription','published'))
					->groupBy('User.id')
					;
				break;
			
			default:
				$Query
					->select('User.id, User.displayname, User.fullname, User.username, User.created_at, User.email, User.type, User.status, User.created_at, Avatar.url')
					->from('User, User.Avatar Avatar')
					->orderBy('User.username ASC')
					;
				break;
		}
		
		# Criteria
		if ( $Identity ) { // Ensure returned users are below our level - pawns should be unaware of kings
			$Query->andWhere('User.level <= ?', $Identity->level);
		}
		if ( $User ) {
			$User = Bal_Doctrine_Core::resolveId($User);
			$Query->andWhere('User.id = ?', $User);
		}
		
		# Fetch
		$result = Bal_Doctrine_Core::prepareFetchResult($params,$Query,'User');
		
		# Done
		return $result;
	}
	
	/**
	 * Fetch a form for a User
	 * @version 1.1, April 12, 2010
	 * @param Bal_Model_User $User
	 * @return Zend_Form
	 */
	public static function fetchForm ( Bal_User $User ) {
		# Prepare
		$Form = Bal_Form_Doctrine::createForm('User');
		
		# Group Elements
		$elements = array(
			'essential' => array(
				'username','password','email','displayname','type','status'
			),
			'names' => array(
				'title','firstname','lastname','description'
			),
			'contact' => array(
				'phone','address1','address2','suburb','state','country'
			),
			'other' => array(
				'subscriptions', 'Avatar', 'Permissions', 'Roles'
			)
		);
		
		# Add Id
		Bal_Form_Doctrine::addIdElement($Form,'User',$User);
		
		# Generate Elements
		$Elements = Bal_Form_Doctrine::addElements($Form, 'User', $elements, $User);
		
		# Return Form
		return $Form;
	}
	
}
