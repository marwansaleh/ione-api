<?php 
namespace App\Helpers;

class Helper
{   
    private $_site_url = '';
    private $_site_img_url = '';
    
    public function __construct($site_url, $site_img_url) {
        $this->_site_url = $site_url;
        $this->_site_img_url = $site_img_url;
    }
    public function sanitize_input_query($string) {
        // remove unwanted characters
        $replace = array(
            "‘" => "",
            "’" => "",
            "”" => '',
            "“" => '',
            "–" => "",
            "—" => "",
            "…" => "",
            "&" => "",
            "=" => "",
            "@" => ""
        );

        foreach($replace as $k => $v)
        {
            $content = str_replace($k, $v, $string);
        }

        // Remove any non-ascii character
        $content = preg_replace('/[^\x20-\x7E]*/','', $content);
    }
    
    function get_image_url($image_name, $type=IMAGE_THUMB_LARGE){

        switch ($type){
            case IMAGE_THUMB_ORI: $base_url= 'userfiles/images/'; break;
            case IMAGE_THUMB_LARGE: $base_url= 'userfiles/thumbs/large/'; break;
            case IMAGE_THUMB_PORTRAIT: $base_url= 'userfiles/thumbs/portrait/'; break;
            case IMAGE_THUMB_MEDIUM: $base_url= 'userfiles/thumbs/medium/'; break;
            case IMAGE_THUMB_SMALL: $base_url= 'userfiles/thumbs/small/'; break;
            case IMAGE_THUMB_SQUARE: $base_url= 'userfiles/thumbs/square/'; break;
            case IMAGE_THUMB_SMALLER: $base_url= 'userfiles/thumbs/smaller/'; break;
            case IMAGE_THUMB_TINY: $base_url= 'userfiles/thumbs/tiny/'; break;
        }
        
        $image_url = sprintf("%s/%s/%s", rtrim($this->_site_img_url,'/'), rtrim($base_url, '/'), $image_name);
        return $image_url;
    }
}