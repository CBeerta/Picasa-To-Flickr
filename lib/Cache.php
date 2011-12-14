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
* Cache
*
* @category CLI
* @package  Migrater
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Cache
{
    /**
    * Save a cachefile
    *
    * @param string $filename filename
    * @param mixed  $data     data to save, must be serializable
    *
    * @return bool
    **/
    public static function save($filename, $data)
    {
        $data = serialize($data);
        return file_put_contents($filename, $data);
    }

    /**
    * Load a cachefile
    *
    * @param string $filename filename
    *
    * @return mixed
    **/
    public static function load($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            return false;
        }
        
        $data = file_get_contents($filename);
        $data = unserialize($data);

        return $data;
    }
    
    /**
    * Check if cached info is current
    *
    * @param string $filename filename
    * @param mixed  $data     data to compare, must be serializable
    *
    * @return bool
    **/
    public static function isCurrent($filename, $data)
    {
        $old = self::load($filename);
        
        if (!$old) {
            return false;
        }
        
        $old = serialize($old);
        $new = serialize($data);
        
        return ($old == $new);
    }
}

