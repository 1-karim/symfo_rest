<?php
/**
 * Created by PhpStorm.
 * User: sinouj
 * Date: 01-Jul-19
 * Time: 3:04 PM
 */

namespace AppBundle\Service;


use Doctrine\Common\Persistence\ObjectManager;

class ClientService
{
    public $em;
    public function __construct(ObjectManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function validNewClient($client){      //l'obj $client{username,role,password,email,client}
        $errors = array();

        if(!$this->isUser($client,true)){ //            object $client malformé
            $errors[0] = 'invalid data';
            return $errors;
        }
        if(strlen(trim($client->username)) < 4){                                                      //USERNAME CHECK
            array_push($errors,'invalid username');
        }
        if(!in_array(trim($client->role),array("ROLE_ADMIN","ROLE_USER",""))){                         //ROLE CHECK
            array_push($errors,'invalid role');
        }
        if(!filter_var($client->email, FILTER_VALIDATE_EMAIL)){                                  //EMAIL CHECK
            array_push($errors,'invalid email');
        }
        /*if (!$this->em->getRepository('AppBundle:Admin\Client')->find($client->client)){     //CLIENT CHECK
           array_push($errors,'client not found');
        }*/
        if(strlen($client->password) < 4){
            array_push($errors,'password invalid');
        }
        if(empty($errors)){
            return true;
        }
        return $errors;

    }


    public function validUserUpdate($user){
        $errors = array();
        if(!$this->isUser($user,false)){
            array_push($errors,'user invalid');
            return $errors;
        }

        $errors = $this->validNewUser($user);
        if(!empty($errors)){
            return $errors;
        }else{
            return true;
        }


    }



    public function modelizeUser(User $user){
        $model = new UserModel($user);
        return $model;

    }


    public function isUser($user,$new=false){ // CHECK $user  attributs
        if(!$new){
            if(!isset($user->id)){
                return false;
            }
            else if(!$this->em->find('AppBundle:User',$user->id)){
                return false;
            }
        }

        if(!isset($user->username) || !isset($user->email) || !isset($user->role) || !isset($user->password) ||!isset($user->client)){
            return false;
        }else{
            return true;
        }

    }











































}