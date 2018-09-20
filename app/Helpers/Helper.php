<?php 
namespace App\Helpers;

class Helper
{   
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
}