<?php
namespace App\Db;

use Tk\Auth;
use Tk\Auth\Exception;



/**
 * Class User
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class User extends \Tk\Db\Map\Model
{
    static $HASH_FUNCTION = 'md5';

    const ROLE_ADMIN = 'admin';
    const ROLE_COORD = 'coordinator';
    const ROLE_LECTURER = 'lecturer';
    const ROLE_STUDENT = 'student';
    const ROLE_USER = 'user';



    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $institutionId = 0;

    /**
     * @var string
     */
    public $uid = '';

    /**
     * @var string
     */
    public $username = '';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $email = '';

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var string
     */
    public $hash = '';

    /**
     * @var string
     */
    public $notes = '';

    /**
     * @var \DateTime
     */
    public $lastLogin = null;

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;


    /**
     *
     */
    public function __construct()
    {
        $this->modified = new \DateTime();
        $this->created = new \DateTime();
    }

    /**
     * 
     * @throws \Tk\Exception
     */
    public function save()
    {
        $this->getHash();
        if (!$this->uid) {
            $this->uid = hash(self::$HASH_FUNCTION, $this->username . $this->created->format(\Tk\Date::ISO_DATE));
        }
        parent::save();
    }

    /**
     * Create a random password
     *
     * @param int $length
     * @return string
     */
    public static function createPassword($length = 8)
    {
        $chars = '234567890abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $i = 0;
        $password = '';
        while ($i <= $length) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
            $i++;
        }
        return $password;
    }

    /**
     * Get the user hash or generate one if needed
     *
     * @return string
     * @throws \Tk\Exception
     */
    public function getHash()
    {
        if (!$this->username)
            throw new \Tk\Exception('The username must be set before calling getHash()');

        if (!$this->hash) {
            $this->hash = $this->generateHash();
        }
        return $this->hash;
    }

    /**
     * Helper method to generate user hash
     * 
     * @return string
     */
    public function generateHash() 
    {
        return hash(self::$HASH_FUNCTION, sprintf('%s', $this->username));
    }

    public function delete()
    {
        \App\Db\Role::getMapper()->deleteAllUserRoles($this->id);
        return parent::delete();
    }

    /**
     * Set the password from a plain string
     *
     * @param $str
     * @throws Exception
     */
    public function setPassword($str = '')
    {
        if (!$str) {
            $str = self::createPassword(10);
        }
        $this->password = hash(self::$HASH_FUNCTION, $str . $this->getHash());
    }

    /**
     * Return the users home|dashboard relative url
     *
     * @return string
     * @throws \Exception
     */
    public function getHomeUrl()
    {
        if ($this->hasRole(self::ROLE_ADMIN))
            return '/admin/index.html';
        if ($this->hasRole(array(self::ROLE_COORD, self::ROLE_LECTURER)))
            return '/staff/index.html';
        if ($this->hasRole(self::ROLE_STUDENT))
            return '/student/index.html';
        if ($this->hasRole(self::ROLE_USER))
            return '/user/index.html';
        return '/index.html';   // Should not get here unless their is no roles
        //maybe we should throw an exception instead??
        //throw new \Tk\Exception('No suitable roles found please contact your administrator.'); 
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        $roles = \App\Db\Role::getMapper()->findByUserId($this->id);
        $arr = array();
        foreach ($roles as $role) {
            $arr[] = $role->name;
        }
        return $arr;
    }

    /**
     * Check if this user has the required permission
     * 
     * This method can check for a Role name  
     * or an object as a singular or as an array.
     * 
     * @param string|Role|array $role
     * @return boolean
     */
    public function hasRole($role)
    {
        if (!is_array($role)) $role = array($role);
        
        foreach ($role as $r) {
            if (!$r instanceof Role) {
                $r = Role::getMapper()->findByName($r);
            }
            if ($r) {
                $obj = Role::getMapper()->findRole($r->id, $this->id);
                if ($obj) {
                    return true;
                }
            }
        }
        return false;
    }

}


class UserValidator extends \Tk\Db\Map\Validator
{

    /**
     * Implement the validating rules to apply.
     *
     */
    protected function validate()
    {
        /** @var User $obj */
        $obj = $this->getObject();

        if (!$obj->name) {
            $this->addError('name', 'Invalid field value.');
        }
        if (!$obj->username) {
            $this->addError('username', 'Invalid field value.');
        } else {
            $dup = User::getMapper()->findByUsername($obj->username);
            if ($dup && $dup->getId() != $obj->getId()) {
                $this->addError('username', 'This username is already in use.');
            }
        }

        if (!filter_var($obj->email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'Please enter a valid email address');
        } else {
            $dup = User::getMapper()->findByEmail($obj->email);
            if ($dup && $dup->getId() != $obj->getId()) {
                $this->addError('email', 'This email is already in use.');
            }
        }

    }
}
