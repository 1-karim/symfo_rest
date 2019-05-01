<?php
// src/AppBundle/Entity/Client.php

namespace AppBundle\Entity;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
/**
 * @ORM\Table("oauth2_clients")
 * @ORM\Entity
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;



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
     * @return DateTime
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * @param DateTime $last_login
     */
    public function setLastLogin($last_login)
    {
        $this->last_login = $last_login;
    }

    public function __construct()
    {
        parent::__construct();
    }
}