<?php

namespace workwithtom\elasticemail\services;

use ElasticEmail\Api\EmailsApi;
use ElasticEmail\Model\BodyContentType;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use Swift_Transport;

class ElasticEmailTransport implements Swift_Transport
{
    /**
     * @var ElasticEmailClient $client
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
     * @param ElasticEmailClient $client
     */
    public function __construct(\Swift_Events_EventDispatcher $eventDispatcher, EmailsApi $client)
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


        $postData = $this->getPostData($message);
        $sent = count($postData['to']);
        $email_message_data = new \ElasticEmail\Model\EmailTransactionalMessageData([
            "recipients" => new \ElasticEmail\Model\TransactionalRecipient([
                "to" => $postData['to'],
            ]),
            "content" => new \ElasticEmail\Model\EmailContent([
                "body" => [new \ElasticEmail\Model\BodyPart([
                    "content_type" => BodyContentType::HTML,
                    "content" => $message->getBody(),
                ])
                ],
                "from" => $postData['from'][0],
                "subject" => $message->getSubject(),
            ])
        ]);
        try {
            $response = $this->client->emailsTransactionalPost($email_message_data);
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
     * Not used.
     */
    public function ping()
    {
        return true;
    }
}
