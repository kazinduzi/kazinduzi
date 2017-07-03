<?php

/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/).
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 *
 * @link      http://kazinduzi.com
 *
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 */
namespace framework\library\Captcha;

use Kazinduzi\Core\Kazinduzi;

/**
 * Captcha class.
 */
class Captcha
{
    const RESOURCES_DIR = 'Resources';
    const WORDS_FILE = 'Words/en.php';

    /**
     * Sessionname to store the original text.
     */
    protected $sessionVar = 'captcha';

    /**
     * Dictionary word file (empty for random text).
     */
    protected $wordsFile = self::WORDS_FILE;

    /**
     * Path for resource files (fonts, words, etc.).
     *
     * "resources" by default. For security reasons, is better move this
     * directory to another location outise the web server
     */
    protected $resourcesPath = self::RESOURCES_DIR;

    /**
     * Width of the image.
     */
    protected $width = 200;

    /**
     * Height of the image.
     */
    protected $height = 70;

    /**
     * Min word length (for non-dictionary random text generation).
     */
    protected $minWordLength = 5;

    /**
     * Max word length (for non-dictionary random text generation).
     *
     * Used for dictionary words indicating the word-length
     * for font-size modification purposes
     */
    protected $maxWordLength = 8;

    /**
     * Background color in RGB-array.
     */
    protected $backgroundColor = [255, 255, 255];

    /**
     * Foreground colors in RGB-array.
     */
    protected $colors = [
    [27, 78, 181], // blue
    [22, 163, 35], // green
    [214, 36, 7], // red
    ];

    /**
     * Shadow color in RGB-array or null.
     */
    protected $shadowColor = null; //array(0, 0, 0);

    /**
     * Horizontal line through the text.
     */
    protected $lineWidth = 0;

    /**
     * Font configuration.
     *
     * - font: TTF file
     * - spacing: relative pixel space between character
     * - minSize: min font size
     * - maxSize: max font size
     */
    protected $fonts = [
    'Antykwa'  => ['spacing' => -3, 'minSize' => 27, 'maxSize' => 30, 'font' => 'AntykwaBold.ttf'],
    'Candice'  => ['spacing' => -1.5, 'minSize' => 28, 'maxSize' => 31, 'font' => 'Candice.ttf'],
    'DingDong' => ['spacing' => -2, 'minSize' => 24, 'maxSize' => 30, 'font' => 'Ding-DongDaddyO.ttf'],
    'Duality'  => ['spacing' => -2, 'minSize' => 30, 'maxSize' => 38, 'font' => 'Duality.ttf'],
    'Heineken' => ['spacing' => -2, 'minSize' => 24, 'maxSize' => 34, 'font' => 'Heineken.ttf'],
    'Jura'     => ['spacing' => -2, 'minSize' => 28, 'maxSize' => 32, 'font' => 'Jura.ttf'],
    'StayPuft' => ['spacing' => -1.5, 'minSize' => 28, 'maxSize' => 32, 'font' => 'StayPuft.ttf'],
    'Times'    => ['spacing' => -2, 'minSize' => 28, 'maxSize' => 34, 'font' => 'TimesNewRomanBold.ttf'],
    'VeraSans' => ['spacing' => -1, 'minSize' => 20, 'maxSize' => 28, 'font' => 'VeraSansBold.ttf'],
    ];

    /**
     * Wave configuracion in X and Y axes.
     */
    protected $Yperiod = 12;
    protected $Yamplitude = 14;
    protected $Xperiod = 11;
    protected $Xamplitude = 5;

    /** letter rotation clockwise */
    protected $maxRotation = 8;

    /**
     * Internal image size factor (for better image quality)
     * 1: low, 2: medium, 3: high.
     */
    protected $scale = 2;

    /**
     * Blur effect for better image quality (but slower image processing).
     * Better image results with scale=3.
     */
    protected $blur = false;

    /**
     * Debug?
     */
    protected $debug = false;

    /**
     * @var string Image format: jpeg or png
     */
    protected $imageFormat = 'jpeg';

    /**
     * @var resource image resource
     */
    protected $img;

    /**
     * @var \Session
     */
    protected $session;

    /**
     * Constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('Image CAPTCHA requires GD extension');
        }
        $this->resourcesPath = __DIR__.DIRECTORY_SEPARATOR.$this->resourcesPath;
    }

    /**
     * Get the session object.
     *
     * @return \Session
     */
    public function getSession()
    {
        if (null === $this->session) {
            $this->session = Kazinduzi::session();
        }

        return $this->session;
    }

    /**
     * Magic setter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * Magic getter.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    /**
     * create Captcha image.
     */
    public function createImage()
    {
        $ini = microtime(true);

    /* Initialization */
    $this->imageAllocate();

    /* Text insertion */
    $text = $this->getCaptchaText();
        $fontsConf = $this->fonts[array_rand($this->fonts)];
        $this->writeText($text, $fontsConf);

    //$_SESSION[$this->sessionVar] = $text;
    $this->getSession()->set($this->sessionVar, $text);

    /* Transformations */
    if (!empty($this->lineWidth)) {
        $this->writeLine();
    }
        $this->waveImage();
        if ($this->blur && function_exists('imagefilter')) {
            imagefilter($this->img, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $this->reduceImage();

        if ($this->debug) {
            imagestring($this->img, 1, 1, $this->height - 8, "$text {$fontConfigs['font']} ".round((microtime(true) - $ini) * 1000).'ms', $this->GdFgColor);
        }

    /* Output */
    $this->writeImage();
        $this->cleanup();
    }

    /**
     * Creates the image resources.
     */
    protected function imageAllocate()
    {
        // Cleanup
    if (!empty($this->img)) {
        imagedestroy($this->img);
    }

        $this->img = imagecreatetruecolor($this->width * $this->scale, $this->height * $this->scale);

    // Background color
    $this->GdBgColor = imagecolorallocate($this->img, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
        imagefilledrectangle($this->img, 0, 0, $this->width * $this->scale, $this->height * $this->scale, $this->GdBgColor);

    // Foreground color
    $color = $this->colors[mt_rand(0, count($this->colors) - 1)];
        $this->GdFgColor = imagecolorallocate($this->img, $color[0], $color[1], $color[2]);

    // Shadow color
    if (!empty($this->shadowColor) && is_array($this->shadowColor) && count($this->shadowColor) >= 3) {
        $this->GdShadowColor = imagecolorallocate($this->img, $this->shadowColor[0], $this->shadowColor[1], $this->shadowColor[2]);
    }
    }

    /**
     * Text generation.
     *
     * @return string
     */
    protected function getCaptchaText()
    {
        $text = $this->getDictionaryCaptchaText();
        if (!$text) {
            $text = $this->getRandomCaptchaText();
        }

        return $text;
    }

    /**
     * Random text generation.
     *
     * @param int $length
     *
     * @return string
     */
    protected function getRandomCaptchaText($length = null)
    {
        if (empty($length)) {
            $length = rand($this->minWordLength, $this->maxWordLength);
        }

        $words = 'abcdefghijlmnopqrstvwyz';
        $vocals = 'aeiou';

        $text = '';
        $vocal = rand(0, 1);
        for ($i = 0; $i < $length; $i++) {
            if ($vocal) {
                $text .= substr($vocals, mt_rand(0, 4), 1);
            } else {
                $text .= substr($words, mt_rand(0, 22), 1);
            }
            $vocal = !$vocal;
        }

        return $text;
    }

    /**
     * Random dictionary word generation.
     *
     * @param bool $extended Add extended "fake" words
     *
     * @return string Word
     */
    protected function getDictionaryCaptchaText($extended = false)
    {
        if (empty($this->wordsFile)) {
            return false;
        }

    // Full path of words file
    if (substr($this->wordsFile, 0, 1) == '/') {
        $wordsfile = $this->wordsFile;
    } else {
        $wordsfile = $this->resourcesPath.'/'.$this->wordsFile;
    }

        if (!file_exists($wordsfile)) {
            return false;
        }

        $fp = fopen($wordsfile, 'r');
        $length = strlen(fgets($fp));
        if (!$length) {
            return false;
        }
        $line = rand(1, (filesize($wordsfile) / $length) - 2);
        if (fseek($fp, $length * $line) == -1) {
            return false;
        }
        $text = trim(fgets($fp));
        fclose($fp);

    /* Change ramdom volcals */
    if ($extended) {
        $text = preg_split('//', $text, -1, PREG_SPLIT_NO_EMPTY);
        $vocals = ['a', 'e', 'i', 'o', 'u'];
        foreach ($text as $i => $char) {
            if (mt_rand(0, 1) && in_array($char, $vocals)) {
                $text[$i] = $vocals[mt_rand(0, 4)];
            }
        }
        $text = implode('', $text);
    }

        return $text;
    }

    /**
     * Horizontal line insertion.
     */
    protected function writeLine()
    {
        $x1 = $this->width * $this->scale * .15;
        $x2 = $this->textFinalX;
        $y1 = rand($this->height * $this->scale * .40, $this->height * $this->scale * .65);
        $y2 = rand($this->height * $this->scale * .40, $this->height * $this->scale * .65);
        $width = $this->lineWidth / 2 * $this->scale;

        for ($i = $width * -1; $i <= $width; $i++) {
            imageline($this->img, $x1, $y1 + $i, $x2, $y2 + $i, $this->GdFgColor);
        }
    }

    /**
     * Text insertion.
     *
     * @param string $text
     * @param array  $fontConfigs
     */
    protected function writeText($text, array $fontConfigs = [])
    {
        if (empty($fontConfigs)) {
            // Select the font configuration
        $fontConfigs = $this->fonts[array_rand($this->fonts)];
        }

    // Full path of font file
    $fontfile = $this->resourcesPath.'/Fonts/'.$fontConfigs['font'];

    /* Increase font-size for shortest words: 9% for each glyp missing */
    $lettersMissing = $this->maxWordLength - strlen($text);
        $fontSizefactor = 1 + ($lettersMissing * 0.09);

    // Text generation (char by char)
    $x = 20 * $this->scale;
        $y = round(($this->height * 27 / 40) * $this->scale);
        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $degree = rand($this->maxRotation * -1, $this->maxRotation);
            $fontsize = rand($fontConfigs['minSize'], $fontConfigs['maxSize']) * $this->scale * $fontSizefactor;
            $letter = substr($text, $i, 1);

            if ($this->shadowColor) {
                $coords = imagettftext($this->img, $fontsize, $degree, $x + $this->scale, $y + $this->scale, $this->GdShadowColor, $fontfile, $letter);
            }
            $coords = imagettftext($this->img, $fontsize, $degree, $x, $y, $this->GdFgColor, $fontfile, $letter);
            $x += ($coords[2] - $x) + ($fontConfigs['spacing'] * $this->scale);
        }

        $this->textFinalX = $x;
    }

    /**
     * Wave filter.
     */
    protected function waveImage()
    {
        // X-axis wave generation
    $xp = $this->scale * $this->Xperiod * rand(1, 3);
        $k = rand(0, 100);
        for ($i = 0; $i < ($this->width * $this->scale); $i++) {
            imagecopy($this->img, $this->img, $i - 1, sin($k + $i / $xp) * ($this->scale * $this->Xamplitude), $i, 0, 1, $this->height * $this->scale);
        }

    // Y-axis wave generation
    $k = rand(0, 100);
        $yp = $this->scale * $this->Yperiod * rand(1, 2);
        for ($i = 0; $i < ($this->height * $this->scale); $i++) {
            imagecopy($this->img, $this->img, sin($k + $i / $yp) * ($this->scale * $this->Yamplitude), $i - 1, 0, $i, $this->width * $this->scale, 1);
        }
    }

    /**
     * Reduce the image to the final size.
     */
    protected function reduceImage()
    {
        $imgResampled = imagecreatetruecolor($this->width, $this->height);
        imagecopyresampled($imgResampled, $this->img, 0, 0, 0, 0, $this->width, $this->height, $this->width * $this->scale, $this->height * $this->scale);
        imagedestroy($this->img);
        $this->img = $imgResampled;
    }

    /**
     * File generation.
     */
    protected function writeImage()
    {
        if ($this->imageFormat === 'png' && function_exists('imagepng')) {
            header('Content-type: image/png');
            imagepng($this->img);
        } else {
            header('Content-type: image/jpeg');
            imagejpeg($this->img, null, 80);
        }
    }

    /**
     * cleanup.
     */
    protected function cleanup()
    {
        imagedestroy($this->img);
    }
}
