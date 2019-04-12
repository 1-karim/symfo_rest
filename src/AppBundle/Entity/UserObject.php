<?php
/**
 * Created by PhpStorm.
 * User: sinouj
 * Date: 05-Apr-19
 * Time: 5:03 PM
 */

namespace AppBundle\Entity;


class UserObject
{
    public $username;
    public $email;
    public $role;
    public $id ;
    public $enabled;
    public $lastLogin;

    function __construct()
    {

    }
}