<?php

// src/AppBundle/Controller/ApiController.php

namespace AppBundle\Controller;
use AppBundle\Entity\UserObject;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use \FOS\UserBundle\Model\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Request\ParamFetcher;
use \Symfony\Component\HttpFoundation\Request as Request;
use mysql_xdevapi\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;


class ApiController extends FOSRestController
{
    /**
     * @Route("/api")
     */
    public function indexAction()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $view = $this->view($user);
        return $this->handleView($view);

    }





    /**
     * @Rest\Get("/api/users/findbyid")
     * @Rest\QueryParam(name="id", requirements="\d+")
     *
     *
     */
    public function GetUserAction(Request $id)
    {   


        $repository  = $this->getDoctrine()->getRepository('AppBundle:User');
        $list = $repository->find($id);

        return $this->view($list);
        //return $this->handleView($view);

    }

    /**
     * @Rest\Get("/api/users")
     *
     *
     */
    public function listAction()
    {
        $users[] = array();

        $repository  = $this->getDoctrine()->getRepository('AppBundle:User');
        $list = $repository->findAll();
        foreach ($list as $k => $value){
            $u = new UserObject();
            $u->id = $value->getId();
            $u->username = $value->getUsername();
            $u->email = $value->getEmail();
            $u->role = $value->getRoles()[0];
            $u->lastLogin = $value->getLastLogin();
            $u->enabled = $value->isEnabled();

            $users[$k] = $u;

        }
        return $this->view($users);


    }


    /**
     * @Rest\Post("/api/users/update")
     * @Rest\QueryParam(name="id", requirements="\d+")
     *
     *
     */
    public function UpdateUserAction(Request $user)
    {
        $content = json_decode($user->getContent());
        $userObject = $content->user;
        $userManager = $this->container->get('fos_user.user_manager');


        $repository  = $this->getDoctrine()->getRepository('AppBundle:User');
        $user = $repository->find($content->id);
        $user->setUsername($content->username);
        $user->setUsernameCanonical($content->username);
        $user->setEmailCanonical($content->email);
        $user->setPlainPassword($content->password);
        $user->setEnabled($content->enabled);
        $user->setRoles([$content->role]);

        return $this->view($user);
        //return $this->handleView($view);*/

    }

    /**
     * @Rest\Post("/api/create")
     */
    public function addAction(Request $user)
    {

        $emailConstraint = new EmailConstraint();

        $userManager = $this->container->get('fos_user.user_manager');


        $newUser = $userManager->createUser();

        $content = json_decode($user->getContent());
        $userObject = $content->user;

        $errors = $this->get('validator')->validate(
            $userObject->email,
            $emailConstraint
        );



        if($userObject->password == $userObject->confirm_password){

           try{
               $newUser->setUsername($userObject->username);
               $newUser->setPlainPassword($userObject->password);
               $newUser->setEmailCanonical($userObject->email);
               $newUser->setEmail($userObject->email);
               $newUser->setUsername($userObject->username);
               $newUser->setUsernameCanonical($userObject->username);
               $newUser->setEnabled($userObject->enabled);
               if($userObject->role == "ROLE_ADMIN"){
                   $newUser->addRole('ROLE_ADMIN');
               }if($userObject->role == "ROLE_SUPER_ADMIN"){
                   $newUser->addRole('ROLE_SUPER_ADMIN');
               }else{
                   $newUser->addRole('ROLE_USER');
               }
                $userManager->updateUser($newUser);
           }catch (Exception $exception){
               return $this->view($exception->getMessage(),Response::HTTP_NOT_ACCEPTABLE);
           }
       }else{
           return $this->view('mot de passe non-identiques '.$errors,Response::HTTP_NOT_ACCEPTABLE);
       }
       $view =  $this->view($newUser);
       return $this->view($view,Response::HTTP_CREATED);


    }


    //check password
    public function validPassword($user, $oldPassword){

        $user = new Users();    //$this->getDoctrine()->getRepo->getUser($id)
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);

        $bool = $encoder->isPasswordValid($user->getPassword(),$oldPassword,$user->getSalt());
    }





}