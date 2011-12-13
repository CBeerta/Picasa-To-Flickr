<?php
/**
* Copy from Picasa to Flickr
*
* PHP Version 5.3
*
* Copyright (C) 2011 by Claus Beerta
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*
* @category CLI
* @package  Migrater
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/

require_once 'HTTP/Request.php';

/**
* Flickr PhotoService
*
* @category CLI
* @package  Migrater
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Flickr implements PhotoService
{
    private $_cfg = array(
        'api_key'	      => '',
        'api_secret'	  => '',
        'endpoint'	      => 'http://www.flickr.com/services/rest/',
        'auth_endpoint'	  => 'http://www.flickr.com/services/auth/?',
        'upload_endpoint' => 'http://api.flickr.com/services/upload/',
        'conn_timeout'    => 5,
        'io_timeout'	  => 5,
        'token'           => null,
	);

    public function setApiKey($key)
    {
        $this->_cfg['api_key'] = $key;    
    }

    public function setApiSecret($secret)
    {
        $this->_cfg['api_secret'] = $secret;    
    }

    public function setApiToken($token)
    {
        $this->_cfg['token'] = $token;
    }

    public function signArgs($args)
    {
        ksort($args);
        $a = '';
        foreach($args as $k => $v) {
            $a .= $k . $v;
        }
        return md5($this->_cfg['api_secret'] . $a);
    }

    function getAuthUrl($perms, $frob='')
    {
        $args = array(
            'api_key'	=> $this->_cfg['api_key'],
            'perms'		=> $perms,
        );

        if (strlen($frob)) {
            $args['frob'] = $frob; 
        }

        $args['api_sig'] = $this->signArgs($args);

        $pairs =  array();
        foreach($args as $k => $v){
            $pairs[] = urlencode($k).'='.urlencode($v);
        }
        return $this->_cfg['auth_endpoint'].implode('&', $pairs);
    }

    public function callMethod($method, $params = array(), $filename = false)
    {
        $p = $params;
        if ($method) {
            $p['method'] = $method;
        }
        $p['api_key'] = $this->_cfg['api_key'];

		if ($this->_cfg['api_secret']){
			$p['api_sig'] = $this->signArgs($p);
		}
	    
	    $endpoint = ($filename == false) 
	        ? $this->_cfg['endpoint'] 
	        : $this->_cfg['upload_endpoint'];
	        
		$req = new HTTP_Request(
		    /* $this->_cfg['endpoint'], */
		    $endpoint,
		    array('timeout' => $this->_cfg['conn_timeout'])
	    );

        // FIXME: dunno what this is supposed to do, but it breaks photo uploads
		// $req->_readTimeout = array($this->_cfg['io_timeout'], 0);

		$req->setMethod(HTTP_REQUEST_METHOD_POST);

	    foreach($p as $k => $v){
	        $req->addPostData($k, $v);
	    }
		
		if ($filename) {
            $result = $req->addFile(
                'photo', 
                $filename
            );
            if (PEAR::isError($result)) {
                echo $result->getMessage();
                die();
            }
		}
		
		$req->sendRequest();

		$http_code = $req->getResponseCode();
		$http_head = $req->getResponseHeader();
		$http_body = $req->getResponseBody();
		
		// print_r(array($http_body, $http_head, $http_code));
		
		return new SimpleXMLElement($http_body);
    }
        
    public function queryApi($method, $params = array(), $filename = false)
    {
        if ($this->_cfg['token'] === null) {
            throw new Exception("No `token` for flickr supplied");
        }
        
        return $this->callMethod(
            $method, 
            array_merge($params, array('auth_token' => $this->_cfg['token'])),
            $filename
        );
    }

    public function getAlbums()
    {
    }

    public function getPhotos($albumId)
    {
    }

    public function getPhotoMeta($albumId, $photoId)
    {
    }

    public function setPhotoMeta($albumId, $photoId, $meta)
    {
        print_r(array($albumId, $photoId, $meta));
        
        return;
    }

    public function createAlbum($albumName)
    {
        /*
        return $this->queryApi(
            "flickr.photosets.create", 
            array(
                'title' => $albumName,
            )
        );
        */
        return false;
    }

    public function uploadPhoto($filename, $title)
    {
        try
        {
            // Check if the picture already exists
            $search = $this->queryApi(
                "flickr.photos.search", 
                array(
                    'text' => $title,
                    'user_id' => 'me',
                )
            );
            $total = (int) $search->photos->attributes()->total;
            
            if ($total == 1) {
                // found a match, return to update metadata
                return (int) $search->photos->photo[0]->attributes()->id;
            } else if ($total > 1) {
                // more then one found, that isn't good
                print "Multiple pictrures found for {$title}\n";
                print_r($search->photos);
                return false;
            }
        } catch (Exception $e) {
            print $e;
            return false;
        }
        
        print "Uploading!!\n";

        // Upload new photo        
        $upload = $this->queryApi(
            false, 
            array(
                'title' => $title,
            ),
            $filename
        );
        
        if (!$upload) {
            return false;
        }
        return (int) $upload->photoid;
    }

}
