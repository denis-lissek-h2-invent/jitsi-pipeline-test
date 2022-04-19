<?php

namespace App\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Service\RoomService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

class DirectSendService
{
    private $publisher;
    private $urlgenerator;
    private $parameterBag;
    private $logger;
    private $translator;
    private $roomService;
    private $twig;

    public function __construct(Environment $environment, HubInterface $publisher, RoomService $roomService, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->publisher = $publisher;
        $this->urlgenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->roomService = $roomService;
        $this->twig = $environment;
    }

    public function setMercurePublisher(HubInterface $hub)
    {
        $this->publisher = $hub;
    }

    public function sendSnackbar($topic, $text, $color)
    {
        $data = array(
            'type' => 'snackbar',
            'message' => $text,
            'color' => $color
        );
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);


    }

    public function sendReloadPage($topic, $timeout)
    {
        $data = array(
            'type' => 'reload',
            'timeout' => $timeout,
        );
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);


    }

    public function sendBrowserNotification($topic, $title, $message, $pushMessage, $id, $color)
    {
        $data = array(
            'type' => 'notification',
            'title' => $title,
            'message' => $message,
            'pushNotification' => $pushMessage,
            'messageId' => $id,
            'color' => $color
        );
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendCleanBrowserNotification($topic, $id)
    {
        $data = array(
            'type' => 'cleanNotification',
            'messageId' => $id,
        );
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendModal($topic, $content)
    {

        $data = array(
            'type' => 'modal',
            'content' => $content,

        );
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);

    }

    public function sendRedirect($topic, $url, $timeout = 1000)
    {
        $data = array(
            'type' => 'redirect',
            'url' => $url,
            'timeout' => $timeout,
        );
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendEndMeeting($topic, $url, $timeout = 1000)
    {
        $data = array(
            'type' => 'endMeeting',
            'url' => $url,
            'timeout' => $timeout
        );
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendNewJitsiMeeting($topic, $options)
    {
        $data = array(
            'type' => 'newJitsi',
            'options' => $options,
        );
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendRefresh($topic, $url)
    {
        $data = array(
            'type' => 'refresh',
            'reloadUrl' => $url,
        );
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendCallAdhockmeeding($title, $topic, $message, $pushMesage, $time)
    {
        $data = array(
            'type' => 'call',
            'title'=>$title,
            'message'=>$message,
            'pushMessage'=>$pushMesage,
            'time'=>$time,
            'color'=>'success',
            'id'=>md5(uniqid())
        );
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    private function sendUpdate(Update $update)
    {
        try {
            $res = $this->publisher->publish($update);
            return true;
        } catch (RuntimeException $e) {
            $this->logger->error('Mercure Hub not available: ' . $e->getMessage());
            return false;
        }
    }

}