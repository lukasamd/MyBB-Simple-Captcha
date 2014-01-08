<?php
/**
 * This file is part of Simple Captcha plugin for MyBB.
 * Copyright (C) 2010-2014 Lukasz Tkacz <lukasamd@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
 * Plugin Activator Class
 * 
 */
class simpleCaptchaActivator
{

    private static $tpl = array();

    private static function getTpl()
    {
        global $db;

      	self::$tpl[] = array(
      		"tid"		=> NULL,
      		"title"		=> 'simpleCaptchaRegister',
      		"template"	=> $db->escape_string('<br />
            <fieldset class="trow2">
            <script type="text/javascript" src="jscripts/simpleCaptcha.js"></script>
            <legend><strong>{$lang->image_verification}</strong></legend>
            <table cellspacing="0" cellpadding="{$theme[\'tablespace\']}">
              <tr>
                <td>
                  <span class="smalltext">{$lang->verification_note}</span></td>
                <td rowspan="2" align="center">
                  <img src="captcha.php?action=regimage&amp;imagehash={$simpleCaptchaData[\'imageshash\']}" alt="{$lang->image_verification}" title="{$lang->image_verification}" id="simpleCaptcha_img" />
                  <br />
                  <span style="color: red;" class="smalltext">{$lang->verification_subnote}</span>
                  <script type="text/javascript">
                  <!--
                  	if(use_xmlhttprequest == "1")
                  	{
                  		document.write(\'<br \/><br \/><input type="button" class="button" tabindex="10000" name="refresh" value="{$lang->refresh}" onclick="return simpleCaptcha.refresh();" \/>\');
                  	}
                  // -->
                  </script>
                </td>
              </tr>
              <tr>
                <td>
                  <input type="text" class="textbox" name="{$simpleCaptchaData[\'inputname\']}" id="{$simpleCaptchaData[\'inputname\']}" size="{$simpleCaptchaData[\'inputsize\']}" value="" style="width: 100%;" />
                  <input type="hidden" name="imagehash" value="{$simpleCaptchaData[\'imageshash\']}" id="imagehash" />
                  <input type="hidden" name="{$simpleCaptchaData[\'saltname\']}" value="{$simpleCaptchaData[\'inputname\']}" id="specialHash" />
                </td>
              </tr>
              <tr>
              	<td id="imagestring_status" style="display: none;" colspan="2">&nbsp;</td>
              </tr>
            </table>
            </fieldset>'),
      		"sid"		=> "-1",
      		"version"	=> "1.0",
      		"dateline"	=> time(),
      	);

      	self::$tpl[] = array(
      		"tid"		=> NULL,
      		"title"		=> 'simpleCaptchaPost',
      		"template"	=> $db->escape_string('<tr id="captcha_trow">
            <td class="trow1" valign="top">
              <strong>{$lang->image_verification}</strong>
              <br />
              <span class="smalltext">{$lang->verification_note}</span>
            </td>
            <td class="trow1">
            <script type="text/javascript" src="jscripts/simpleCaptcha.js"></script>
            <table style="width: 300px; padding: 4px;">
              <tr>
                <td>
                  <img src="captcha.php?action=regimage&amp;imagehash={$simpleCaptchaData[\'imageshash\']}" alt="{$lang->image_verification}" title="{$lang->image_verification}" id="simpleCaptcha_img" />
                  <br />
                  <span style="color: red;" class="smalltext">{$lang->verification_subnote}</span>
              		<script type="text/javascript">
              		<!--
              			if(use_xmlhttprequest == "1")
              			{
              				document.write(\'<br \/><br \/><input type="button" class="button" name="refresh" value="{$lang->refresh}" onclick="return simpleCaptcha.refresh();" \/>\');
              			}
              		// -->
              		</script>
                </td>
              </tr>
              <tr>
                <td>
                  <input type="text" class="textbox" name="{$simpleCaptchaData[\'inputname\']}" id="{$simpleCaptchaData[\'inputname\']}" size="{$simpleCaptchaData[\'inputsize\']}" value="" style="width: 100%;" />
                  <input type="hidden" name="imagehash" value="{$simpleCaptchaData[\'imageshash\']}" id="imagehash" />
                  <input type="hidden" name="{$simpleCaptchaData[\'saltname\']}" value="{$simpleCaptchaData[\'inputname\']}" id="specialHash" />
                </td>
              </tr>
            </table>
            </td>
            </tr>'),
      		"sid"		=> "-1",
      		"version"	=> "1.0",
      		"dateline"	=> time(),
      	);
    	
        // Captcha hidden template
      	self::$tpl[] = array(
      		"tid"		=> NULL,
      		"title"		=> 'simpleCaptchaHidden',
      		"template"	=> $db->escape_string('<input type="hidden" name="imagehash" value="{$simpleCaptchaData[\'imagehash\']}" />
            <input type="hidden" name="{$simpleCaptchaData[\'saltname\']}" value="{$simpleCaptchaData[\'inputname\']}" />
            <input type="hidden" name="{$simpleCaptchaData[\'inputname\']}" value="{$simpleCaptchaData[\'imagestring\']}" id="specialHash" />'),
      		"sid"		=> "-1",
      		"version"	=> "1.0",
      		"dateline"	=> time(),
      	);
    }

    public static function activate()
    {
        global $db; 
        self::deactivate();

        for ($i = 0; $i < sizeof(self::$tpl); $i++)
        {
            $db->insert_query('templates', self::$tpl[$i]);
        }
        find_replace_templatesets("member_register", '#{\$regimage}#', "{\$simple_captcha}{\$regimage}");
        find_replace_templatesets("showthread", '#thread\.js#', "simpleCaptchaThread.js");
        
        // Disable standard captcha
        $db->update_query("settings", array("value" => "0"), "name = 'captchaimage'");
    }

    public static function deactivate()
    {
        global $db;
        self::getTpl();

        for ($i = 0; $i < sizeof(self::$tpl); $i++)
        {
            $db->delete_query('templates', "title = '" . self::$tpl[$i]['title'] . "'");
        }

        include MYBB_ROOT . '/inc/adminfunctions_templates.php';
	     find_replace_templatesets("member_register", '#'.preg_quote('{$simple_captcha}').'#', '',0);
	     find_replace_templatesets("showthread", '#simpleCaptchaThread\.js#', "thread.js");
    }     

}
