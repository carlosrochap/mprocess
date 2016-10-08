<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage YouTube
 */
class Actor_YouTube_Uploader extends Actor_YouTube
{
    const UPLOAD_FORM_URL    = 'http://upload.youtube.com/my_videos_upload';
    const UPLOAD_REQUEST_URL = 'http://upload.youtube.com/upload/rupio';

    const VIDEO_EDIT_URL = '/my_videos_edit';


    /**
     * Updates video details, currently -- sets title & description
     *
     * @param string $video_id
     * @param string $title
     * @param string $description
     * @param int    $category
     * @return bool
     */
    public function update_video_details($video_id, $title, $description=null, $category=null)
    {
        $this->log("Updating video {$video_id} details");

        $this->get(self::HOST . self::VIDEO_EDIT_URL, array(
            'ns'       => 1,
            'video_id' => &$video_id
        ));
        $this->_dump("edit.{$video_id}.html");
        if (!$this->get_form('id', 'video-details-form')) {
            throw new Actor_YouTube_Exception(
                'Video details editor form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        if (!preg_match(
            "#'XSRF_TOKEN': '([^']+)'#",
            $this->_response,
            $m
        )) {
            throw new Actor_YouTube_Exception(
                'Session token not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $this->_form['session_token'] = $m[1];
        }

        $this->_form['field_myvideo_title'] = $title;
        $this->_form['field_myvideo_keywords'] =
            preg_match_all('#\W(\w{5,})\W#', strtolower($title), $m)
                ? implode(' ', $m[1])
                : '';
        if (null !== $description) {
            $this->_form['field_myvideo_descr'] = $description;
        }
        $this->_form['field_myvideo_categories'] = (null !== $category)
            ? (int)$category
            : rand(22, 24);
        $this->_dump("edit.{$video_id}.submit.txt", $this->_form->to_array());
        $this->submit();
        $this->_dump("edit.{$video_id}.submit.html");
        if (false !== strpos($this->_response, 'video-details-form')) {
            return true;
        }

        $this->log('Failed updating video details',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * Uploads a video file
     *
     * @param string $fn
     * @param string $title
     * @param string $description
     * @return bool
     */
    public function upload($fn, $title=null, $description=null)
    {
        $this->log("Uploading video file {$fn}");

        while (true) {
            $this->get(self::UPLOAD_FORM_URL);
            $this->_dump('form.html');
            if (false === strpos(
                $this->_connection->last_url,
                'email_confirm'
            )) {
                break;
            }

            $this->get(self::HOST . self::EMAIL_CONFIRM_URL);
            $this->_dump('confirm_email.html');
        }

        if (!preg_match(
            '#"uploadKey": "([^"]+)"#',
            $this->_response,
            $m
        ) && !preg_match(
            '#UPLOAD_KEY, "([^"]+)"#',
            $this->_response,
            $m
        )) {
            throw new Actor_YouTube_Exception(
                'Failed parsing out upload key',
                Actor_Exception::PROXY_BANNED
            );
        }

        $post = json_encode(array(
            'protocolVersion'      => '0.7',
            'clientId'             => 'scotty html form',
            'createSessionRequest' => array('fields' => array(
                array('external' => array(
                    'name'     => 'file',
                    'filename' => basename($fn),
                    'formPost' => new stdclass()
                )),
                array('inlined' => array(
                    'name'        => 'return_address',
                    'content'     => 'upload.youtube.com',
                    'contentType' => 'text/plain'
                )),
                array('inlined' => array(
                    'name'        => 'upload_key',
                    'content'     => $m[1],
                    'contentType' => 'text/plain'
                )),
                array('inlined' => array(
                    'name'        => 'action_postvideo',
                    'content'     => '1',
                    'contentType' => 'text/plain'
                )),
                array('inlined' => array(
                    'name'        => 'uploader_type',
                    'content'     => 'Web_HTML',
                    'contentType' => 'text/plain'
                ))
            )),
        ));
        $this->_dump('request.js', $post);
        $this->post(self::UPLOAD_REQUEST_URL, $post);
        $this->_dump('response.js');
        $this->_response = json_decode($this->_response, true);
        if (!$this->_response || empty($this->_response['sessionStatus'])) {
            throw new Actor_YouTube_Exception(
                'Invalid response',
                Actor_Exception::PROXY_BANNED
            );
        }

        $url = @$this->_response['sessionStatus']['externalFieldTransfers'][0]['formPostInfo']['url'];
        if (!$url) {
            $this->log('Form post URL not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->_form = new Html_Form();
        $this->_form->action = $url;
        $this->_form->add_file('Filedata', $fn);
        $this->submit();
        $this->_dump('submit.js');
        $this->_response = json_decode($this->_response, true);
        if (!$this->_response || empty($this->_response['sessionStatus'])) {
            throw new Actor_YouTube_Exception(
                'Invalid upload response',
                Actor_Exception::PROXY_BANNED
            );
        }

        $video_id = @$this->_response['sessionStatus']['additionalInfo']['uploader_service.GoogleRupioAdditionalInfo']['completionInfo']['customerSpecificInfo']['video_id'];
        if ($video_id) {
            if ($title || $description) {
                $this->update_video_details($video_id, $title, $description);
            }
            return $video_id;
        }

        $this->log('Upload failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
