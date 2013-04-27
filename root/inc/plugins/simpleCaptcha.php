<?php
/**
 *
 * @author Lukasz "LukasAMD" Tkacz
 *
 * @package Simple Captcha
 * @version 2.8.1
 * @copyright (c) Lukasz Tkacz
 * @license Based on CC BY-NC-SA 3.0 with special clause
 *
 */

/**
 * Disallow direct access to this file for security reasons
 * 
 */
if (!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.');
}

/**
 * Disallow direct access to this file for security reasons
 * 
 */
$plugins->objects['simpleCaptcha'] = new simpleCaptcha();

/**
 * Standard MyBB info function
 * 
 */
function simpleCaptcha_info()
{
    global $lang;

    $lang->load("simpleCaptcha");
    
    $lang->simpleCaptchaDesc = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right;">' .
        '<input type="hidden" name="cmd" value="_s-xclick">' . 
        '<input type="hidden" name="hosted_button_id" value="3BTVZBUG6TMFQ">' .
        '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' .
        '<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">' .
        '</form>' . $lang->simpleCaptchaDesc;

    return Array(
        'name' => $lang->simpleCaptchaName,
        'description' => $lang->simpleCaptchaDesc,
        'website' => 'http://lukasztkacz.com',
        'author' => 'Lukasz "LukasAMD" Tkacz',
        'authorsite' => 'http://lukasztkacz.com',
        'version' => '2.0.1',
        'guid' => '5cac17cbd737eccb755d614a60fe19a0',
        'compatibility' => '16*'
    );
}

/**
 * Standard MyBB activation functions 
 * 
 */
function simpleCaptcha_activate()
{
    require_once('simpleCaptcha.tpl.php');
    simpleCaptchaActivator::activate();
}

function simpleCaptcha_deactivate()
{
    require_once('simpleCaptcha.tpl.php');
    simpleCaptchaActivator::deactivate();
}

/**
 * Strict Username Plugin Class 
 * 
 */
class simpleCaptcha
{
    /**
     * Array with captcha data after generate
     */
    private $data = array();
    
    /**
     * Variable to hide captcha if previously was valid
     */
    private $hide = false;

    /**
     * Constructor - add plugin hooks
     */
    public function __construct()
    {
        global $plugins;

        $plugins->hooks["member_register_start"][10]["sc_generateCaptcha"] = array("function" => create_function('', 'global $plugins; $plugins->objects[\'simpleCaptcha\']->generateCaptcha("Register");'));
        $plugins->hooks["member_register_end"][10]["sc_XMLHttpCheck"] = array("function" => create_function('', 'global $plugins; $plugins->objects[\'simpleCaptcha\']->XMLHttpCheck();'));
        $plugins->hooks["datahandler_user_validate"][10]["sc_checkRegister"] = array("function" => create_function('', 'global $plugins; $plugins->objects[\'simpleCaptcha\']->checkRegister();'));
        $plugins->hooks["xmlhttp"][10]["sc_XMLHttpRefresh"] = array("function" => create_function('', 'global $plugins; $plugins->objects[\'simpleCaptcha\']->XMLHttpRefresh();'));
        $plugins->hooks["pre_output_page"][10]["sc_pluginThanks"] = array("function" => create_function('&$arg', 'global $plugins; $plugins->objects[\'simpleCaptcha\']->pluginThanks($arg);'));
    }

    /**
     * Add javascript validator in register view to auto-validate field
     */
    public function XMLHttpCheck()
    {
        global $lang, $validator_extra;

        $validator_extra .= "\tregValidator.register('{$this->data['inputname']}', 'ajax', {url:'xmlhttp.php?action=validate_captcha', extra_body: 'imagehash', loading_message:'{$lang->js_validator_captcha_valid}', failure_message:'{$lang->js_validator_no_image_text}'});\n";
    }

    /**
     * Refresh simple captcha using ajax
     */
    public function XMLHttpRefresh()
    {
        global $db, $lang, $mybb;

        if ($mybb->input['action'] != "refreshSimpleCaptcha")
        {
            return;
        }

        $lang->load("member");

        if (!$this->validateCaptchaXMLHttp())
        {
            xmlhttp_error($lang->captcha_not_exists);
        }
        $this->generateCaptcha();

        if (!in_array('Content-type', headers_list()))
        {
            header("Content-type: text/plain; charset={$lang->settings['charset']}");
        }
        
        echo $this->data['saltname'] . '|' . $this->data['inputname'] . '|' . $this->data['inputsize'] . '|' . $this->data['imageshash'] .
        "|regValidator.register('" . $this->data['inputname'] . "', 'ajax', {url:'xmlhttp.php?action=validate_captcha', extra_body: 'imagehash', loading_message:'{$lang->js_validator_captcha_valid}', failure_message:'{$lang->js_validator_no_image_text}'});";
        die();
    }

    /**
     * Validate captcha on register
     */
    public function checkRegister()
    {
        global $db, $mybb, $templates, $simple_captcha, $theme, $lang, $errors;

        if (THIS_SCRIPT != 'member.php')
        {
            return;
        }

        if (!$this->validateCaptcha())
        {
            $errors[] = $lang->error_regimageinvalid;
        }
    }

    /**
     * Validate captcha on new post or thread
     */
    public function checkNewPost()
    {
        global $mybb, $lang, $post_errors;
        
        if ($mybb->user['uid'] > 0)
        {
            return;
        }

        $ajax_mode = ($mybb->input['ajax']) ? true : false;

        if (!$this->validateCaptcha($ajax_mode))
        {
            $post_errors[] = $lang->invalid_captcha;  
        }
        else
        {
            $this->hide = true;
        }
        
        if ($ajax_mode)
        {
            $this->generateXMLHttp();
        }
        
        return;
    }
    
    /**
     * Generate captcha for ajax request in new post
     */
    private function generateXMLHttp()
    {
        $this->generateCaptcha();
        
        if (!in_array('Content-type', headers_list()))
        {
            global $lang;
            header("Content-type: text/html; charset={$lang->settings['charset']}");
        }

        echo '<captcha>';
        echo "{$this->data['saltname']}|{$this->data['inputname']}|{$this->data['inputsize']}|{$this->data['imageshash']}|";
        echo ($this->hide) ? $this->data['imagestring'] : '0';
        echo '</captcha>';
    }

    /**
     * Generate captcha on new post or thread
     */
    public function generateNewPost()
    {
        if (!$this->hide)
        {
            $this->generateCaptcha('Post');
        }
        else
        {
            $this->generateCaptcha('Hidden');
        }
    }

    /**
     * Validate captcha on member login
     */
    public function checkLogin()
    {
        global $lang, $errors, $do_captcha;

        if ($do_captcha && !$this->validateCaptcha())
        {
            $errors[] = $lang->error_regimageinvalid;
        }
    }

    /**
     * Generate captcha on member login
     */
    public function generateLogin()
    {
        global $do_captcha;

        if ($do_captcha == true)
        {
            $this->generateCaptcha('Post');
        }
    }

    /**
     * Validate captcha main function
     * 
     * @param bool $ajax Ajax mode, if yes, don't delete captcha from db
     * @return int Valid on invalid    
     */
    private function validateCaptcha($ajax = false)
    {
        global $db, $mybb;
        
        if ($mybb->user['uid'] > 0)
        {
            return 1;
        }

        $imagehash = $db->escape_string($mybb->input['imagehash']);
        $fieldHash = sha1($mybb->settings['adminemail'] . date('d'));
        $inputHash = trim($mybb->input[$fieldHash]);
        $inputCode = trim($mybb->input[$inputHash]);

        if (!$inputCode)
        {
            return false;
        }

        $result = $db->simple_select("captcha", "*", "imagehash='{$imagehash}' AND imagestring='{$inputCode}'");
        $num_rows = (int) $db->num_rows($result);

        if (!$ajax || ($ajax && $num_rows))
        {
            $db->delete_query("captcha", "imagehash='{$imagehash}'");
        }

        if ($num_rows)
        {
            $this->hide = true;
        }

        return $num_rows;
    }

    /**
     * Validate captcha for Ajax - only for hidden captcha
     * 
     * @return int Valid on invalid    
     */
    private function validateCaptchaXMLHttp()
    {
        global $mybb, $db;

        $imagehash = $db->escape_string($mybb->input['imagehash']);
        $result = $db->simple_select("captcha", "*", "imagehash='$imagehash'");
        return (int) $db->num_rows($result);
    }

    /**
     * Generate captcha - main function
     * 
     * @param string $tpl Template name for eval if needed 
     */
    public function generateCaptcha($tpl = '')
    {
        global $simple_captcha, $db, $lang, $mybb, $templates;

        if ($mybb->user['uid'] > 0)
        {
            return;
        }

        $this->data = array(
            'imageshash' => md5(random_str(12)),
            'inputname' => sha1(time() . rand()),
            'inputsize' => rand(6, 12),
            'saltname' => sha1($mybb->settings['adminemail'] . date('d')),
        );

        $sql_array = array(
            "imagehash" => $this->data['imageshash'],
            "imagestring" => random_str(5),
            "dateline" => TIME_NOW
        );
        $db->insert_query("captcha", $sql_array);
        $this->data = array_merge($this->data, $sql_array);

        if ($tpl != '')
        {
            $simpleCaptchaData = $this->data;
            eval("\$simple_captcha = \"" . $templates->get("simpleCaptcha{$tpl}") . "\";");
        }
    }

    /**
     * Say thanks to plugin author - paste link to author website.
     * If you didn't make donate, don't remove this code
     * otherwise you're breaking the license     
     */
    public function pluginThanks(&$content)
    {
        global $session, $lukasamd_thanks;
        
        if (!isset($lukasamd_thanks) && $session->is_spider)
        {
            $thx = '<div style="margin:auto; text-align:center;">This forum uses <a href="http://lukasztkacz.com">Lukasz Tkacz</a> MyBB addons.</div></body>';
            $content = str_replace('</body>', $thx, $content);
            $lukasamd_thanks = true;
        }
    }
}
