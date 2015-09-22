# Avatar Service

Provides a service to get an Elgg avatar in a Gravatar like way

## How to use

### Base Request

Avatar images may be requested just like a normal image, using an IMG tag. To get an image specific to a user, you must first calculate their email hash.

The most basic image request URL looks like this:

`http://www.yoursite.com/avatar_service/HASH`

where HASH is replaced with the calculated hash for the specific email address you are requesting. The hash is an md5 of the lowercased version of an emailaddress.

For example, here is the base URL for the info@domain.com emailaddress:

`http://www.yoursite.com/avatar_service/dfb0624735f6756ede0693f1c8e94a8c`

If you require a file-type extension (some places do) then you may also add an (optional) .jpg extension to that URL:

`http://www.yoursite.com/avatar_service/dfb0624735f6756ede0693f1c8e94a8c.jpg`

### Size

By default, images are presented at 80px by 80px if no size parameter is supplied. You may request a specific image size, which will be dynamically delivered by using the s= or size= parameter and passing a single pixel dimension (since the images are square):

`http://www.yoursite.com/avatar_service/dfb0624735f6756ede0693f1c8e94a8c?s=200`

You may request images anywhere from 1px up to 2048px, however note that many users have lower resolution images, so requesting larger sizes may result in pixelation/low-quality images.

### Default Image

What happens when an email address has no matching user or user avatar? You will get the default user icon of the community.