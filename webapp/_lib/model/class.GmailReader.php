<?php
/**
 * A class to access various information on a Gmail account. Adapted from the code example at
 * http://www.kerkness.ca/gmailreaderphp-a-php-imap-class-for-reading-your-gmail-account/
 *
 * @author Sam Rose <samwho@lbak.co.uk>
 */
class GmailReader {

    /**
     * The handle to the mailbox as returned from imap_open
     *
     * @var resource
     */
    private $mbox;

    /**
     * The Gmail IMAP connection reference.
     *
     * @var str
     */
    private $ref = '{imap.gmail.com:993/imap/ssl}';

    /**
     * The last error that happened in this class.
     *
     * @var str
     */
    private $last_error = null;

    const ALL_MAIL = "[Gmail]/All Mail";
    const INBOX = "INBOX";
    const SENT_MAIL = "[Gmail]/Sent Mail";
    const BIN = "[Gmail]/Bin";
    const SPAM = "[Gmail]/Spam";
    const STARRED = "[Gmail]/Starred";
    const IMPORTANT = "[Gmail]/Important";
    const DRAFTS = "[Gmail]/Drafts";

    /**
     * The constructor opens the initial connection to the Gmail account.
     *
     * @param str $user Username for gmail, e.g. me@gmail.com
     * @param str $pass Password for your Gmail account.
     */
    function GmailReader($user, $pass) {
        if (self::checkIMAP()) {
            $this->mbox = imap_open($this->ref . "INBOX", $user, $pass);

            if ($this->mbox == false) {
                $this->last_error = imap_last_error();
            }
        } else {
            $this->last_error = 'You do not have the IMAP extension loaded on your server. 
Please contact your hosting provider to resolve this issue.';
        }
    }

    /**
     * Check if the IMAP extension exists in your installation of PHP.
     *
     * @return bool Returns true of IMAP is loaded, false otherwise.
     */
    public static function checkIMAP() {
        return extension_loaded('imap');
    }

    /**
     * Opens a specific "mailbox" in your Gmail account. You can
     * use label names that you have set in your Gmail account as
     * a parameter.
     *
     * @param str $mailbox The mailbox to open.
     */
    function openMailBox($mailbox) {
        if (!imap_reopen($this->mbox, $this->ref . $mailbox)) {
            $this->last_error = imap_last_error();
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns an array of information about the current mailbox.
     *
     * @return array Mailbox info.
     *
     * Date - current system time formatted according to » RFC2822
     * Driver - protocol used to access this mailbox: POP3, IMAP, NNTP
     * Mailbox - the mailbox name
     * Nmsgs - number of messages in the mailbox
     * Recent - number of recent messages in the mailbox
     */
    function getMailboxInfo() {
        $check = imap_check($this->mbox);
        if (!$check) {
            $this->last_error = imap_last_error();
        }
        return $check;
    }

    /**
     * This method returns a list of the available mailboxes on the current connection.
     *
     * If the $detailed parameter is set to true, this function will return a numerical
     * indexarray of stdClass objects.
     *
     * Reference:
     * http://www.php.net/manual/en/function.imap-getmailboxes.php
     * http://www.php.net/manual/en/function.imap-list.php
     *
     * @param bool $sanitize The mailboxes get returned with the ref string appended
     * to the front of them. Set this to true if you want to remove this, false if you want
     * to keep it. Defaults to true.
     * @param bool $detailed Specify whether to retrieve detailed information on the mailboxes
     * or not. Defaults to false.
     * @param str $pattern The pattern to search for. Defaults to "*".
     * @return array An array of available mailboxes on this connection.
     */
    public function listMailboxes($pattern = "*", $sanitize = true, $detailed = false) {
        if ($detailed) {
            $mailboxes = imap_getmailboxes($this->mbox, $this->ref, $pattern);
        } else {
            $mailboxes = imap_list($this->mbox, $this->ref, $pattern);
        }

        if ($detailed) {
            if ($sanitize) {
                foreach ($mailboxes as $value) {
                    $value->name = str_replace($this->ref, '', $value->name);
                }
            }
        } else {
            if ($sanitize) {
                foreach ($mailboxes as $key=>$value) {
                    $mailboxes[$key] = str_replace($this->ref, '', $value);
                }
            }
        }
        return $mailboxes;
    }

    /**
     * $date should be a string
     * Example Formats Include:
     * Fri, 5 Sep 2008 9:00:00
     * Fri, 5 Sep 2008
     * 5 Sep 2008
     * I am sure other's work, just test them out.
     */
    function getHeadersSince($date) {
        $uids = $this->getMessageIdsSinceDate($date);
        $messages = array();
        foreach ($uids as $k => $uid) {
            $messages[] = $this->retrieve_header($uid);
        }
        return $messages;
    }

    /**
     * $date should be a string
     * Example Formats Include:
     * Fri, 5 Sep 2008 9:00:00
     * Fri, 5 Sep 2008
     * 5 Sep 2008
     * I am sure other's work, just test them out.
     */
    function getEmailSince($date) {
        $uids = $this->getMessageIdsSinceDate($date);
        $messages = array();
        foreach ($uids as $k => $uid) {
            $messages[] = $this->retrieve_message($uid);
        }
        return $messages;
    }

    public function getAllEmail() {
        return $this->fetchMessages('ALL');
    }

    public function getLastError() {
        return $this->last_error;
    }

    /**
     * Fetch email messages from the currently selected mailbox.
     *
     * @param str $search A search string. For more details on this, visit:
     * http://www.php.net/manual/en/function.imap-search.php
     * @return array Retrieved messages.
     */
    public function fetchMessages($search) {
        $uids = imap_search($this->mbox, $search);
        $messages = array();
        foreach ($uids as $k => $uid) {
            $messages[] = $this->retrieve_message($uid);
        }
        return $messages;
    }

    /**
     * This function takes a message ID and fetches that email from the current mailbox.
     *
     * @param int $messageid The message ID of the email to fetch.
     * @param bool $construct_email_addresses If this is set to true, it takes the object based emails
     * and constructs them in to strings. Defaults to true.
     * @param bool $strip_quotes If set to true, this strips out email quotes from the
     * retrieved body text. Defaults to false.
     * @return array An associative array containing email information.
     */
    private function retrieve_message($messageid, $construct_email_addresses = true, $strip_quotes = false) {
        $message = array();

        $header = imap_header($this->mbox, $messageid);
        $structure = imap_fetchstructure($this->mbox, $messageid);
        print_r($header);

        $message['message_id'] = $header->message_id;
        $message['subject'] = $header->subject;
        $message['from'] = $this->constructEmailAddress($header->from[0]);
        $message['reply_to'] = $header->reply_toaddress;
        $message['sender'] = $header->senderaddress;
        $message['to'] = $header->toaddress;
        $message['cc'] = $header->ccaddress;
        $message['date'] = $header->date;
        $message['udate'] = $header->udate;
        $message['size'] = $header->size;
        $message['starred'] = $header->Flagged == 'F' ? true : false;

        if ($this->check_type($structure)) {
            $message['body'] = imap_fetchbody($this->mbox, $messageid, "1"); ## GET THE BODY OF MULTI-PART MESSAGE
            if (!$message['body']) {
                $message['body'] = '';
            }
        } else {
            $message['body'] = imap_body($this->mbox, $messageid);
            if (!$message['body']) {
                $message['body'] = '';
            }
        }

        if ($strip_quotes) {
            $message['body'] = preg_replace('/>(.+?)\n/', '', $message['body']);
        }

        return $message;
    }
    
    /**
     * When an email header is retrieved, the various emails comes back
     * in this format:
     * 
     * [from] => Array
     *  (
     *      [0] => stdClass Object
     *          (
     *              [personal] => Gina Trapani
     *              [mailbox] => ginatrapani
     *              [host] => gmail.com
     *          )
     *  )
     * 
     * This function turns that object into this:
     * 
     * "Gina Trapani" <ginatrapani@gmail.com>
     * 
     * @param stdClass $email_object An email object from an email header.
     * @return string Full email address including name.
     */
    private function constructEmailAddress($email_object) {
        if (is_object($email_object)) {
            $return = '';
            if (!empty($email_object->personal)) {
                $return .= '"' . $email_object->personal . '" ';
            }

            $return .= '<' . $email_object->mailbox . '@' . $email_object->host . '>';

            return $return;
        } else {
            return '';
        }
    }

    private function check_type($structure) { ## CHECK THE TYPE
        if ($structure->type == 1) {
            return(true); ## YES THIS IS A MULTI-PART MESSAGE
        } else {
            return(false); ## NO THIS IS NOT A MULTI-PART MESSAGE
        }
    }

}

