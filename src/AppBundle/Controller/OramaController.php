<?php

namespace AppBundle\Controller;
use AppBundle\Entity\Admin\Client;
use AppBundle\Entity\models\ReclamationModel;
use AppBundle\Entity\models\UserModel;
use AppBundle\Entity\User;
use AppBundle\Service\UserService;
use FOS\RestBundle\Controller\FOSRestController;
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
     * @Rest\Get("/api/super/user/list")
     *
     *
     */
    public function listUserAction()
    {
        $users[] = array();
        $repository  = $this->getDoctrine()->getRepository('AppBundle:User');
        $list = $repository->findAll();


        foreach ($list as $k => $value){
            $user = new UserModel($value);   //reformat fosUser to user model
            $users[$k] = $user;              // ajout à tab $users
        }
        return $this->view($users);


    }

    /**
     * @Rest\Put("/api/super/user/create")
     */
    public function addUserAction(Request $user)
    {

        $content = json_decode($user->getContent());
        $userObject = $content->user;
        $em = $this->getDoctrine()->getManager();
        $userService= new UserService($em);
        //instance de userManager
        $userModel = new UserModel($userObject);
        $newUser = new User();
        if($userService->isUser($userObject,true)){
            if($userService->validNewUser($userObject) === true){
                $newUser->objToUser($userObject);
                $newUser->setClient($em->find('AppBundle:Admin\Client',$userObject->client));
                $em->persist($newUser);
                $em->flush();
                return $this->view((array)$newUser,Response::HTTP_CREATED);
            }else{
                return $this->view((array)$userService->validNewUser($userObject),Response::HTTP_BAD_REQUEST);
            }
        }else{
            return $this->view('data invalid',Response::HTTP_BAD_REQUEST);
        }



/*
 * $userManager = $this->container->get('fos_user.user_manager');
        if ($userObject->password == $userObject->confirm_password) {
            $clientRepo = $this->getDoctrine()->getRepository('AppBundle:Admin\Client');
            $myClient = $clientRepo->findOneBy(['id' => (int)$userObject->client]);

            try {
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
*/
        return $this->view($newUser, Response::HTTP_CREATED);


    }

    /**
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



        /*
        $repository  = $this->getDoctrine()->getRepository('AppBundle:User');

        if(!$user = $repository->find($userObject->id)) {
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

        if ($userObject->role == "ROLE_ADMIN") {
            $user->addRole('ROLE_ADMIN');

        } else {
            $user->setRoles(['ROLE_USER']);
        }
        $user->setRoles([$userObject->role]);
        $userManager->updateUser($user);*/



    }

    /**
     * @Rest\Delete("/api/super/user/delete/{id}")
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
            $newClient->setOffre($sentClient['abonnement'].' mois');
            $newClient->setDescription($sentClient['description']);
            $clientManager->persist($newClient);
            $clientManager->flush();

        }catch (\Doctrine\DBAL\DBALException $exception){
            return $this->view($exception->getMessage(),Response::HTTP_BAD_REQUEST);
        }


        return $this->view($newClient,Response::HTTP_CREATED);


    }

    /**
     * @Rest\Post("/api/super/client/update")
     */
    public function UpdateClientAction(Request $client){
        $updatable = $client->request->get('client');
        $oldClient = $this->getDoctrine()->getRepository('AppBundle:Admin\Client')->findOneBy(['id'=>$updatable['id']]);
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

        return $this->view($oldClient);

    }
    /**
     * @Rest\Delete("/api/super/client/delete/{id}")
     */
    public function DeleteClientAction(Request $request,$id){
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository('AppBundle:Admin\Client')->findOneBy(['id'=>$id]);
        $em->remove($client);
        $em->flush();
        return $this->view(Response::HTTP_OK);
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
