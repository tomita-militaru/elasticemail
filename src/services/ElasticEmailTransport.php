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
    const DOMAIN_HEADER = 'mg:domain';

    /**
     * @var ElasticEmailClient $client
     */
    private $client;

    /**
     * @var string domain
     */
    private $domain;

    /**
     * The event dispatcher from the plugin API.
     *
     * @var \Swift_Events_EventDispatcher eventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param \Swift_Events_EventDispatcher $eventDispatcher
     * @param ElasticEmailClient $client
     * @param $domain
     */
    public function __construct(\Swift_Events_EventDispatcher $eventDispatcher, ElasticEmailClient $client, $domain)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->domain = $domain;
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

        $postData = $this->getPostData($message);
        $domain = $this->getDomain($message);
        $sent = count($postData['to']);
        try {
            $this->client->messages()->sendMime($domain, $postData['to'], $message->toString(), $postData);
            $resultStatus = Swift_Events_SendEvent::RESULT_SUCCESS;
        } catch (\Exception $e) {
            $failedRecipients = $postData['to'];
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
     * Looks at the message headers to find post data.
     *
     * @param Swift_Message $message
     */
    protected function getPostData(Swift_Message $message)
    {
        return $this->prepareRecipients($message);;
    }

    /**
     * @param Swift_Message $message
     *
     * @return array
     */
    protected function prepareRecipients(Swift_Message $message)
    {
        $headerNames = array('from', 'to', 'bcc', 'cc');
        $messageHeaders = $message->getHeaders();
        $postData = array();
        foreach ($headerNames as $name) {
            /** @var \Swift_Mime_Headers_MailboxHeader $h */
            $h = $messageHeaders->get($name);
            $postData[$name] = $h === null ? array() : $h->getAddresses();
        }

        // Merge 'bcc' and 'cc' into 'to'.
        $postData['to'] = array_merge($postData['to'], $postData['bcc'], $postData['cc']);
        unset($postData['bcc']);
        unset($postData['cc']);

        // Remove Bcc to make sure it is hidden
        $messageHeaders->removeAll('bcc');

        return $postData;
    }

    /**
     * If the message header got a domain we should use that instead of $this->domain.
     *
     * @param Swift_Message $message
     *
     * @return string
     */
    protected function getDomain(Swift_Message $message)
    {
        $messageHeaders = $message->getHeaders();
        if ($messageHeaders->has(self::DOMAIN_HEADER)) {
            $domain = $messageHeaders->get(self::DOMAIN_HEADER)->getValue();
            $messageHeaders->removeAll(self::DOMAIN_HEADER);

            return $domain;
        }

        return $this->domain;
    }

    /**
     * Not used.
     */
    public function ping()
    {
        return true;
    }
}
