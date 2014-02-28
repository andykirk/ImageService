ImageService
============

A small service for providing resized images based on query string.

If you link to an image normally, that's the image that's served, but if you add a `?s=X` query 
parameter, then this script will serve a resized version where `X` is the maximum size (width or 
height) of the new image.

The resized image is cached to allow for speedier subsequent loading, but will be re-generated if
the original image has been modified since the cached image was created, so the images are never
out of sync.

View demo: http://demo.gridlight-design.co.uk/image-service.html