<?php

namespace workwithtom\elasticemail\services;

use ElasticEmailClient;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use Swift_Transport;

class ElasticEmailTransport implements Swift_Transport
{
    /**
     * @var ElasticEmailClient\ElasticClient $client
     */
    private $client;

    /**
     * The event dispatcher from the plugin API.
     *
     * @var \Swift_Events_EventDispatcher eventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param \Swift_Events_EventDispatcher $eventDispatcher
     * @param ElasticEmailClient\ElasticClient $client
     */
    public function __construct(\Swift_Events_EventDispatcher $eventDispatcher, ElasticEmailClient\ElasticClient $client)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->client = $client;
    }

    /**
     * Not used.
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Not used.
     */
    public function start()
    {
    }

    /**
     * Not used.
     */
    public function stop()
    {
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param Swift_Mime_SimpleMessage $message
     * @param string[]           $failedRecipients An array of failures by-reference
     *
     * @throws \Swift_TransportException
     * 
     * @return int number of mails sent
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $failedRecipients = (array) $failedRecipients;

        if ($evt = $this->eventDispatcher->createSendEvent($this, $message)) {
            $this->eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        if (null === $message->getHeaders()->get('To')) {
            throw new \Swift_TransportException('Cannot send message without a recipient');
        }

        $bodyText = null;
        $bodyHtml = null;
        if ($message->getChildren()[0]->getBodyContentType() === 'text/plain') {
            $bodyText = $message->getChildren()[0]->getBody();
        } else {
            $bodyHtml = $message->getChildren()[0]->getBody();
        }
        $fromArray = $message->getFrom();
        $from = array_key_first($fromArray);
        $fromName = $fromArray[$from];
        $sent = count($message->getTo());
        $to = array_keys($message->getTo());
        try {
            $this->client->Email->Send(
                $message->getSubject(),
                $from,
                $fromName,
                null,
                null,
                null,
                null,
                null,
                null,
                $to,
                array(),
                array(),
                array(),
                array(),
                array(),
                null,
                null,
                null,
                $bodyHtml,
                $bodyText
            );
            $resultStatus = Swift_Events_SendEvent::RESULT_SUCCESS;
        } catch (\Exception $e) {
            $failedRecipients = $to;
            $sent = 0;
            $resultStatus = Swift_Events_SendEvent::RESULT_FAILED;
        }

        if ($evt) {
            $evt->setResult($resultStatus);
            $evt->setFailedRecipients($failedRecipients);
            $this->eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        return $sent;
    }

    /**
     * Register a plugin in the Transport.
     *
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * Not used.
     */
    public function ping()
    {
        return true;
    }
}
