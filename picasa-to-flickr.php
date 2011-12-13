#!/usr/bin/env php
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

require_once 'vendor/Cling/Cling.php';

require_once 'lib/ServiceInterface.php';
require_once 'lib/Flickr.php';
require_once 'lib/Picasa.php';

$app = new Cling(array('debug' => true));

$picasa = new Picasa();
$flickr = new Flickr();

/**
* Help comes first
**/
$app->command('help', 'h', 
    function() use ($app)
    {
        $app->notFound();
        exit;
    })
    ->help("This Help Text.");

$app->command('flickr-api-key:', 
    function($key) use ($flickr)
    {
        $flickr->setApiKey($key);
    })
    ->help("Flickr Api Key");

$app->command('flickr-api-secret:', 
    function($secret) use ($flickr)
    {
        $flickr->setApiSecret($secret);
    })
    ->help("Flickr Api Secret");

$app->command('flickr-token:', 
    function($token) use ($flickr)
    {
        $flickr->setApiToken($token);
    })
    ->help("Flickr Api Frob");

$app->command('get-flickr-frob', 
    function() use ($flickr)
    {
        $frob = (string) $flickr->callMethod('flickr.auth.getFrob')->frob;
        
        print "Your Frob is {$frob}\n";
        print "Please Open the following URL in your browser, and allow\n";
        print $flickr->getAuthUrl('delete', $frob) . "\n";
        exit;
    })
    ->help("Request a `frob` from Flickr api for authentication");

$app->command('authenticate-frob:', 
    function($frob) use ($flickr)
    {
        $token = $flickr->callMethod(
            'flickr.auth.getToken', 
            array('frob' => $frob)
        );
        
        print "Your token is: {$token->auth->token}. Keep it around!";
        exit;
    })
    ->help("Authenticate against Flickr, and recieve a token.");

$app->command('picasa-user-id:', 
    function($uid) use ($picasa)
    {
        $picasa->setUserId($uid);
    })
    ->help("UserID for Picasa Web Albums");

$app->command(':*', 
    function() use ($app, $flickr, $picasa)
    {
        foreach ($picasa->getAlbums($app->option('picasa-user-id')) as $album) {
        
            //print_r($album);
            print "Album: {$album->title}\n";
            
            if (!in_array($album->title, array('My Photography' , 'Höchst'))) {
                print "\t... Skipping\n";
                continue;
            }
            
            foreach ($picasa->getPhotos($album->albumid) as $photo) {
            
                //print_r($photo);
                print "\tPhoto: {$photo->title}\n";

                $tmp_file = 'data/' . $photo->filename;
                
                if (!file_exists($tmp_file)) {
                    $ch = curl_init();
                    $out = fopen($tmp_file, 'w');
                    curl_setopt($ch, CURLOPT_URL, $photo->link);
                    curl_setopt($ch, CURLOPT_HEADER, false);
                    curl_setopt($ch, CURLOPT_FILE, $out);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($out);
                }
                
                $id = $flickr->uploadPhoto($tmp_file, $photo->title);

                if (!is_numeric($id)) {
                    print "\t... Failed to Upload\n";
                    continue;
                }
                
                $meta = $picasa->getPhotoMeta($album->albumid, $photo->photoid);
                if ($meta) {
                    print "\t... Setting Metadata\n";
                    $flickr->setPhotoMeta(null, $id, $meta);
                }

                print "\t... Adding to Album\n";
                $flickr->addToAlbum($album->title, $id);
            }


        }

    });



$app->run();



