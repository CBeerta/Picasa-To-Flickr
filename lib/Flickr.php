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
    /**
    * Flickr "api" configuration
    **/
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

    /**
    * List with all sets from flickr
    **/
    private $_albumList = array();

    /**
    * Set Flickr Api Key
    *
    * @param string $key api key
    *
    * @return void
    **/
    public function setApiKey($key)
    {
        $this->_cfg['api_key'] = $key;    
    }

    /**
    * Set Flickr Api Secret
    *
    * @param string $secret api secret
    *
    * @return void
    **/
    public function setApiSecret($secret)
    {
        $this->_cfg['api_secret'] = $secret;    
    }

    /**
    * Set Flickr Api Token
    *
    * @param string $token api token
    *
    * @return void
    **/
    public function setApiToken($token)
    {
        $this->_cfg['token'] = $token;
    }

    /**
    * Sign arguments with key
    *
    * @param array $args Arguments
    *
    * @return string
    **/
    public function signArgs($args)
    {
        ksort($args);
        $a = '';
        foreach ($args as $k => $v) {
            $a .= $k . $v;
        }
        return md5($this->_cfg['api_secret'] . $a);
    }

    /**
    * Get auth Url for flickr
    *
    * @param string $perms permissions to request
    * @param string $frob  The FROB!
    *
    * @return string
    **/
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
        foreach ($args as $k => $v) {
            $pairs[] = urlencode($k).'='.urlencode($v);
        }
        return $this->_cfg['auth_endpoint'].implode('&', $pairs);
    }


    /**
    * Call a method on flickr
    *
    * @param string $method   method to use
    * @param array  $params   parameters
    * @param string $filename filename of file to upload
    *
    * @return xml
    **/
    public function callMethod($method, $params = array(), $filename = false)
    {
        $p = $params;
        if ($method) {
            $p['method'] = $method;
        }
        $p['api_key'] = $this->_cfg['api_key'];

        if ($this->_cfg['api_secret']) {
            $p['api_sig'] = $this->signArgs($p);
        }

        $endpoint = ($filename == false) 
            ? $this->_cfg['endpoint'] 
            : $this->_cfg['upload_endpoint'];

        $req = new HTTP_Request(
            $endpoint,
            array('timeout' => $this->_cfg['conn_timeout'])
        );

        // FIXME: dunno what this is supposed to do, but it breaks photo uploads
        // $req->_readTimeout = array($this->_cfg['io_timeout'], 0);

        $req->setMethod(HTTP_REQUEST_METHOD_POST);

        foreach ($p as $k => $v) {
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

        try {
            $xml = new SimpleXMLElement($http_body);
            if ($xml->attributes()->stat == 'fail') {
                throw new Exception($xml->err->attributes()->msg);
            }
        } catch (Exception $e) {
            // echo "ERROR: " . $e->getMessage() . "\n";
            return false;
        }

        return $xml;
    }
        
    /**
    * Query the Flickr Api
    * 
    * @param string $method   method to use
    * @param array  $params   parameters
    * @param string $filename filename of file to upload
    *
    * @return xml
    **/
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

    /**
    * Return all albums
    * 
    * @return object
    **/
    public function getAlbums()
    {
    }

    /**
    * Get all Photos from an album
    * 
    * @param int $albumId ID of the Album to query
    *
    * @return object
    **/
    public function getPhotos($albumId)
    {
    }

    /**
    * Get Meta Data for a Photo
    * 
    * @param int $albumId ID of the Album 
    * @param int $photoId ID of the Photo
    *
    * @return object
    **/
    public function getPhotoMeta($albumId, $photoId)
    {
    }

    /**
    * Set Meta Data for a Photo
    * 
    * @param int    $albumId ID of the Album 
    * @param int    $photoId ID of the Photo
    * @param object $meta    Meta Data to set
    *
    * @return bool
    **/
    public function setPhotoMeta($albumId, $photoId, $meta)
    {
        // print_r(array($albumId, $photoId, $meta));
        
        foreach ($meta as $k=>$v) {
            if ($v === null) {
                continue;
            }
            
            switch ($k) {
            case 'keywords':
                $res = $this->queryApi(
                    "flickr.photos.setTags", 
                    array(
                        'photo_id' => $photoId,
                        'tags' => implode(' ', $v)
                    )
                );
                break;
            case 'date_taken':
                $res = $this->queryApi(
                    "flickr.photos.setDates", 
                    array(
                        'photo_id' => $photoId,
                        'date_taken' => $v,
                        'date_posted' => $v,
                    )
                );
                break;
            case 'geo_pos':
                $res = $this->queryApi(
                    "flickr.photos.geo.setLocation", 
                    array(
                        'photo_id' => $photoId,
                        'lat' => $v[0],
                        'lon' => $v[1],
                    )
                );
                break;
            default:
                break;
            
            }
        }
        
        return;
    }

    /**
    * Create a new Album
    * 
    * @param string $albumName Name of the Album 
    * @param int    $photoId   ID of Photo to add
    *
    * @return int 
    **/
    public function addToAlbum($albumName, $photoId)
    {
        // See if the albumlist has been populated, if not
        // load all available sets
        if (count($this->_albumList) == 0) {
            $res =  $this->queryApi(
                "flickr.photosets.getList", 
                array(
                    'title' => $albumName
                )
            );
        
            $albumId = null;
            foreach ($res->photosets->photoset as $set) {
                $this->_albumList[(string) $set->title] = (object) array(
                    'description' => (string) $set->description,
                    'id' => (int) $set->attributes()->id,
                );
            }
        }
        
        if (!in_array($albumName, array_keys($this->_albumList))) {
            $res =  $this->queryApi(
                "flickr.photosets.create", 
                array(
                    'title' => $albumName,
                    'primary_photo_id' => $photoId,
                )
            );
            $this->_albumList = array();
        } else {
            $res =  $this->queryApi(
                "flickr.photosets.addPhoto", 
                array(
                    'photoset_id' => $this->_albumList[$albumName]->id,
                    'photo_id' => $photoId,
                )
            );
        }
        return;
    }

    /**
    * Upload a Photo
    * 
    * @param string $filename Filename with the photo
    * @param string $title    Title to give the photo
    *
    * @return int id of the photo that was created
    **/
    public function uploadPhoto($filename, $title)
    {
        try
        {
            // Check if the picture already exists
            $search = $this->queryApi(
                "flickr.photos.search", 
                array(
                    'text' => "\"$title\"",
                    'user_id' => 'me',
                )
            );
            $total = (int) $search->photos->attributes()->total;
            
            // Go through all results and find the exact match
            // return id if one was found.
            foreach ($search->photos->photo as $k => $v) {
                $attr = $v->attributes();
                if (isset($attr->title) && $attr->title == $title) {
                    return (int) $attr->id;
                }
            }
        } catch (Exception $e) {
            print $e;
            return false;
        }
        
        print "\t ...Uploading!!\n";

        // Upload new photo        
        $upload = $this->queryApi(
            false, 
            array(
                'title' => $title,
            ),
            $filename
        );
        
        // print_r($upload);
        
        if (!$upload) {
            return false;
        }
        return (int) $upload->photoid;
    }

}
