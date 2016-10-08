<?php
/**
 * @package Actor
 */ 
/**
 * @package Actor
 * @subpackage Live
 */
class Actor_Live_Inviter extends Actor_Live
{
    const SEND_INVITATION_URL   = '/connect/connectionservice.fpp';
    const ACCEPT_INVITATION_URL = '/messages/messagesservice.fpp';


    /** * Fetches contacts list
     *
     * @return string|false
     */
    protected function _fetch_contacts_list()
    {
        $this->log('Fetching contacts list');

        $this->get(self::HOME_HOST);
        $this->_dump('homepage.html');
        if (!preg_match(
            '#<a href="([^"]+)"[^>]*>People</a>#',
            $this->_response,
            $url
        )) {
            throw new Actor_Live_Exception(
                'Contacts list landing URL not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $url = html_entity_decode($url[1], ENT_QUOTES);
            $this->log("Go to {$url}",
                       Log_Abstract::LEVEL_DEBUG);
        }

        $this->get($url);
        $this->_dump('contacts.landing.html');
        if (!preg_match(
            '#<iframe[^>]+src="([^"]+)"#',
            $this->_response,
            $url
        )) {
            throw new Actor_Live_Exception(
                'Contacts list URL not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $url = html_entity_decode($url[1], ENT_QUOTES);
            $this->log("Go to {$url}",
                       Log_Abstract::LEVEL_DEBUG);
            $this->_connection->set_cookie(array(
                'afu' => urlencode($url),
                'UIC' => urlencode($this->_connection->get_cookie('UIC')),
            ), null, '.live.com');
        }

        $this->get($url);
        $this->_dump('contacts.html');
        return $this->_response;
    }

    /**
     * Parses out specific button's URL
     *
     * @param string $title Button title
     * @param string $src   Optional source
     * @return string|false
     */
    protected function _get_contact_list_button($title, $src=null)
    {
        $this->log("Extracting {$title} button URL");

        if (preg_match(
            '#<a[^>]+href="([^"]+)" title="' . str_replace(
                ' ',
                '&\#32;',
                preg_quote(htmlentities($title, ENT_QUOTES))
            ) . '"#i',
            ((null !== $src)
                ? $src
                : $this->_response),
            $url
        )) {
            return html_entity_decode($url[1], ENT_QUOTES);
        }

        $this->log("{$title} button not found",
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }


    /**
     * Fetches the list of contacts who accepted the user invites
     *
     * @return array|false
     */
    public function get_contacts_list()
    {
        $this->log('Fetching the contacts list');

        if (!$this->_fetch_contacts_list()) {
            return false;
        }

        $contacts = array();
        if (preg_match_all(
            '#,\'([^\']+\\x40[^\']+)\',#',
            $this->_response,
            $m
        )) {
            foreach ($m[1] as $s) {
                $s = json_decode($s, true);
                if ($s) {
                    $contacts[] = array(
                        'name'    => $s[3],
                        'user_id' => $s[6]
                    );
                }
            }
            $this->log('Contacts: ' . serialize($contacts),
                       Log_Abstract::LEVEL_DEBUG);
        }
        return count($contacts)
            ? $contacts
            : false;
    }

    /**
     * Accepts all pending invitations
     *
     * @return bool
     */
    public function accept()
    {
        $this->log('Accepting all pending invitations');

        $profile_host = $this->_get_profile_host();

        if (!$this->_fetch_contacts_list()) {
            return false;
        }

        $url = $this->_get_contact_list_button('View invitations');
        if (!$url) {
            return false;
        }

        $invitations = array();
        $this->get($url);
        $this->_dump('contacts.invitations.html');
        foreach (array('cid', 'name') as $k) {
            if (!preg_match_all(
                '#\s' . $k . ':"([^"]+)",\s#',
                $this->_response,
                $m
            )) {
                $this->log("Key {$k} not found",
                           Log_Abstract::LEVEL_ERROR);
            } else {
                foreach ($m[1] as $i => $v) {
                    $invitations[$i][$k] = $v;
                }
            }
        }
        $invitations = array_unique($invitations);
        if (!count($invitations)) {
            $this->log('No invitations found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $accepted = array();
        foreach ($invitations as $i => $invitation) {
            $this->ajax($profile_host . self::ACCEPT_INVITATION_URL, array(
                'cn' => 'Microsoft.Spaces.Profile.Messages.MessagesService',
                'mn' => 'replynetworkrequest',
                'd'  => '0,0,"' . $invitation['cid'] . '","' . $invitation['name'] . '",true,true,""',
                'v'  => 2,
            ), array(
                'cnmn' => 'Microsoft.Spaces.Profile.Messages.MessagesService.replynetworkrequest',
                'ptid' => 0,
                'a'    => '',
                'au'   => 'undefined',
            ));
            $this->_dump("contacts.invitations.accept.{$i}.{$invitation['cid']}.submit.js");
            if (false !== strpos($this->_response, 'in your network')) {
                $accepted[] = $invitation['name'];
            }
        }
        if (count($accepted)) {
            return $accepted;
        }

        $this->log('Failed accepting invitations',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /** * Adds a contact sending out an invitation
     *
     * @param string $user_id Contact's user ID (e-mail)
     * @return bool
     */
    public function invite($user_id)
    {
        $this->log("Sending an invitation to {$user_id}");

        $user_id = strtolower($user_id);
        $profile_host = $this->_get_profile_host();

        if (!$this->_fetch_contacts_list()) {
            return false;
        }

        if (false !== strpos(
            $this->_response,
            str_replace('@', '\x40', $user_id)
        )) {
            $this->log('Contact already added',
                       Log_Abstract::LEVEL_ERROR);
            return true;  // Count as successfully added
        }

        $url = $this->_get_contact_list_button('Add people');
        if (!$url) {
            return false;
        }

        $this->get($url);
        $this->_dump('contacts.add.html');
        if (!$this->get_form('id', 'add_by_email')) {
            throw new Actor_Live_Exception(
                'Add contact form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['cpselectedInviteEmails'] =
            '[;;' . urlencode($user_id) . ';false;false;false;false]';
        $this->_form['x'] = 23;
        $this->_form['y'] = 13;
        $this->submit();
        $this->_dump('contacts.add.confirm.html');
        if (!$tmp_form = $this->get_form('name', 'InviteResults')) {
            throw new Actor_Live_Exception(
                'Invite results form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $query = array();
        foreach (array('DN', 'FN', 'LN', 'E', 'SD', 'C') as $k) {
            if (!preg_match(
                '#\s' . preg_quote($k) . ':["\'](.*?)["\'](?:,\s|})#',
                $this->_response,
                $m
            )) {
                $this->log("Key {$k} not found",
                           Log_Abstract::LEVEL_ERROR);
                return false;
            }
            $query[$k] = addcslashes(stripcslashes($m[1]), ':');
        }

        $this->ajax($profile_host . self::SEND_INVITATION_URL, array(
            'cn' => 'Microsoft.Spaces.Connections.ConnectionService',
            'mn' => 'sendinvitebatch',
            'd'  =>
                '[{"' . $query['C'] . '",null,"' . $query['E'] .
                '","' . $query['DN'] . '","' . $query['FN'] .
                '","' . $query['LN'] . '",null,null,"' .
                $query['SD'] . '"}],{"",true,true,[]},false,null',
            'v'  => 2,
        ), array(
            'cnmn' => 'Microsoft.Spaces.Connections.ConnectionService.sendinvitebatch',
            'ptid' => 0,
            'a'    => '',
            'au'   => 'undefined',
        ));
        $this->_dump('contacts.add.confirm.submit.js');
        if (false !== strpos($this->_response, 'InviteResult')) {
            return true;
        }

        $this->log('Failed sending invitation',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
