<?php

namespace App\Controller;

use PDO;
use App\Entity\User;
use App\Service\PDOService;
use App\Service\UserService;
use App\Service\CarteService;
use App\Service\MessageService;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    #[Route('/user', name: 'app_user')]
    public function index(UserRepository $userRepo, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepo->findOneById(1);
        dd($user);

        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/user/notifications', name: 'app_notifications')]
    public function getNotifications()
    {
        $notification = new NotificationService();

        $table_notif = $this->getUser()->getTablenotification();

        $nbr_nonLu = $notification ->getNotRead($table_notif);

        $response = new StreamedResponse();
        $response->setCallback(function () use (&$nbr_nonLu) {

            echo "data:" . json_encode($nbr_nonLu) .  "\n\n";
            ob_end_flush();
            flush();
        });
        
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Content-Type', 'text/event-stream');
        return $response;

    }
    #[Route('/user/messages', name: 'app_messages')]
    public function getMessages()
    {
        $message = new MessageService();

        $table_msg = $this->getUser()->getTablemessage();

        $nbr_nonLu = $message ->getNotShow($table_msg);

        //dd($nbr_nonLu);

        $response = new StreamedResponse();
        $response->setCallback(function () use (&$nbr_nonLu) {

            echo "data:" . json_encode($nbr_nonLu) .  "\n\n";
            ob_end_flush();
            flush();
        });
        
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Content-Type', 'text/event-stream');
        return $response;

    }

    #[Route('/user/cartes', name: 'app_cartes')]
    public function getCartes()
    {
        $carte = new CarteService();

        $table_carte = $this->getUser()->getTablecarte();

        $nbr_nonLu = $carte ->getNotWait($table_carte);

        //dd($nbr_nonLu);

        $response = new StreamedResponse();
        $response->setCallback(function () use (&$nbr_nonLu) {

            echo "data:" . json_encode($nbr_nonLu) .  "\n\n";
            ob_end_flush();
            flush();
        });
        
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Content-Type', 'text/event-stream');
        return $response;

    }

    #[Route('/user/get_messages', name: 'app_get_messages')]
    public function getAllMessage()
    {
        $message = new MessageService();

        $table_msg = $this->getUser()->getTablemessage();

        dd($table_msg);

        $messages = $message ->getAll($table_msg);
        if(count($messages)>0){
            return $this->json($messages);
        }else{
            return $this->json("Aucun message");
        }

    }

    #[Route('/user/get_messages_by_id/{user_id}', name: 'app_get_messages_by_id')]
    public function getAllMessageById($user_id)
    {
        $message = new MessageService();

        $table_msg = $this->getUser()->getTablemessage();

        $messages = $message ->getMessageById($table_msg,$user_id);

        $response = new StreamedResponse();
        
        //return $response;

        if(count($messages)>0){
            $response->setCallback(function () use (&$messages) {

                echo "data:" . json_encode($messages) .  "\n\n";
                ob_end_flush();
                flush();
            });
            
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Cache-Control', 'no-cache');
            $response->headers->set('Content-Type', 'text/event-stream');
            return $response;
        }else{
            return $this->json("Aucun message");
        }

    }

    #[Route('/user/setIsShow', name: 'app_setIsShow')]
    public function setIsShow()
    {
        $message = new MessageService();

        $table_msg = $this->getUser()->getTablemessage();

        $message ->setIsShow($table_msg);

        return $this->json("Message vu");

    }

    #[Route('/user/setIsRead/{user_id}', name: 'app_setIsRead')]
    public function setIsRead($user_id)
    {
        $message = new MessageService();

        $table_msg = $this->getUser()->getTablemessage();

        $message ->setIsRead($table_msg,$user_id);

        return $this->json("Message lu");

    }

    /**
     * @Route("user/send_message" , name="app_send_message")
     */
    public function sendMessage(Request $request)
    {
        $user = $this->getUser();

        $requestContent = json_decode($request->getContent(), true);

        $user_id = $requestContent["user_id"];
        $content = $requestContent["content"];

        $message = new MessageService();

        $table_msg = $this->getUser()->getTablemessage();

        $other_user = $this->em->getRepository(User::class)->findOneById($user_id);

        $tbl_msg_other_user = $other_user->getTablemessage();

        $message ->sendOneMessage($table_msg, $user_id, $content, 1);

        $message ->sendOneMessage($tbl_msg_other_user, $user->getId(), $content, 0);

        return  $this->json("Message envoyé");
    }
    #[Route('/user/all_messages', name: 'app_messenger')]
    public function getMessageUser(Request $request): Response
    {
        $user = $this->getUser();

        $table_msg = $this->getUser()->getTablemessage();

        $msg_serv = new MessageService();

        $user_list = $this->em->getRepository(User::class)->findAll();

        $last_msg = null;

        $all_message = null;

        $other_user = null;

        if($msg_serv ->getLastMessage($table_msg)){
            $last_msg = $msg_serv ->getLastMessage($table_msg);
            $all_message =$msg_serv->getMessageById($table_msg, $last_msg["user_id"]);
            $other_user = $this->em->getRepository(User::class)->findOneById($last_msg["user_id"]);
        }


        return $this->render('user/message.html.twig', [
            'controller_name' => 'UserController',
            'user'=>$user,
            'user_list' =>$user_list,
            'last_message' =>$last_msg,
            'all_message' => $all_message,
            'other_user' => $other_user,
        ]);
    }

    #[Route('/user/messages_by', name: 'app_messenger_user_id')]
    public function getMessageAll(): Response
    {
        $user = $this->getUser();

        $table_msg = $this->getUser()->getTablemessage();

        $msg_serv = new MessageService();

        //$all_message =$msg_serv->getMessageById($table_msg,$user_id);
        $all_message = $msg_serv->getAll($table_msg);

        $response = new StreamedResponse();
        
        //return $response;

        if(count($all_message)>0){
            $response->setCallback(function () use (&$all_message) {

                echo "data:" . json_encode($all_message) .  "\n\n";
                ob_end_flush();
                flush();
            });
            
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Cache-Control', 'no-cache');
            $response->headers->set('Content-Type', 'text/event-stream');
            return $response;
            //return  $this->json($all_message);
        }else{
            return $this->json("Aucun message");
        }

    }

}
