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

/**
* Picasa PhotoService
*
* @category CLI
* @package  Migrater
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Picasa implements PhotoService
{
    private $_baseUrl = "https://picasaweb.google.com/data/";
    
    private $_userId = null;

    /**
    * Set the UserId 
    *
    * @param int $userId userId to set
    *
    * @return void;
    **/    
    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }
    
    /**
    * Return all albums
    * 
    * @return object
    **/
    public function getAlbums()
    {
        if (is_null($this->_userId)) {
            throw new Exception("No User ID Given.");
        }
        
        $data = file_get_contents(
            $this->_baseUrl
            . 'feed/base/'
            . 'user/' 
            . $this->_userId
        );
        
        $xml = new SimpleXMLElement($data);
        
        $albums = array();
        foreach ($xml->entry as $album) {
            
            preg_match('|\/albumid\/([0-9]+)[/\?]|i', (string) $album->id, $matches);
            
            $albums[] = (object) array(
                'title' => (string) $album->title,
                'id' => (string) $album->id,
                'albumid' => $matches[1],
                'published' => (string) $album->published,
            );
        }
        
        return $albums;
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
        $data = file_get_contents(
            $this->_baseUrl
            . 'feed/base/'
            . 'user/' 
            . $this->_userId
            . '/albumid/' . $albumId
            . '?hl=en_US&imgmax=1600'
        );
        $data = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2_$3", $data);

        $photos = array();

        try {        
            $xml = new SimpleXMLElement($data);
            foreach ($xml->entry as $photo) {
                $link = (string) $photo->content->attributes()->src[0];
                $filename = str_replace("%25", "%", basename($link));
    
                preg_match(
                    '|\/photoid\/([0-9]+)[/\?]|i', 
                    (string) $photo->id, 
                    $matches
                );
                
                $photos[] = (object) array(
                    'title' => (string) $photo->title,
                    'id' => (string) $photo->id,
                    'link' => $link,
                    'photoid' => $matches[1],
                    'filename' => urldecode(urldecode($filename)),
                );
            }
        } catch (Exception $e) {
            throw new Exception("Invalid response from Picasa: {$e}");
        }
        return $photos;
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
        $data = file_get_contents(
            $this->_baseUrl
            . 'entry/base/'
            . 'user/' 
            . $this->_userId
            . '/albumid/' . $albumId
            . '/photoid/' . $photoId
            . '?hl=en_US&imgmax=1600'
        );
        $data = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2_$3", $data);
        
        $meta = (object) array(
            'author' => null,
            'date_taken' => null,
            'keywords' => array(),
            'title' => null,
            'geo_pos' => null,
        );
        
        try {
            $xml = new SimpleXMLElement($data);
            
            preg_match(
                "|Date: (.*?)\<br|i", 
                strip_tags((string) $xml->summary, '<br>'), 
                $matches
            );
            
            if (($date_taken = strtotime($matches[1])) !== false) {
                $meta->date_taken = $date_taken;
            }
            
            $meta->author = (string) $xml->media_group->media_credit;
            $meta->title = (string) $xml->media_group->media_title;
            
            if (isset($xml->georss_where->gml_Point->gml_pos)) {
                $meta->geo_pos
                    = explode(' ', (string) $xml->georss_where->gml_Point->gml_pos);
            }
                
            if (isset($xml->media_group->media_keywords)) {
                $keywords = (string) $xml->media_group->media_keywords;
                foreach (explode(',', $keywords) as $kwd) {
                    $kwd = str_replace(' ', '-', trim($kwd));
                    array_push($meta->keywords, $kwd);
                }
            }
                
        } catch (Exception $e) {
            print $e;
            return false;
        }
        
        return $meta;
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
    }

}



