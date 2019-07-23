<?php

// src/AppBundle/Controller/ApiController.php

namespace AppBundle\Controller;
use AppBundle\Entity\FbPages;
use AppBundle\Entity\models\ReclamationModel;
use AppBundle\Entity\pageObject;
use AppBundle\Entity\Reclamation;
use AppBundle\Entity\UserObject;
use Facebook\Facebook;
use FOS\OAuthServerBundle\Model\Token;
use http\Client;
use http\Header;
use \AppBundle\Entity\models;
use OAuth2\Model\IOAuth2Client;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use \FOS\UserBundle\Model\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;

use \Symfony\Component\HttpFoundation\Request as Request;


use Symfony\Component\HttpFoundation\Response;
use DateTime;


class ApiController extends FOSRestController
{
    /**
     * @Route("/api")
     */
    public function indexAction()
    {


       $fosUser = $this->get('security.token_storage')->getToken()->getUser();



        //dateTime system
        $now = new DateTime('now');

        //chercher le client dans le repository
        $userClient = $this->getDoctrine()->getRepository('AppBundle:Admin\Client')->findOneBy(['id' => $fosUser->getClient()->getId()]);

        //mettre a jour lastLogin du client & utilisateur
        $userClient->setLastLogin($now);
        $fosUser->setLastLogin($now);



        //instance de userModal
        $userModal = new models\UserModel($fosUser);
        $userModal->setUsername($fosUser->getUsername());
        $userModal->setEmail($fosUser->getEmail());
        $userModal->setClient($fosUser->getClient());
        $userModal->setRole($fosUser->getRoles());
        $userModal->enabled = $fosUser->isEnabled();
        $userModal->setLastlogin($fosUser->getLastLogin());


        //mise a jour des données dans la bd.
        $this->getDoctrine()->getManager()->flush();

        //serialization et renvoie des données
        return $this->view($userModal,Response::HTTP_OK);

    }

    /**
     * @Rest\Post("/api/me/update")
     *
     */
    public function UpdateMeAction(Request $user)
    {
        $me = $this->get('security.token_storage')->getToken()->getUser();

        $content = json_decode($user->getContent());
        $userObject = $user->request->get('user');

        $userManager = $this->container->get('fos_user.user_manager');

        if(!$this->verifEmail($userObject['email'])){

            $error =[
                "message" => "email invalid",
                "error_description" => "email invalid"
            ];
            return $this->view($error,Response::HTTP_BAD_REQUEST);
        }
        if(strlen($userObject['username'])){
            $me->setUsername($userObject['username']);
            $me->setUsernameCanonical($userObject['username']);
        }

        if(strlen($userObject['email'])){
            $me->setEmail($userObject['email']);
            $me->setEmailCanonical($userObject['email']);
        }

        if(strlen($userObject['password'])>4){
            $me->setPlainPassword($userObject['password']);
        }



        $userManager->updateUser($me);
        return $this->view($user);


    }
/*
* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*                                                       * -addUser: Ajouter un utilisateur.
*                    USER  CONTROLLERS                  * -updateUser: Mettre a jour un utilisateur.
*                                                       *
* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*/





    /**
     * @Rest\Put("/api/admin/user/create")
     */
    public function addUserAction(Request $user)
    {
        $fosUser = $this->get('security.token_storage')->getToken()->getUser();
        $content = json_decode($user->getContent());
        $userObject = $content->user;

        //instance de userManager
        $userManager = $this->container->get('fos_user.user_manager');


        $newUser = $userManager->createUser();


        if ($userObject->password == $userObject->confirm_password) {

            $myClient = $fosUser->getClient();

            try {
                if(!strlen($userObject->username)){
                    return $this->view('username manquant',Response::HTTP_BAD_REQUEST);
                }
                $newUser->setUsername($userObject->username);
                $newUser->setPlainPassword($userObject->password);
                if(!$this->verifEmail($userObject->email)){
                    $error =[
                        'message'=> 'email invalid',
                        'error_desc'=>'email invalid'
                    ];
                    return $this->view($error,Response::HTTP_BAD_REQUEST);
                }
                $newUser->setEmailCanonical($userObject->email);
                $newUser->setEmail($userObject->email);
                $newUser->setUsername($userObject->username);
                $newUser->setUsernameCanonical($userObject->username);
                $newUser->setEnabled(1);
                $newUser->setClient($myClient);
                if ($userObject->role == "ROLE_ADMIN") {
                    $newUser->addRole('ROLE_ADMIN');

                } else {
                    $newUser->addRole('ROLE_USER');
                }
                $userManager->updateUser($newUser);

                //incrementer les nombres de compte du client.
                $myClient->setMembers((int)$myClient->getMembers()+1);
                $this->getDoctrine()->getManager()->persist($myClient);


            } catch(\Doctrine\DBAL\DBALException  $exception) {
                $error=[
                    'message' => 'utilisateur/email deja existant',
                    'error_desc' => $exception->getMessage()
                ];
                return $this->view($error, Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->view('mot de passe non-identiques ', Response::HTTP_NOT_ACCEPTABLE);
        }

        return $this->view($newUser, Response::HTTP_CREATED);


    }



    /**
     * @Rest\Post("/api/admin/user/update")
     *
     */
    public function UpdateUserAction(Request $user)
    {
        $client = $this->get('security.token_storage')->getToken()->getUser()->getClient();

        $content = json_decode($user->getContent());
        $userObject = $content->user;

        $userManager = $this->container->get('fos_user.user_manager');

        $repository  = $this->getDoctrine()->getRepository('AppBundle:User');

        if(!$user = $repository->findOneBy(['id'=> $userObject->id,'client'=>$client])){
            $error = [
                "message" => "utilisateur inexistant",
                "error_description" => "utilisateur introuvable dans la bd"
            ];


            return $this->view($error,Response::HTTP_BAD_REQUEST);
        }

        if(!$this->verifEmail($userObject->email)){

            $error =[
                "message" => "email invalid",
                "error_description" => "email invalid"
            ];
            return $this->view($error,Response::HTTP_BAD_REQUEST);
        }
        if(strlen($userObject->username)){
            $user->setUsername($userObject->username);
            $user->setUsernameCanonical($userObject->username);
        }

        $user->setEmail($userObject->email);
        $user->setEmailCanonical($userObject->email);
        if(strlen($userObject->password)){
            $user->setPlainPassword($userObject->password);
        }

        //$user->setEnabled($userObject->enabled);
        if (strlen($userObject->role)  ) {
            if($userObject->role == "ROLE_ADMIN"){
                $user->addRole('ROLE_ADMIN');
            }
            else {
                $user->addRole('ROLE_USER');
            }
        }

        $userManager->updateUser($user);
        return $this->view($user);


    }

    /**
     * @Rest\Delete("/api/admin/user/delete/{id}")
     */
    public function DeleteUserAction(Request $request,$id){
        $client = $this->get('security.token_storage')->getToken()->getUser()->getClient();
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('AppBundle:User')->findOneBy(['id'=>$id,'client'=>$client]);
        if(!$user){
            return $this->view('utilisateurs inexistant',Response::HTTP_BAD_REQUEST);
        }
        $em->remove($user);
        $em->flush();
        return $this->view('utilisateur supprimé ',Response::HTTP_OK);
    }








    /**
     * @Rest\Get("/api/admin/user/list")
     */
    public function ClientUserAction(Request $request)
    {
        $client = $this->get('security.token_storage')->getToken()->getUser()->getClient();
        $users[] = array();
        $repository  = $this->getDoctrine()->getRepository('AppBundle:User');
        $list = $repository->findBy(['client'=>$client]);
        foreach ($list as $k => $value){
            $u = new UserObject();
            $u->id = $value->getId();
            $u->username = $value->getUsername();
            $u->email = $value->getEmail();
            $u->role = $value->getRoles()[0];
            if(!$u->lastLogin = $value->getLastLogin()){
                $u->lastLogin = 'jamais';
            }
            $u->client = $client->getId();
            $users[$k] = $u;


        }
        return $this->view($users);


    }


//*************************CLIENT UPDATE **************************
    /**
     * @Rest\Post("/api/admin/client/update")
     */
    public function UpdateClientAction(Request $client){
        $updatable = $client->request->get('client');
        $oldClient = $this->get('security.token_storage')->getToken()->getUser()->getClient();

        if($this->verifEmail($updatable['email'])){
            $oldClient->setEmail($updatable['email']);
        }
        if(strlen($updatable['tel'])){
            $oldClient->setTel($updatable['tel']);
        }
        if(strlen($updatable['description'])){
            $oldClient->setDescription($updatable['description']);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($oldClient);
        $em->flush();

        return $this->view($oldClient);

    }


    /*
       * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
       *                                                       *
       *           FACEBOOK-SERVICE  CONTROLLERS               *
       *                                                       *
       * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
    */
    /**
     *
     * @Rest\Put("/api/fbpage/create")
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
     */
    public function listPageAction()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $pages[] = array();


        $user_id = $user->getId();

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
     * @Rest\Delete("/api/fbpage/delete/{id}")
     */
    public function DeleteFBPageAction(Request $request,$id){
        $em = $this->getDoctrine()->getManager();
        $fbPage = $em->getRepository('AppBundle:FbPages')->findOneBy(['id'=>$id]);
        $em->remove($fbPage);
        $em->flush();
        return $this->view(Response::HTTP_OK);
    }
    /**
     * @Rest\Post("/fb/login")
     *
     */
    public function facebookLogin(Request $request){

        $token = $request->request->get('fb_access_token');//recuperer le token
        $client_secret = $request->request->get('client_secret');
        $fb_app_id = $request->request->get('fb_app_id');
        $fb_app_secret = $request->request->get('fb_app_secret');

        //verifier client
        if(!$client = $this->get('fos_oauth_server.client_manager')->findClientBy(array('secret'=>$client_secret))){
            //client non-verifié
            return $this->view('unauthorized client',Response::HTTP_UNAUTHORIZED);
        }
        //initialiser l'obj de connection fb
        $fb = new \Facebook\Facebook([
            'app_id' => $fb_app_id, //FACEBOOK APP ID
            'app_secret' => $fb_app_secret, //FACEBOOK APP SECRET
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


    /*
       * * * * * * * * * * * * * * * * * * * * * * * * * * * * * - addReclamation : ajouter 1 reclamation
       *                                                       * - checkReclamtion: marquer reclamation comme Vu (update)
       *                RECLAMATION  CONTROLLERS               * - listReclamation : lister tout les reclamations
       *                                                       * - deleteReclamation : Supprimer une reclamation
       * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
    */


    /**
     * @Rest\Post("/api/reclamation/add")
     */
    public function addReclamation(Request $request){
        $newreclamation = new Reclamation();

        $reclamation =  $request->request->get('reclamation');
        $em = $this->getDoctrine()->getManager();
        try{
            $myUser = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['id'=>$reclamation['user_id']]);
            $newreclamation->setUserId($myUser);
            $myClient = $this->getDoctrine()->getRepository('AppBundle:Admin\Client')->findOneBy(['id'=>$reclamation['client_id']]);
            $newreclamation->setClientId($myClient);
            $newreclamation->setContenu($reclamation['contenu']);
            $newreclamation->setCreatedAt((new DateTime));
            $newreclamation->setSujet($reclamation['sujet']);
            $newreclamation->setVu(0);
            $em->persist($newreclamation);
            $em->flush($newreclamation);
            return $this->view($newreclamation,Response::HTTP_CREATED);
        }catch(\Exception $e){
            return $this->view($e->getMessage(),Response::HTTP_BAD_REQUEST);
        }
    }


    public function verifEmail($email)
    {
        $constraint = new \Symfony\Component\Validator\Constraints\Email();
        $stringToTest = $email;

        $errors = $this->get('validator')->validate($stringToTest, $constraint);

        if(count($errors) > 0) {
            return false;
        }else{
            return true;
        }


    }
}