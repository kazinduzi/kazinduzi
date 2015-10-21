<?php defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

class text {
    /**
     * Code Highlighter
     *
     * Colorizes code strings
     *
     * @access	public
     * @param	string	the text string
     * @return	string
     */	
    public static function highlight_code($str)
    {		
        // The highlight string function encodes and highlights
        // brackets so we need them to start raw
        $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);

        // Replace any existing PHP tags to temporary markers so they don't accidentally
        // break the string out of PHP, and thus, thwart the highlighting.

        $str = str_replace(array('<?', '?>', '<%', '%>', '\\', '</script>'), 
                                                array('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'), $str);

        // The highlight_string function requires that the text be surrounded
        // by PHP tags, which we will remove later
        $str = '<?php '.$str.' ?>'; // <?

        // All the magic happens here, baby!	
        $str = highlight_string($str, true);

        // Prior to PHP 5, the highligh function used icky <font> tags
        // so we'll replace them with <span> tags.

        if (abs(PHP_VERSION) < 5)
        {
                $str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
                $str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
        }

        // Remove our artificially added PHP, and the syntax highlighting that came with it
        $str = preg_replace('/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i', '<span style="color: #$1">', $str);
        $str = preg_replace('/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is', "$1</span>\n</span>\n</code>", $str);
        $str = preg_replace('/<span style="color: #[A-Z0-9]+"\><\/span>/i', '', $str);

        // Replace our markers back to PHP tags.
        $str = str_replace(array('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'),
                                                array('&lt;?', '?&gt;', '&lt;%', '%&gt;', '\\', '&lt;/script&gt;'), $str);

        return $str;
    }

    /**
     * Character Limiter
     *
     * Limits the string based on the character count.  Preserves complete words
     * so the character count may not be exactly as specified.
     *
     * @access	public
     * @param	string
     * @param	integer
     * @param	string	the end character. Usually an ellipsis
     * @return	string
     */
    public static function character_limiter($str, $n = 500, $end_char = '&#8230;')
    {
        if (strlen($str) < $n)
        {
                return $str;
        }

        $str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));

        if (strlen($str) <= $n)
        {
                return $str;
        }

        $out = "";
        foreach (explode(' ', trim($str)) as $val)
        {
            $out .= $val.' ';

            if (strlen($out) >= $n)
            {
                    $out = trim($out);
                    return (strlen($out) == strlen($str)) ? $out : $out.$end_char;
            }		
        }
    }
    /**
     * Word Limiter
     *
     * Limits a string to X number of words.
     *
     * @access	public
     * @param	string
     * @param	integer
     * @param	string	the end character. Usually an ellipsis
     * @return	string
     */	
    public static function word_limiter($str, $limit = 100, $end_char = '&#8230;')
    {
        if (trim($str) == '')
        {
                return $str;
        }

        preg_match('/^\s*+(?:\S++\s*+) {1,'.(int) $limit.'}/', $str, $matches);

        if (strlen($str) == strlen($matches[0]))
        {
                $end_char = '';
        }

        return rtrim($matches[0]).$end_char;
    }    
}
	