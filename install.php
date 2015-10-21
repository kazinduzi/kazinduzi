<?php defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

ini_set('display_errors', 1);
if (!ini_get('safe_mode')) {
    @set_time_limit(150);
}
date_default_timezone_set(@date_default_timezone_get());

$errors = checkRequirements();
$phpCheck = checkPHPVersion();

$mainConf = file(APP_PATH.'/configs/main.php');
if (!empty($_POST['config'])) {
    overwriteMainConfig($mainConf, $_POST['config']);
}
print_r($mainConf);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Kazinduzi CMF &rsaquo; Installation</title>
</head>
    <body>
        <?php if ($phpCheck==false || !empty($errors)) :?>
            <h1>Errors</h1>
            <div class="display-errors">
                <?php
                    print_r($errors);
                    echo PHP_VERSION;
                ?>
            </div>
        <?php else :?>
            <h1>Installing Kazinduzi CMF</h1>
            <div class="">
                <p>First configure the main configuration for the site</p>
                <form method="post" action="" id="form-configu-main">
                    <ul>
                        <li>
                            <label>Site name</label>
                            <input type="text" name="config[site.name]" value=""/>
                        </li>
                        <li>
                            <label>Application name</label>
                            <input type="text" name="config[Application.name]" value=""/>
                        </li>
                        <li>
                            <label>Language</label>
                            <input type="text" name="config[lang]" value=""/>
                        </li>
                        <li>
                            <label>Encoding Charset</label>
                            <input type="text" name="config[charset]" value=""/>
                        </li>
                        <li>
                            <label>Timezone</label>
                            <input type="text" name="config[date.timezome]" value=""/>
                        </li>
                        <li>
                            <p><strong>Controller settings</strong></p>
                            <label>Default controller</label>
                            <input type="text" name="config[default_controller]" value="sample"/>
                            <br/>
                            <label>Default action</label>
                            <input type="text" name="config[default_action]" value="index"/>
                        </li>
                        <li>
                            <input type="submit" value="Save"/>
                        </li>
                    </ul>
                </form>
            </div>
        <?php endif;?>
    </body>
</body>
</html>

<?php
/**
 *
 * @return boolean
 */
function checkPHPVersion(){
    if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
        return true;
    }
    return false;
}

/**
 *
 * @return type
 */
function checkRequirements(){
    $errors = array();
    if (!function_exists('imagecreatetruecolor')) {
        $errors['imageTest'] = '';
    }
    if (!function_exists('mysql_connect') || !function_exists('mysqli_connect')){
        $errors['dbTest'] = true;
    }
    if (!function_exists('xml_parse') || !function_exists('simplexml_load_file')){
        $errors['xmlTest'] = true;
    }
    // Check writtability
    if (!is_writable(APP_PATH.'/configs')){
        $errors['config_dir'] = 'Your configuration directory '.APP_PATH.'/configs does not appear to be writable by the web server.';
    }
    //
    if (!is_writable(__DIR__.'/html')){
        $errors['html_dir'] = 'Your public /html directory does not appear to be writtable.';
    }
    return (array)$errors;
}

/**
 *
 * @param type $mainConf
 * @param type $data
 */
function overwriteMainConfig($mainConf, $data){
    foreach ($data as $key=>$val){
        foreach($mainConf as &$line){
            if (strpos($line, $key) !== false && !empty($val)){
                $line = '$config[\''.$key.'\'] = \''.$val.'\';'."\r\n";
            }
        }
    }
    $str = implode('', $mainConf);
    file_put_contents(APP_PATH.'/configs/main.php', $str);
}
