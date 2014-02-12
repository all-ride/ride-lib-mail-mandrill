<?php

namespace pallo\library\mail;

use pallo\library\mail\exception\MailException;

/**
 * A Mandrill email message
 */
class MandrillMailMessage extends MailMessage {

    /**
     * Mandrill tags for the mail
     * @var array
     */
    private $tags = array();

    /**
     * Id of the Mandrill subaccount
     * @var unknown
     */
    private $subaccount;

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