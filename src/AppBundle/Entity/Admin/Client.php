<?php

namespace AppBundle\Entity\Admin;

use Doctrine\ORM\Mapping as ORM;

/**
 * Client
 *
 * @ORM\Table(name="admin_client")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Admin\ClientRepository")
 */
class Client
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="tel", type="string", length=100, nullable=true, unique=true)
     */
    private $tel;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="offre", type="string", length=255, nullable=true)
     */
    private $offre;

    /**
     * @var string
     *
     * @ORM\Column(name="date_exp", type="string", length=255, nullable=true)
     */
    private $dateExp;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="date_inscri", type="string", length=255, nullable=true)
     */
    private $dateInscri;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Client
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set tel
     *
     * @param string $tel
     *
     * @return Client
     */
    public function setTel($tel)
    {
        $this->tel = $tel;

        return $this;
    }

    /**
     * Get tel
     *
     * @return string
     */
    public function getTel()
    {
        return $this->tel;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Client
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set offre
     *
     * @param string $offre
     *
     * @return Client
     */
    public function setOffre($offre)
    {
        $this->offre = $offre;

        return $this;
    }

    /**
     * Get offre
     *
     * @return string
     */
    public function getOffre()
    {
        return $this->offre;
    }

    /**
     * Set dateExp
     *
     * @param string $dateExp
     *
     * @return Client
     */
    public function setDateExp($dateExp)
    {
        $this->dateExp = $dateExp;

        return $this;
    }

    /**
     * Get dateExp
     *
     * @return string
     */
    public function getDateExp()
    {
        return $this->dateExp;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Client
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set dateInscri
     *
     * @param string $dateInscri
     *
     * @return Client
     */
    public function setDateInscri($dateInscri)
    {
        $this->dateInscri = $dateInscri;

        return $this;
    }

    /**
     * Get dateInscri
     *
     * @return string
     */
    public function getDateInscri()
    {
        return $this->dateInscri;
    }
}

