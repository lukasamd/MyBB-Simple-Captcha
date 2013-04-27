/**
 * This file is part of Simple Captcha plugin for MyBB.
 * Copyright (C) 2010-2013 Lukasz Tkacz <lukasamd@gmail.com>
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

var simpleCaptcha = {
    refresh: function()
    {
        var imagehash = $('imagehash').value;
        this.spinner = new ActivityIndicator("body", {image: imagepath + "/spinner_big.gif"});
        new Ajax.Request('xmlhttp.php?action=refreshSimpleCaptcha&imagehash='+imagehash, {
        	method: 'get',
        	onComplete: function(request) { simpleCaptcha.refresh_complete(request); }
        });
        return false;
    },

    refresh_complete: function(request)
    {
        var oldHash = $('specialHash').value;
        $(oldHash).value = '';
        $(oldHash).className = 'textbox';
        
        if(request.responseText.match(/<error>(.*)<\/error>/))
        {
            message = request.responseText.match(/<error>(.*)<\/error>/);
            
            if(!message[1])
            {
                message[1] = "An unknown error occurred.";
            }
            
            alert('There was an error fetching the new captcha.\n\n'+message[1]);
        }
        else if(request.responseText)
        {
            var simpleCaptchaData = request.responseText.split("|");
            
            // Delete old validator event and register a new one
            Event.stopObserving(oldHash);
            $(oldHash).insert({after: "<script type='text/javascript'>" + simpleCaptchaData[4] + "</script>"});
            
            // Change old captcha field attributes to new
            $(oldHash).setAttribute("name", simpleCaptchaData[1]);
            $(oldHash).setAttribute("size", simpleCaptchaData[2]);
            $(oldHash).setAttribute("id", simpleCaptchaData[1]);
            $('specialHash').value = simpleCaptchaData[1];
            
            // Change captcha img
            $('simpleCaptcha_img').src = "captcha.php?action=regimage&imagehash=" + simpleCaptchaData[3];
            $('imagehash').value = simpleCaptchaData[3];
        }
        
        if(this.spinner)
        {
        	this.spinner.destroy();
        	this.spinner = '';
        }
        
        // Delete old hash info-status div
        $(oldHash + '_status').remove();
	}
};