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
use DateTime;

class ApiController extends FOSRestController
{
    /**
     * @Route("/api")
     */
    public function indexAction()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $username = $this->get('security.token_storage')->getToken()->getUsername();
        $repo = $this->getDoctrine()->getRepository('AppBundle:User');
        $fosUser = $repo->findOneBy(['username'=>$username]);
        $now = new DateTime('now');
        $userClient = $this->getDoctrine()->getRepository('AppBundle:Admin\Client')->findOneBy(['id' => $fosUser->getClient()->getId()]);
        $userClient->setLastLogin($now);
        $this->getDoctrine()->getManager()->flush($userClient);

        $view = $this->view($fosUser);

        return $this->handleView($view);

    }




/*
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * -ListUser: Lister tout les users dans la BD
*                                                       * -addUser: Ajouter un utilisateur.
*                    USER  CONTROLLERS                  * -updateUser: Mettre a jour un utilisateurs.
*                                                       *
* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*/



    /**
     * @Rest\Get("/api/user/list")
     *
     *
     */
    public function listUserAction()
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
            if(($value->getClient())){
                $u->client = $value->getClient()->getName();
            }else{
                $u->client = "no client associated";
            }

            $users[$k] = $u;
        }
        return $this->view($users);


    }

    /**
     * @Rest\Put("/api/user/create")
     */
    public function addUserAction(Request $user)
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


        if ($userObject->password == $userObject->confirm_password) {

            $clientRepo = $this->getDoctrine()->getRepository('AppBundle:Admin\Client');
            $myClient = $clientRepo->findOneBy(['id' => (int)$userObject->client]);
            $myClient->setMembers((int)$myClient->getMembers()+1);
            $this->getDoctrine()->getManager()->persist($myClient);
            try {
                $newUser->setUsername($userObject->username);
                $newUser->setPlainPassword($userObject->password);
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
            } catch (Exception $exception) {
                return $this->view($exception->getMessage(), Response::HTTP_NOT_ACCEPTABLE);
            }
        } else {
            return $this->view('mot de passe non-identiques ' . $errors, Response::HTTP_NOT_ACCEPTABLE);
        }
        $view = $this->view($newUser);
        return $this->view($view, Response::HTTP_CREATED);


    }

    /**
     * @Rest\Post("/api/user/update")
     * @Rest\QueryParam(name="id", requirements="\d+")
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
     * @Rest\Delete("/api/user/delete/{id}")
     */
    public function DeleteUserAction(Request $request,$id){
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(['id'=>$id]);
        $em->remove($user);
        $em->flush();
        return $this->view(Response::HTTP_OK);
    }



    /*
    * * * * * * * * * * * * * * * * * * * * * * * * * * * * * -ClientList : lister tout les clients dans la BD.
    *                                                       * -AddClient: Ajout est init un Client a la BD.
    *                Client  CONTROLLERS                    * -ClientUser: Lister tout les users appartenant a un Client precis.
    *                                                       * -UpdateClient: Mettre a jour un client.
    * * * * * * * * * * * * * * * * * * * * * * * * * * * * * -DeleteClient: Supprimer le client ET tout ses utilisateurs
    */


    /**
     * @Rest\Get("/api/admin/client/list")
     */
    public function ClientListAction(Request $request){
        $repo = $this->getDoctrine()->getRepository('AppBundle:Admin\Client');
        $clientList = $repo->findAll();
        return $this->view($clientList,Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/api/admin/client/create")
     */
    public function AddClientAction(Request $client)
    {

        $emailConstraint = new EmailConstraint();

        $clientManager = $this->getDoctrine()->getManager();


        $newClient= new \AppBundle\Entity\Admin\Client();

        $sentClient = $client->request->get('client');


        $errors = $this->get('validator')->validate(
            $sentClient['email'],
            $emailConstraint
        );
        try{
            $newClient->setName($sentClient['name']);
            $newClient->setTel($sentClient['tel']);
            $newClient->setEmail($sentClient['email']);
            $newClient->setDateExp($sentClient['date_exp']);
            $newClient->setDateInscri($sentClient['date_depart']);
            $newClient->setOffre($sentClient['abonnement'].' mois');
            $newClient->setDescription($sentClient['description']);
            $clientManager->persist($newClient);
            $clientManager->flush();

        }catch (Exception $exception){
            return $this->view($exception->getMessage(),Response::HTTP_NOT_ACCEPTABLE);
        }

        $view =  $this->view($newClient);
        return $this->view($view,Response::HTTP_CREATED);


    }

    /**
     * @Rest\Post("/api/admin/client/update")
     */
    public function UpdateClientAction(Request $client){
        $updatable = $client->request->get('client');
        $oldClient = $this->getDoctrine()->getRepository('AppBundle:Admin\Client')->findOneBy(['id'=>$updatable['id']]);
        if(strlen($updatable['name'])){
            $oldClient->setName($updatable['name']);
        }
        if(strlen($updatable['email'])){
            $oldClient->setEmail($updatable['email']);
        }
        if(strlen($updatable['tel'])){
            $oldClient->setTel($updatable['tel']);
        }
        if(strlen($updatable['offre'])){
            $oldClient->setOffre($updatable['offre']);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($oldClient);
        $em->flush();

        return $this->view($oldClient);

    }

    /**
     * @Rest\Delete("/api/admin/client/delete/{id}")
     */
    public function DeleteClientAction(Request $request,$id){
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository('AppBundle:Admin\Client')->findOneBy(['id'=>$id]);
        $em->remove($client);
        $em->flush();
        return $this->view(Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/api/client/users")
     */
    public function ClientUserAction(Request $request)
    {
        $client_id =  $request->request->get('client_id');
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
            if(($value->getClient())){
                $u->client = $value->getClient()->getName();
            }else{
                $u->client = "no client associated";
            }

            $users[$k] = $u;
        }
        return $this->view($users);


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
       * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
       *                                                       *
       *                RECLAMATION  CONTROLLERS               *
       *                                                       *
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
    /**
     * @Rest\Post("/api/reclamation/check")
     */
    public function checkReclamation(Request $request){


        $reclamation =  $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        try{
            $vReclamation = $this->getDoctrine()->getRepository('AppBundle:Reclamation')->findOneBy(['id'=>$reclamation]);

            $vReclamation->setVu(1);
            $em->persist($vReclamation);
            $em->flush($vReclamation);
            return $this->view(Response::HTTP_OK);
        }catch(\Exception $e){
            return $this->view($e->getMessage(),Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Get("/api/reclamation/list")
     */
    public function reclamationList(Request $request){
        $repo = $this->getDoctrine()->getRepository('AppBundle:Reclamation');
        $reclamationList = $repo->findBy(['vu' => 0]);
        $reclamations[] = array();

        foreach ($reclamationList as $k => $reclamation){
            $r = new ReclamationModel();
            $r->id = $reclamation->getId();
            $r->user = $reclamation->getUserId()->getUsername();
            $r->client = $reclamation->getClientId()->getName();
            $r->sujet = $reclamation->getSujet();
            $r->contenu = $reclamation->getContenu();
            $r->created_at = $reclamation->getCreatedAt();
            $r->vu = $reclamation->isVu();
            $reclamations[$k] = $r;
        }
        return $this->view($reclamations,Response::HTTP_OK);
    }

    /**
     * @Rest\Delete("/api/reclamation/delete/{id}")
     */
    public function DeleteReclamationAction(Request $request,$id){
        $em = $this->getDoctrine()->getManager();
        $reclamation = $em->getRepository('AppBundle:Reclamation')->findOneBy(['id'=>$id]);
        $em->remove($reclamation);
        $em->flush();
        return $this->view(Response::HTTP_OK);
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