<?php
// src/AppBundle/Entity/User.php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table("users")
 * @ORM\Entity
 */
class User extends BaseUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * @var mixed
     * @ORM\Column(name="facebook_id", type="string",nullable=true)
     */
    protected $facebookID;



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
     ** @ORM\ManyToOne(targetEntity="AppBundle\Entity\Admin\Client")
     *  @ORM\JoinColumn(name="client_id", referencedColumnName="id",onDelete="CASCADE")
     */
    protected $client;

    /**
     * @return mixed
     */
    public function getFacebookID()
    {
        return $this->facebookID;
    }

    /**
     * @param mixed $facebookID
     */
    public function setFacebookID($facebookID)
    {
        $this->facebookID = $facebookID;
    }


    /**
     * Get id
     *
     * @return integer
     */


    public function getId()
    {
        return $this->id;
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

    public function objToUser($rawObject){

        foreach($rawObject as $attribut => $value){
            $setmethod = 'set'.ucfirst($attribut);
            if(method_exists($this,$setmethod)){
                $this->$setmethod($value);
            }

        }
    }
}