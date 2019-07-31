<?php

namespace AppBundle\Controller;
use AppBundle\Entity\Admin\Client;
use AppBundle\Entity\models\ReclamationModel;
use AppBundle\Entity\models\UserModel;
use AppBundle\Entity\User;
use AppBundle\Service\UserService;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use \Symfony\Component\HttpFoundation\Response as Response;
use Symfony\Component\Validator\Constraints\Date;


class OramaController extends FOSRestController
{


    /**
     * @Rest\Get("/api/super")
     */
    public function superAction(Request $request)
    {
        return $this->view('controllers index de orama',Response::HTTP_OK);
    }

    /**
     * list TOUT les utilisateurs , Retourne un tableau de UserModel.
     * @Rest\Get("/api/super/user/list")
     *
     * @ApiDoc(
     *     tags={
     *      "operation on : USER"="#003366"
     *     },
     *     resourceDescription="Operations on users.",
     *     input={"class"= "AppBundle\Entity\models\UserModel", "name"=""},
     *     resource=true,
     *     authentication=true,
     *     requirements={
     *
     *      },
     *     description="list all users",
     *     statusCodes={
     *
     *         200="Operation Reussie",
     *         400="( 'access_token' invalid ) OR (no 'access_token' provided)"
     *     },
     *     responseMap={
     *      200= {"class"=UserModel::class,"collection"=true},
     *      400= {"class"=UserModel::class, "form_errors"=true, "name" = ""}
     *
     *     },
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
     *      "collection"=true,
     *      "collectionName"="User service",
     *      "class"="AppBundle\Entity\Models\UserModel",
     *      "description"="User Object ={ username}",
     *
     *      },
     *     section="Role : SUPER_ADMIN"
     * )
     *
     */
    public function listUserAction()
    {
        $users[] = array();
        $repository  = $this->getDoctrine()->getRepository('AppBundle:User');
        $list = $repository->findAll();


        foreach ($list as $k => $value){
            $user = new UserModel($value,false);   //convert fosUser to userModel
            $users[$k] = $user;              // ajout à tab $users
        }
        return $this->view($users);


    }

    /** add new user .ROLE : SUPER ADMIN
     * @ApiDoc(
     *
     *     resourceDescription="Operations on users.",
     *     tags={
     *      "operation on : USER"="#003366"
     *     },
     *     input={"class"= "AppBundle\Entity\models\UserModel", "name"=""},
     *     resource=true,
     *     authentication=true,
     *     parameters={
     *          {"name"="user", "dataType"="Json Object", "required"=true,"format"="JSON", "description"=" user :{ username : string , email : string , role : string , password: string }"}
     *      },
     *     description="add any user",
     *     section="Role : SUPER_ADMIN",
     *     statusCodes={
     *
     *         200="Operation Reussie",
     *         400="( 'access_token' invalid ) OR (no 'access_token' provided)"
     *     },
     *
     *     responseMap={
     *      200= {"class"=UserModel::class,"groups"={"user"}, "name" = "user","description"="utilisateur ajouté"},
     *      400= {"class"=UserModel::class, "form_errors"=true, "name" = ""}
     *
     *     },
     *      headers={
     *         {
     *             "name"="Content-type",
     *              "description"="(Optional) not required in angular 6+",
     *         },
     *         {
     *             "name"="access_token",
     *             "description"="access_token valid ",
     *             "required"=true,
     *         }
     *     },
     *     output={
     *
     *      "collectionName"="User service",
     *      "class"="AppBundle\Entity\Models\UserModel",
     *      "description"="User Object ={ username}",
     *      }
     * )
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Put("/api/super/user/create")
     */
    public function addUserAction(Request $user)
    {

        $content = json_decode($user->getContent());
        $userObject = $content->user;                      //recuperer l'object de la requete
        $em = $this->getDoctrine()->getManager();
        $userService= new UserService($em);
        $userModel = new UserModel($userObject,false);//instance de userManager
        $newUser = new User();
        if($userService->isUser($userObject,true)){       // si l'objet reçu contient les champs requis
            if($userService->validNewUser($userObject) === true){  // si les valeur sont valide
                $newUser->objToUser($userObject);                  //hydratation de l'obj ORM
                $newUser->setClient($em->find('AppBundle:Admin\Client',$userObject->client));   //affectation du client au nouveau utilisateur
                try{
                    $em->persist($newUser);
                    $em->flush();
                }catch(\Doctrine\DBAL\DBALException  $exception) {   //catch data_base error
                    $error=[
                        'message' => 'username/e-mail existant',     //reformuler l'erreur
                        'error_desc' => $exception->getMessage()     //   ,,         ,,
                    ];
                    return $this->view($error, Response::HTTP_BAD_REQUEST);
                }

                return $this->view((array)$newUser,Response::HTTP_CREATED);
            }else{
                return $this->view((array)$userService->validNewUser($userObject),Response::HTTP_BAD_REQUEST);
            }
        }else{
            return $this->view('data invalid',Response::HTTP_BAD_REQUEST);
        }

    }

    /**
     * Mais a jour un utilisateur  , Retourne l'utilisateur apres mise a jour ,  type de retour : UserModel
     * @ApiDoc(
     *     authenticationRoles={"Role_ADMIN"},
     *     resourceDescription="user model",
     *     tags={
     *      "operation on : USER"="#003366"
     *     },
     *     authentication=true,
     *     resource=true,
     *     parameters={
     *          {"name"="user", "dataType"="Object", "required"=true,"format"="JSON", "description"="user :{id : integer , username : string, email : string, client : ClientObj, role : string , password : string}"}
     *      },
     *     description="Update any user ",
     *     section="Role : SUPER_ADMIN",
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
     *      "section"="Role : SUPER_ADMIN",
     *      "class"="AppBundle\Entity\Models\UserModel.php",
     *      "description"="User Object ={ username }"
     *      }
     *
     * )
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Post("/api/super/user/update")
     *
     */

    public function UpdateUserAction(Request $user)
    {
        $content = json_decode($user->getContent());
        $userObject = $content->user;
        $em = $this->getDoctrine()->getManager(); //

        $userService = new UserService($em);
        if($userService->validUserUpdate($userObject) === true){
            if($newUser = $em->getRepository('AppBundle:User')->find($userObject->id)){
                $newUser->setUsername($userObject->username);
                $newUser->setUsernameCanonical($userObject->username);

                $newUser->setEmail($userObject->email);
                $newUser->setEmailCanonical($userObject->email);

                $newUser->setPlainPassword($userObject->password);
                $newUser->setClient($this->getDoctrine()->getManager()->find('AppBundle:Admin\Client',$userObject->client));
                $this->getDoctrine()->getManager()->persist($newUser);
                $this->getDoctrine()->getManager()->flush();
                return $this->view((array)$newUser,Response::HTTP_OK);
            }
        }else{
            $response = $userService->validUserUpdate($userObject);
            if($response == false){
                return $this->view($userService->validUserUpdate($userObject),Response::HTTP_BAD_REQUEST);
            }
            return $this->view($response,Response::HTTP_BAD_REQUEST);
        }





    }

    /**
     * @Rest\Delete("/api/super/user/delete/{id}")
     *
     */
    public function DeleteUserAction(Request $request,$id){
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('AppBundle:User')->findOneBy(['id'=>$id]);
        if(!$user){
            return $this->view('utilisateurs inexistant',Response::HTTP_BAD_REQUEST);
        }
        $em->remove($user);
        $em->flush();
        return $this->view('utilisateur supprimé ',Response::HTTP_OK);
    }


    /*
  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * -ClientList :  lister tout les clients dans la BD.
  *                                                       * -AddClient:    Ajout est init un Client a la BD.
  *                Client  CONTROLLERS                    * -ClientUser:   Lister tout les users appartenant a un Client precis.
  *                                                       * -UpdateClient: Mettre a jour un client.
  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * -DeleteClient: Supprimer le client ET tout ses utilisateurs
  */


    /**
     * @Rest\Get("/api/super/client/list")
     */
    public function ClientListAction(Request $request){
        $repo = $this->getDoctrine()->getRepository('AppBundle:Admin\Client');
        $clientList = $repo->findAll();
        return $this->view($clientList,Response::HTTP_OK);
    }

    /**
     * * Ajoute un client
     * * Retourne l'objet ajouté
     * @ApiDoc(
     *
     *     resourceDescription="Operations on users.",
     *     input={"class"= "AppBundle\Entity\Client", "name"=""},
     *     resource=true,
     *     parameters={
     *          {"name"="client", "dataType"="Json Object", "required"=true,"format"="JSON", "description"=" user :{ username : string , email : string , role : string , password: string }"}
     *      },
     *     requirements={
     *
     *      },
     *     description="add new Client ",
     *     section="Role : SUPER_ADMIN",
     *     statusCodes={
     *
     *         200="Operation Reussie",
     *         400="( 'access_token' invalid ) OR (no 'access_token' provided)"
     *     },
     *     responseMap={
     *      200= {"class"=Client::class,"groups"={"user"},"description"="Operation Reussie"},
     *      400= {"class"=Client::class, "form_errors"=true,}
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
     *      "class"="AppBundle\Entity\Client"
     *      },
     *     tags={
     *          "operation on : Client"="#065535"
     *       },
     *     authentication=true
     * )
     *@Rest\View(statusCode=Response::HTTP_CREATED)
     *
     * @Rest\Put("/api/super/client/create")
     */
    public function AddClientAction(Request $client)
    {



        $clientManager = $this->getDoctrine()->getManager();


        $newClient= new \AppBundle\Entity\Admin\Client();

        $sentClient = $client->request->get('client');

        try{
            if(!strlen($sentClient['name'])){
                return $this->view('nom manquant',Response::HTTP_BAD_REQUEST);
            }

           // to validnewclient fn
            $newClient->setName($sentClient['name']);
            $newClient->setTel($sentClient['tel']);
            if(!$this->verifEmail($sentClient['email'])){
                return $this->view('email invalid',Response::HTTP_BAD_REQUEST);
            }if (\DateTime::createFromFormat('Y-m-d', $sentClient['date_depart']) == FALSE ||
                \DateTime::createFromFormat('Y/m/d', $sentClient['date_exp']) == FALSE) {
                return $this->view('date invalid',Response::HTTP_BAD_REQUEST);
            }
            $newClient->setEmail($sentClient['email']);
            $newClient->setDateExp($sentClient['date_exp']);
            $newClient->setDateInscri($sentClient['date_depart']);
            $newClient->setOffre($sentClient['offre'].' mois');
            $newClient->setDescription($sentClient['description']);
            $clientManager->persist($newClient);
            $clientManager->flush();
            // end validnewclient
        }catch (\Doctrine\DBAL\DBALException $exception){
            return $this->view($exception->getMessage(),Response::HTTP_BAD_REQUEST);
        }


        return $this->view($newClient,Response::HTTP_CREATED);


    }

    /**
     *
     * - Mais a jour  un client
     * - Retourne l'objet apres mise a jour
     * @ApiDoc(
     *
     *     resourceDescription="user model",
     *     resource=true,
     *     parameters={
     *          {"name"="client", "dataType"="Object", "required"=true,"format"="JSON", "description"="user :{ - id : integer , date_exp : date , offre : string , email : string, tel: string , description: string }"}
     *      },
     *
     *     description="Update client ",
     *     section="Role : SUPER_ADMIN",
     *     statusCodes={
     *         200="mise a jour effectué",
     *         400="Operation failed",
     *         },responseMap={
     *          200 = {"class"=Client::class,"description"="l'objet apres mis a jour" },
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
     *
     *      "class"="AppBundle\Entity\Client",
     *      "description"="client Object ={ username }"
     *      },
     *     tags={
     *         "operation on : Client "="#065535"
     *       },
     *     authentication=true
     *
     * )
     * @Rest\View(statusCode=Response::HTTP_OK)
     *
     * @Rest\Post("/api/super/client/update")
     */
    public function UpdateClientAction(Request $client){
        $updatable = $client->request->get('client');
        $oldClient = $this->getDoctrine()->getRepository('AppBundle:Admin\Client')->findOneBy(['id'=>$updatable['id']]);



        $errors = array();
        if(!$oldClient){
            $errors[0]="client not found";
            return $this->view($errors,Response::HTTP_BAD_REQUEST);
        }
        if(!isset($updatable['name'])){
            $errors[0]=" invalid client object";
            return $this->view($errors,Response::HTTP_BAD_REQUEST);
        }
        if(strlen($updatable['name'])){
            $oldClient->setName($updatable['name']);
        }
        if($this->verifEmail($updatable['email'])){
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

        return $this->view($oldClient,Response::HTTP_OK);

    }
    /**
     *  * Supprime un client .Retourne 200 - OK si Operation reussie.
     * @ApiDoc(
     *     resource=true,
     *     authenticationRoles={"ADMIN"},
     *     description="delete client ",
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
     *         400="Operation failed"
     *         },
     *     responseMap={
     *          200 = {"class"=Client::class,"description"="l'objet apres mis a jour" },
     *          400 = {"class"=UserModel::class, "form_errors"=true}
     *           },
     *     section="Role : SUPER_ADMIN",
     *     tags={
     *          "operation on : Client "="#065535"
     *       },
     *     output={
     *          "class"="AppBundle\Entity\Client",
     *     },
     *     authentication=true,
     * )
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Delete("/api/super/client/delete/{id}")
     */
    public function DeleteClientAction($id){
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository('AppBundle:Admin\Client')->findOneBy(['id'=>$id]);
        if($client){
            $em->remove($client);
            $em->flush();
            return $this->view(Response::HTTP_OK);
        }else{
            return $this->view($error='client '.$id.' not found ',Response::HTTP_BAD_REQUEST);
        }


    }





    /**
     * @Rest\Post("/api/super/reclamation/check")
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
     * @Rest\Get("/api/super/reclamation/list")
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
     * @Rest\Delete("/api/super/reclamation/delete/{id}")
     */
    public function DeleteReclamationAction(Request $request,$id){
        $em = $this->getDoctrine()->getManager();
        $reclamation = $em->getRepository('AppBundle:Reclamation')->findOneBy(['id'=>$id]);
        $em->remove($reclamation);
        $em->flush();
        return $this->view(Response::HTTP_OK);
    }


    // Generation de mot de passe aleatoire
    function generateRandomPassword($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
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
