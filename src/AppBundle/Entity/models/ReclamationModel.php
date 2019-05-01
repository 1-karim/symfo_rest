<?php
/**
 * Created by PhpStorm.
 * User: sinouj
 * Date: 23-Apr-19
 * Time: 3:17 PM
 */

namespace AppBundle\Entity\models;


class ReclamationModel
{
    public $id;
    public $user;
    public $client;
    public $contenu;
    public $sujet;
    public $created_at;
    public $vu;
    public function __construct()
    {

    }


}