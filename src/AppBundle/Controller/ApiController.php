<?php

// src/AppBundle/Controller/ApiController.php

namespace AppBundle\Controller;
use AppBundle\Entity\FbPages;
use AppBundle\Entity\pageObject;
use AppBundle\Entity\UserObject;
use Facebook\Facebook;
use FOS\OAuthServerBundle\Model\Token;
use http\Client;
use http\Header;
use OAuth2\Model\IOAuth2Client;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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




    /**
     *
     * @Rest\Post("/api/fbpage/create")
     */
    public function addPageAction(Request $page)
    {

        $newPage = new FbPages();

        $unpackedObj =  $page->request->get('page');
        try{
            $newPage->setDescription($unpackedObj['description']);
            $newPage->setName($unpackedObj['nom']);
            $newPage->setUserId($unpackedObj['user_id']);
            $newPage->setUrl($unpackedObj['url']);
            $pm = $this->getDoctrine()->getManager();
            $pm->persist($newPage);
            $pm->flush($newPage);
            return $this->view('page '.$newPage->getName().' ajoutee avec success',Response::HTTP_CREATED);
        }catch(\Exception $e){
            return $this->view($e->getMessage(),Response::HTTP_BAD_REQUEST);
        }

}

    /**
     * @Rest\Get("/api/fbpage/list")
     *
     *
     */
    public function listPageAction()
    {
        $username = $this->get('security.token_storage')->getToken()->getUsername();

        $pages[] = array();

        $criteria = array('username' => $username);

        $currentUser = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy($criteria);
        $user_id = $currentUser->getId();

        $criteria = array('userId' => $user_id);

        $result = $this->getDoctrine()->getRepository('AppBundle:FbPages')->findBy($criteria);
        foreach ($result as $k => $value){
            $p = new pageObject();
            $p->id = $value->getId();
            $p->name = $value->getName();
            $p->url= $value->getUrl();
            $p->description = $value->getDescription();

            $pages[$k] = $p;
        }

        return $this->view($pages);

    }

    /**
     * @Rest\Post("/fb/login")
     *
     */
    public function facebookLogin(Request $request){

        $token = $request->request->get('token');//recuperer le token

        //verifier client
        if(!$client = $this->get('fos_oauth_server.client_manager')->findClientBy(array('secret'=>'APP SECRET'))){
            //client non-verifié
            return $this->view('unauthorized client',Response::HTTP_UNAUTHORIZED);



        }
        //initialiser l'obj de connection fb
        $fb = new \Facebook\Facebook([
            'app_id' => 'FB APP ID', //FACEBOOK APP ID
            'app_secret' => 'FB APP SECRET', //FACEBOOK APP SECRET
            'default_graph_version' => 'v2.10',
            'default_access_token' => $token,  //le token recuperer dans la requete
        ]);


        //VERIFICATION DU TOKEN(recu du front) AVEC FB
        try {
            $response = $fb->get('/me?fields=id,name,email', $token);
        } catch(\Facebook\Exceptions\FacebookResponseException $e){
            // si graph retourne une erreur
            return $this->view( 'Graph returned an error: ' . $e->getMessage(),Response::HTTP_BAD_REQUEST);
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            // Si la validation echoue ou le FBsdk retourne une erreur
           return $this->view( 'Facebook SDK returned an error: ' . $e->getMessage(),Response::HTTP_BAD_REQUEST);

        }

        $me = $response->getGraphUser();//recuperer l'utilisateur fb.

        // verifier si utilisateur existant
        $repository = $this->getDoctrine()->getRepository('AppBundle:User');
        $currentUser = $repository->findOneBy(['email' => $me->getEmail()]);

        if($currentUser){ //si utilisateur existant
            //ajouter facebookID
            $currentUser->setFacebookID($me->getId());

        }else{
            //nouvel utilisateur
            $em = $this->getDoctrine()->getManager();

            $currentUser = new \AppBundle\Entity\User();
            $currentUser->setEmail($me->getEmail());
            $currentUser->setEmailCanonical($me->getEmail());
            $currentUser->setUsername($me->getName());
            $currentUser->setPlainPassword($this->generateRandomPassword());

            $currentUser->setUsernameCanonical($me->getName());
            $currentUser->setFacebookID($me->getId());
            $em->persist($currentUser);
        }

        $token = $this->get('fos_oauth_server.server')->createAccessToken($client,$currentUser);
        return $this->view($token,Response::HTTP_OK);
    }

    function generateRandomPassword($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }






}