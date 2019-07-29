<?php

// src/AppBundle/Controller/ApiController.php

namespace AppBundle\Controller;
use AppBundle\Entity\FbPages;

use AppBundle\Entity\pageObject;
use AppBundle\Entity\Reclamation;
use AppBundle\Entity\UserObject;
use AppBundle\Entity\User;
use \AppBundle\Entity\models;
use AppBundle\Entity\models\UserModel;
use JMS\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;
use \Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpFoundation\Response;
use DateTime;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Nelmio\ApiDocBundle\Swagger as SWG;



class ApiController extends FOSRestController
{
    /**
     * Retourne l'utilisateur courant . Type : UserModel
     *
     *
     * @ApiDoc(
     *     resource=true,
     *     description="Get current user object",
     *     section="USER",
     *     input={
     *          "class"="AppBundle\Entity\models\UserModel"
     *     },
     *     statusCodes = {
     *        201 = "Returned when successful",
     *        400 = "Returned when the form has errors"
     *    },
     *     responseMap={
     *      200= {"class"=UserModel::class,"groups"={"user"}, "name" = "user","description"="utilisateur ajouté"},
     *      400= {"class"=UserModel::class, "form_errors"=true, "name" = ""}
     *
     *     },
     *     headers={
     *         {
     *             "name"="Content-type",
     *             "description"="(Optional) not required in angular 6+",
     *
     *         },
     *         {
     *             "name"="access_token",
     *             "description"="access_token valid ",
     *             "type"="string",
     *             "required"=true,
     *
     *         }
     *     },
     *     output={
     *      "collection"=false,
     *      "collectionName"="User service",
     *      "class"="UserModel::class",
     *
     *      },
     *     tags={
     *         "ROLE_USER" = "#99cc33"
     *     }
     * )
     *
     *@Rest\View(statusCode=Response::HTTP_OK)
     * @Route("/api",methods={"GET"})
     *
     */
    public function indexAction()
    {

       $fosUser = $this->get('security.token_storage')->getToken()->getUser();
        //dateTime system
        $now = new DateTime('now');
        //chercher le client dans le repository
        $userClient = $this->getDoctrine()->getRepository('AppBundle:Admin\Client')->findOneBy(['id' => $fosUser->getClient()->getId()]);
        //mise a jour lastLogin du client & utilisateur
        $userClient->setLastLogin($now);
        $fosUser->setLastLogin($now);
        //instance de userModal
        $userModal = new models\UserModel($fosUser,true);
        //serialization et renvoie des données
        return $this->view( (array)$userModal,Response::HTTP_OK);

    }

    /**
     * Mais a jour l'utilisateur courant (porteur du token) , Retourne l'utilisateur apres mise a jour .  type de retour : Obj UserModel
     * @ApiDoc(
     *     authenticationRoles={"Role_ADMIN"},
     *     resourceDescription="Operations on users.",
     *     resource=true,
     *     parameters={
     *          {"name"="user", "dataType"="Object", "required"=true,"format"="JSON", "description"="user :{ id : integer,  username : string, email : string, client : ClientObj, role : string}"}
     *      },
     *     requirements={
     *
     *      },
     *     description="Update current user",
     *     section="USER",
     *     statusCodes={
     *         401="Returned when the user is not authorized to say hello OR access_token expired",
     *         200="contenant l'Obj User"
     *     },
     *
     *      headers={
     *         {
     *             "name"="Content-type",
     *              "description"="(Optional) not required in angular 6+",
     *
     *         },
     *         {
     *             "name"="access_token",
     *             "description"="access_token valid ",
     *             "required"=true,
     *
     *         }
     *     },
     *     output={
     *      "section"="USER",
     *      "collectionName"="User service",
     *      "class"="AppBundle\Entity\Models\UserModel.php",
     *      "description"="User Object ={ username}",
     *     "authenticationRoles"={"ROLE_ADMIN"}
     *      },
     *     tags={
     *         "ROLE_USER" = "#99cc33"
     *     }
     * )
     *
     *
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
*                                                       * -addUser: Ajoute un utilisateur.
*                    USER  CONTROLLERS                  * -updateUser: Mais a jour un utilisateur.
*                                                       * -deleteUser: Suppr utilisateur
* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*/


    /**
     *
     *
     *
     * Ajoute un utilisateurs dans la meme agence , Retourne l'objet ajouté.
     * @ApiDoc(
     *
     *     resourceDescription="Operations on users.",
     *     input={"class"= "AppBundle\Entity\models\UserModel", "name"=""},
     *     resource=true,
     *     parameters={
     *          {"name"="user", "dataType"="Json Object", "required"=true,"format"="JSON", "description"=" user :{ username : string , email : string , role : string , password: string }"}
     *      },
     *     requirements={
     *
     *      },
     *     description="add new user ",
     *     section="ADMIN",
     *     statusCodes={
     *
     *         200="Operation Reussie",
     *         400="( 'access_token' invalid ) OR (no 'access_token' provided)"
     *     },
     *     responseMap={
     *      200= {"class"=UserModel::class,"groups"={"user"}, "name" = "user","description"="utilisateur ajouté"},
     *      400= {"class"=UserModel::class, "form_errors"=true, "name" = ""}
     *
     *     },
     *      headers={
     *         {
     *             "name"="Content-type",
     *              "description"="Application/JSON",
     *
     *         },
     *         {
     *             "name"="access_token",
     *             "description"="access_token valid ",
     *             "required"=true,
     *
     *         }
     *     },
     *     output={
     *
     *
     *      "collectionName"="User service",
     *      "class"="AppBundle\Entity\Models\UserModel",
     *      "description"="User Object ={ username}",
     *     "authenticationRoles"={"ROLE_ADMIN"}
     *      },
     *     tags={
     *          "[ROLE_ADMIN]"="#ffae42"
     *       }
     *
     * )
     *@Rest\View(statusCode=Response::HTTP_CREATED)
     *
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
            return $this->view('mot de passe non-identiques ', Response::HTTP_BAD_REQUEST);
        }

        return $this->view($newUser, Response::HTTP_CREATED);


    }



    /**
     *
     * Mais a jour un utilisateur dans la meme agence que l'utilisateur courrant , Retourne l'utilisateur apres mise a jour ,  type de retour : UserModel
     * @ApiDoc(
     *
     *     authenticationRoles={"ROLE_ADMIN"},
     *
     *     resourceDescription="user model",
     *     resource=true,
     *     parameters={
     *          {"name"="id", "dataType"="integer", "required"=true,"format"="JSON", "description"="target user id"},
     *          {"name"="user", "dataType"="Object", "required"=true,"format"="JSON", "description"="user :{ id : integer,  username : string, email : string, client : ClientObj, role : string}"}
     *      },
     *     requirements={
     *
     *      },
     *     description="Update user in agence",
     *     section="ADMIN",
     *     statusCodes={
     *         200="OK",
     *         400="Operation failed"
     *         },responseMap={
     *          200 = {"class"=UserModel::class,"groups"={"user"}, "name" = "user","description"="list tout les utilisateurs" },
     *          400 = {"class"=UserModel::class, "form_errors"=true, "name" = ""}
     *           },
     *      headers={
     *         {
     *             "name"="Content-type",
     *              "description"="(Optional) not required in angular 6+"
     *
     *         },
     *         {
     *             "name"="access_token",
     *             "description"="access_token valid ",
     *             "required"=true,
     *
     *         }
     *     },
     *     output={
     *      "section"="user_operations",
     *      "class"="AppBundle\Entity\Models\UserModel",
     *      "description"="User Object ={ username }"
     *      },
     *     tags={
     *          "[ROLE_ADMIN]"="#ffae42"
     *       }
     *
     * )
     * @Rest\View(statusCode=Response::HTTP_OK)
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
        return $this->view($user,Response::HTTP_OK);


    }










/**
 *   liste les utilisateur de la meme agence , Retourne UserModel[].
 * @ApiDoc(
 *     description="list agence users ",
 *     resource=true,
 *     resourceDescription="user list",
 *     section="ADMIN",
 *     statusCodes={
 *         200="OK",
 *         400="Operation echouée"
 *         },responseMap={
 *          200= {"class"=UserModel::class,"collection"=true,"description"="list des utilisateurs sous l'agence du porteur de token"},
 *          400 = {"class"=UserModel::class, "form_errors"=true, "name" = ""}
 *           },
 *     headers={
 *         {
 *             "name"="Content-type",
 *             "description"="(Optional) Application/JSON",
 *
 *         },
 *         {
 *             "name"="access_token",
 *             "description"="access_token valid ",
 *             "required"=true,
 *
 *         }
 *     },
 *     tags={
 *          "[ROLE_ADMIN]"="#ffae42"
 *       }
 *  )
 *@Rest\View(statusCode=Response::HTTP_OK)
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
        return $this->view($users,Response::HTTP_OK);


    }

    /**
     * Supprime un utilisateur de la meme agence .Retourne 200 - OK si Operation reussie.
     * @ApiDoc(
     *     resource=true,
     *
     *     authenticationRoles={"ADMIN"},
     *  description="delete user in same agence ",
     *  requirements={
     *     {
     *          "name"="id",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="ID de l'utilisateur cible"
     *      }
     *
     *  },statusCodes={
     *         200="OK",
     *         400="Operation failed OR user not found",
     *          400=""
     *         },
     *     responseMap={
     *          400 = {"class"=UserModel::class, "form_errors"=true}
     *           },
     *     section="ADMIN",
     *     tags={
     *          "[ROLE_ADMIN]"="#ffae42"
     *       }
     * )
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Delete("/api/admin/user/delete/{id}" )
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