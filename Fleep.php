<?php
/*
The MIT License (MIT)

Copyright (c) 2019 Hai Pham Hoang

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 */

/**
 *  Main Object. Construct it by passing your webhook url from fleep.com (e.g. https://fleep.io/hook/XXXXXXXXX)
 *  Needed for posting Fleep Messages
 */
class Fleep
{
    // WebhookUrl e.g. https://fleep.io/hook/XXXXXXXXX
    public $url;

    // Empty => Default username set in Fleep Webhook integration settings
    public $username = ' ';

    public function __construct($webhookUrl)
    {
        $this->url = $webhookUrl;
    }

    public function setDefaultUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function setWebhookUrl($webhookUrl)
    {
        $this->url = $webhookUrl;
    }

    public function send(FleepMessage $message)
    {
        $data = $message->toArray();

        try {

            if (empty($data['content'])) {
                return false;
            }
            $postData = $this->buildDataFiles($data['content'], $data['attachments']);

            if (is_array($this->url)) {
                foreach ($this->url as $key => $url) {
                    $result = $this->sendToWebHook($url, $postData);

                    if ($result == '{}') {
                        $result[$key] = true;
                    }
        
                    $result[$key] = false;
                }
            } else {
                $result = $this->sendToWebHook($this->url, $postData);
                if ($result == '{}') {
                    return true;
                }
    
                return false;
            }
            
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    private function sendToWebHook($webhookUrl, $postData)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $webhookUrl,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $postData['data'],
            CURLOPT_HTTPHEADER => array(
            //"Authorization: Bearer $TOKEN",
                "Content-Type: multipart/form-data; boundary=" . $postData['delimiter'],
                "Content-Length: " . strlen($postData['data'])
            ),
        ));
        $result = curl_exec($curl);

        if (!$result) {
            return false;
        }

        curl_close($curl);

        return $result;
    }

    private function buildDataFiles($fields, $files=null)
    {
        $data = '';
        $eol = "\r\n";
        
        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;
    
        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
                . $content . $eol;
        }
    
        if (!empty($files)) {
            foreach ($files as $name => $listFile) {
                foreach ($listFile as $fileInfo) {
                    $data .= "--" . $delimiter . $eol
                      . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $fileInfo['name'] . '"' . $eol
                      . 'Content-Type: '.$fileInfo['type'].$eol
                      . 'Content-Transfer-Encoding: binary'.$eol
                      ;
                    $data .= $eol;
                    $data .= $fileInfo['content'] . $eol;
                }
            }
        }
        
        $data .= "--" . $delimiter . "--".$eol;

        $result['delimiter'] = $delimiter;
        $result['data'] = $data;
    
        return $result;
    }
}

class FleepMessage
{
    private $fleep;

    // Message to post
    public $message = "";

    // Empty => Default username set in Fleep instance
    public $username;

    // Array of Fleep attachment
    public $attachments;

    public function __construct(Fleep $fleep)
    {
        $this->fleep = $fleep;
    }

    /*
    Settings
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }
    

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function addAttachment($name, $content, $contentType=null)
    {
        $attachmentFormat = array(
                'name' => $name,
                'content' => $content,
                'type' => $contentType,
            );

        if (!isset($this->attachments)) {
            $this->attachments = array('files' => array($attachmentFormat));
            return $this;
        }

        $this->attachments[] = $attachmentFormat;
        return $this;
    }

    public function toArray()
    {
        // Loading defaults
        if (isset($this->fleep->username)) {
            $username = $this->fleep->username;
        }

        // Overwrite/create defaults
        if (isset($this->username)) {
            $username = $this->username;
        }

        if (empty($this->message)) {
            return $data = array();
        }
        $data = array(
            'content' => array('message' => $this->message),
        );
        if (isset($username)) {
            $data['content']['user'] = $username;
        }

        $data['attachments'] = null;
        if (isset($this->attachments)) {
            $data['attachments'] = $this->attachments;
        }

        return $data;
    }

    /*
     * Send this message to Fleep
     */
    public function send()
    {
        return $this->fleep->send($this);
    }
}
