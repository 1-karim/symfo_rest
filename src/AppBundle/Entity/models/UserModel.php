<?php
namespace AppBundle\Entity\models;
use AppBundle\Entity\User;

class UserModel
{

    public $id;
    public $username;
    public $role;
    public $email;
    public $password;
    public $confirmPassword;
    public $client;
    public $lastLogin;




    public function __construct( $user){


        $this->hydrate($user);

    }

    public function hydrate($user)
    {

        foreach($this as $attribut => $value)
        {
            $setmethod = 'set'.ucfirst($attribut);
            $getmethod = 'get'.ucfirst($attribut);
            if(method_exists($this,$setmethod)){
                //method exists in model
                if(method_exists($user,$getmethod)){
                    //method exists in ormObj
                    $this->$setmethod($user->$getmethod());
                }
            }
        }

    }

    /**
     * @return mixed
     */
    public function getConfirmPassword()
    {
        return $this->confirmPassword;
    }

    /**
     * @param mixed $confirmPassword
     */
    public function setConfirmPassword($confirmPassword)
    {
        $this->confirmPassword = $confirmPassword;
    }


    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }





    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getLastlogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param mixed $lastLogin
     */
    public function setLastlogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }
    public function objToUser($rawObject){

        foreach($rawObject as $attribut => $value){

            $setmethod = 'set'.ucfirst($attribut);
            if(method_exists($this,$setmethod)){
                $this->$setmethod($value);
            }

        }
    }



}
?>