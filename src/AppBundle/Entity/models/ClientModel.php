<?php
/**
 * Created by PhpStorm.
 * User: sinouj
 * Date: 01-Jul-19
 * Time: 3:00 PM
 */

namespace AppBundle\Entity\models;


use AppBundle\Entity\User;

class ClientModel
{
    public $id;
    public $name;
    public $date_exp;
    public $tel;
    public $last_login;
    public $email;
    public $members;

    public function __construct(User $user){

        $this->hydrate((array) $user);

    }

    public function hydrate(array $donnees)
    {
        foreach ($donnees as $attribut => $value)
        {
            $method = 'set'.ucfirst($attribut);

            if(method_exists($this, $method)){
                $this->$method($value);
            }
        }
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDateExp()
    {
        return $this->date_exp;
    }

    /**
     * @param mixed $date_exp
     */
    public function setDateExp($date_exp)
    {
        $this->date_exp = $date_exp;
    }

    /**
     * @return mixed
     */
    public function getTel()
    {
        return $this->tel;
    }

    /**
     * @param mixed $tel
     */
    public function setTel($tel)
    {
        $this->tel = $tel;
    }

    /**
     * @return mixed
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * @param mixed $last_login
     */
    public function setLastLogin($last_login)
    {
        $this->last_login = $last_login;
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

    public function isClient($client , $new = true){
        if(!$new){
            if(!isset($client->id)){
                return false;
            }

        }
        if( !$client->name || !$client->tel || !$client->offre || !$client->desc || !$client->date_exp || !$client->inscri){
            return false;
        }
        return true;
    }

}