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
* PhotoService Interface
*
* @category CLI
* @package  Migrater
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
interface PhotoService
{
    /**
    * Return all albums
    * 
    * @return object
    **/
    public function getAlbums();

    /**
    * Create a new Album
    * 
    * @param string $albumName Name of the Album 
    * @param int    $photorId  ID of Photo to add
    *
    * @return int 
    **/
    public function addToAlbum($albumName, $photoId);
    
    /**
    * Get all Photos from an album
    * 
    * @param int $albumId ID of the Album to query
    *
    * @return object
    **/
    public function getPhotos($albumId);
    
    /**
    * Get Meta Data for a Photo
    * 
    * @param int $albumId ID of the Album 
    * @param int $photoId ID of the Photo
    *
    * @return object
    **/
    public function getPhotoMeta($albumId, $photoId);

    /**
    * Set Meta Data for a Photo
    * 
    * @param int    $albumId ID of the Album 
    * @param int    $photoId ID of the Photo
    * @param object $meta    Meta Data to set
    *
    * @return bool
    **/
    public function setPhotoMeta($albumId, $photoId, $meta);

    /**
    * Upload a Photo
    * 
    * @param string $filename Filename with the photo
    * @param string $title    Title to give the photo
    *
    * @return int id of the photo that was created
    **/
    public function uploadPhoto($filename, $title);
}
