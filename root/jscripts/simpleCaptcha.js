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