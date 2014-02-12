<?php

namespace pallo\library\mail\transport;

use pallo\library\log\Log;
use pallo\library\mail\exception\MailException;
use pallo\library\mail\MandrillMailMessage;
use pallo\library\mail\MailMessage;

use \Exception;
use \Mandrill_Messages;
use \Mandrill;

/**
 * Mandrill message transport
 * @see http://mandrill.com
 */
class MandrillTransport extends AbstractTransport {

    /**
     * Instance of the Mandrill library
     * @var Mandrill
     */
    protected $mandrill;

    /**
     * Default tags for the mail
     * @var array
     */
    protected $tags;

    /**
     * Id of the Mandrill subaccount
     * @var string
    */
    protected $subaccount;

    /**
     * Errors of the last send
     * @var array
     */
    protected $errors;

    /**
     * Constructs a new message transport
     * @return null
     */
    public function __construct($apiKey, Log $log = null, $lineBreak = null) {
        $this->mandrill = new Mandrill($apiKey);
        $this->tags = array();
        $this->subaccount = null;

        parent::__construct($log, $lineBreak);
    }

    /**
     * Creates a mail message
     * @return pallo\library\mail\MailMessage
     */
    public function createMessage() {
        return new MandrillMailMessage();
    }

    /**
     * Deliver a mail message to the Mandrill API
     * @param pallo\library\mail\MailMessage $message Message to send
     * @return null
     * @throws pallo\library\mail\exception\MailException when the message could
     * not be delivered
     */
    public function send(MailMessage $message) {
        try {
            $struct = array(
                'subject' => $message->getSubject(),
                'headers' => array(),
            );

            // set sender
            $from = $message->getFrom();
            if (!$from && $this->defaultFrom) {
                $from = new MailAddress($this->defaultFrom);
            }

            if ($from) {
                $struct['from_email'] = $from->getEmailAddress();
                if ($from->getDisplayName()) {
                    $struct['from_name'] = $from->getDisplayName();
                }
            }

            // set recipient
            if ($this->debugTo) {
                $struct['to'] = array(
                    array('email' => $this->debugTo),
                );
            } else {
                $to = $message->getTo();
                if ($to) {
                    $struct['to'] = $this->getAddresses($to);
                }

                $cc = $message->getCc();
                if ($cc) {
                    $struct['headers']['Cc'] = implode(', ', $cc);
                }

                $bcc = $message->getBcc();
                if ($bcc) {
                    $struct['headers']['Bcc'] = implode(', ', $bcc);
                }
            }

            $replyTo = $message->getReplyTo();
            if ($replyTo) {
                $struct['headers']['Reply-To'] = (string) $replyTo;
            }

            $returnPath = $message->getReturnPath();
            if ($returnPath) {
                $struct['headers']['Reply-Path'] = (string) $returnPath;
            }

            // set body
            $struct['auto_text'] = false;
            $struct['auto_html'] = false;
            if ($message->isHtmlMessage()) {
                $struct['html'] = $message->getMessage();
                $struct['text'] = $message->getPart(MailMessage::PART_ALTERNATIVE);
            } else {
                $struct['text'] = $message->getMessage();
            }

            // add attachments
            $parts = $message->getParts();
            foreach ($parts as $name => $part) {
                if ($name == MailMessage::PART_BODY || $name == MailMessage::PART_ALTERNATIVE) {
                    continue;
                }

                if (!isset($struct['attachments'])) {
                    $struct['attachments'] = array();
                }

                $struct['attachments'][] = array(
                    'name' => $name,
                    'type' => $part->getMimeType(),
                    'content' => $part->getBody(),
                );
            }

            $tags = array();
            $subaccount = null;

            if ($message instanceof MandrillMailMessage) {
                $tags = $message->getTags();
                $subaccount = $message->getSubaccount();
            }

            if ($this->tags) {
                foreach ($this->tags as $tag => $null) {
                    if (!in_array($tag, $tags)) {
                        $tags[] = $tag;
                    }
                }
            }

            if (!$subaccount && $this->subaccount) {
                $subaccount = $this->subaccount;
            }

            if ($tags) {
                $struct['tags'] = $tags;
            }

            if ($subaccount) {
                $struct['subaccount'] = $subaccount;
            }

            // send the mail
            $mailer = new Mandrill_Messages($this->mandrill);
            $result = $mailer->send($struct);

            $this->errors = array();
            foreach ($result as $recipientResult) {
                if ($recipientResult['status'] == 'rejected') {
                    $this->errors[$recipientResult['email']] = 'Rejected: ' . $recipientResult['reject_reason'];
                } elseif ($recipientResult['status'] == 'invalid') {
                    $this->errors[$recipientResult['email']] = 'Invalid';
                }
            }

            // log the mail
            if (isset($struct['text'])) {
                unset($struct['text']);
            }
            if (isset($struct['body'])) {
                unset($struct['body']);
            }
            if (isset($struct['attachments'])) {
                unset($struct['attachments']);
            }

            $this->logMail($struct['subject'], var_export($struct, true), !count($this->errors));
        } catch (Exception $exception) {
            throw new MailException('Could not send the mail', 0, $exception);
        }
    }

    /**
     * Gets the errors of the last send action
     * @return array Email address of the recipient as key, error as value
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Gets the addresses in Mandrill format
     * @param MailAddress|array $addresses Array with MailAddress instances
     * @return array Array with a address struct
     */
    protected function getAddresses($addresses) {
        if (!is_array($addresses)) {
            $addresses = array($addresses);
        }

        $result = array();

        foreach ($addresses as $address) {
            $r = array(
                'email' => $address->getEmailAddress(),
            );

            if ($address->getDisplayName()) {
                $r['name'] = $address->getDisplayName();
            }

            $result[] = $r;
        }

        return $result;
    }

    /**
     * Adds a tag
     * @param string $tag
     * @return null
     */
    public function addTag($tag) {
        $this->tags[$tag] = true;
    }

    /**
     * Removes a tag
     * @param string $tag
     * @return boolean True when the tag has been removed, false when it was
     * not set
     */
    public function removeTag($tag) {
        if (isset($this->tags[$tag])) {
            unset($this->tags[$tag]);

            return true;
        }

        return false;
    }

    /**
     * Gets the tags
     * @return array
     */
    public function getTags() {
        return array_keys($this->tags);
    }

    /**
     * Sets the id of the Mandrill subaccount
     * @param string $subaccount
     * @return null
     */
    public function setSubaccount($subaccount) {
        $this->subaccount = $subaccount;
    }

    /**
     * Gets the id of the Mandrill subaccount
     * @return string|null
     */
    public function getSubaccount() {
        return $this->subaccount;
    }

}