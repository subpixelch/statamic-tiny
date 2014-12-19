<?php

use Intervention\Image\Image;

/**
 * Plugin_tiny
 * Manipulate and shrink the crap out of your images!
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 * @author  Patrick Barca <patrick.barca@subpixel.ch>
 *
 * @copyright  2014
 * @license    http://statamic.com/license-agreement
 */


class Plugin_tiny extends Plugin
{

    public function index()
    {

        /*
        |--------------------------------------------------------------------------
        | Check for image
        |--------------------------------------------------------------------------
        |
        | Transform just needs the path to an image to get started. If it exists,
        | the fun begins.
        |
        */

        $image_src = $this->fetchParam('src', null, false, false, false);
        
        // Set full system path
        $image_path = Path::standardize(Path::fromAsset($image_src));

        // Check if image exists before doing anything.
        if ( ! File::isImage($image_path)) {

            Log::error("Could not find requested image to tiny: " . $image_path, "core", "tiny");

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Resizing and cropping options
        |--------------------------------------------------------------------------
        |
        | The first transformations we want to run is for size to reduce the
        | memory usage for future effects.
        |
        */

        $width  = $this->fetchParam('width', null, 'is_numeric');
        $height = $this->fetchParam('height', null, 'is_numeric');

        // resize specific
        $ratio  = $this->fetchParam('ratio', true, false, true);
        $upsize = $this->fetchParam('upsize', true, false, true);

        // crop specific
        $pos_x  = $this->fetchParam('pos_x', 0, 'is_numeric');
        $pos_y  = $this->fetchParam('pos_y', 0, 'is_numeric');

        $quality = $this->fetchParam('quality', '100', 'is_numeric');


        /*
        |--------------------------------------------------------------------------
        | Action
        |--------------------------------------------------------------------------
        |
        | Available actions: resize, crop, and guess.
        |
        | "Guess" will find the best fitting aspect ratio of your given width and
        | height on the current image automatically, cut it out and resize it to
        | the given dimension.
        |
        */

        $action = $this->fetchParam('action', 'resize');


        /*
        |--------------------------------------------------------------------------
        | Extra bits
        |--------------------------------------------------------------------------
        |
        | Delicious and probably rarely used options.
        |
        */

        $angle     = $this->fetchParam('rotate', false);
        $flip_side = $this->fetchParam('flip' , false);
        $blur      = $this->fetchParam('blur', false, 'is_numeric');
        $pixelate  = $this->fetchParam('pixelate', false, 'is_numeric');
        $greyscale = $this->fetchParam(array('greyscale', 'grayscale'), false, false, true);
        $watermark = $this->fetchParam('watermark', false, false, false, false);


        /*
        |--------------------------------------------------------------------------
        | Assemble filename and check for duplicate
        |--------------------------------------------------------------------------
        |
        | We need to make sure we don't already have this image created, so we
        | defer any action until we've processed the parameters, which create
        | a unique filename.
        |
        */

        // Late modified time of original image
        $last_modified = File::getLastModified($image_path);

        // Find .jpg, .png, etc
        $extension = File::getExtension($image_path);

        // Filename with the extension removed so we can append our unique filename flags
        $stripped_image_path = str_replace('.' . $extension, '', $image_path);

        // The possible filename flags
        $parameter_flags = array(
            'width'     => $width,
            'height'    => $height,
            'quality'   => $quality,
            'rotate'    => $angle,
            'flip'      => $flip_side,
            'pos_x'     => $pos_x,
            'pos_y'     => $pos_y,
            'blur'      => $blur,
            'pixelate'  => $pixelate,
            'greyscale' => $greyscale,
            'modified'  => $last_modified
        );

        // Start with a 1 character action flag
        $file_breadcrumbs = '-'.$action[0];

        foreach ($parameter_flags as $param => $value) {
            if ($value) {
                $flag = is_bool($value) ? '' : $value; // don't show boolean flags
                $file_breadcrumbs .= '-' . $param[0] . $flag;
            }
        }

        // Allow converting filetypes (jpg, png, gif)
        $extension = $this->fetchParam('type', $extension);

        // Allow saving in a different directory
        $destination = $this->fetchParam('destination', Config::get('transform_destination', false), false, false, false);


        if ($destination) {

            $destination = Path::tidy(BASE_PATH . '/' . $destination);

            // Method checks to see if folder exists before creating it
            Folder::make($destination);

            $stripped_image_path = Path::tidy($destination . '/' . basename($stripped_image_path));
        }

        // Reassembled filename with all flags filtered and delimited
        $new_image_path = $stripped_image_path . $file_breadcrumbs . '.' . $extension;

        // Check if we've already built this image before
        if (File::exists($new_image_path)) {
            return Path::toAsset($new_image_path);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Image
        |--------------------------------------------------------------------------
        |
        | Transform just needs the path to an image to get started. The image is
        | created in memory so we can start manipulating it.
        |
        */

        $image = Image::make($image_path);


        /*
        |--------------------------------------------------------------------------
        | Perform Actions
        |--------------------------------------------------------------------------
        |
        | This is fresh transformation. Time to work the magic!
        |
        */

        if ($action === 'resize' && ($width || $height) ) {
            $image->resize($width, $height, $ratio, $upsize);
        }

        if ($action === 'crop' && $width && $height) {
            $image->crop($width, $height, $pos_x, $pos_y);
        }

        if ($action === 'smart') {
            $image->grab($width, $height);
        }

        $resize  = $this->fetchParam('resize', null);

        if ($resize) {
            $resize_options = Helper::explodeOptions($resize, true);

            $image->resize(
                array_get($resize_options, 'width'),
                array_get($resize_options, 'height'),
                array_get($resize_options, 'ratio', true),
                array_get($resize_options, 'upsize', true)
            );
        }

        $crop = $this->fetchParam('crop', null);

        if ($crop) {
            $crop_options = Helper::explodeOptions($crop, true);

            $image->crop(
                array_get($crop_options, 'width'),
                array_get($crop_options, 'height'),
                array_get($crop_options, 'x'),
                array_get($crop_options, 'y')
            );
        }

        if ($angle) {
            $image->rotate($angle);
        }

        if ($flip_side === 'h' || $flip_side === 'v') {
            $image->flip($flip_side);
        }

        if ($greyscale) {
            $image->greyscale();
        }

        if ($blur) {
            $image->blur($blur);
        }

        if ($pixelate) {
            $image->pixelate($pixelate);
        }

        // Positioning options via ordered pipe settings:
        // source|position|x offset|y offset
        if ($watermark) {
            $watermark_options = Helper::explodeOptions($watermark);

            $source = Path::tidy(BASE_PATH . '/' . array_get($watermark_options, 0, null));
            $anchor = array_get($watermark_options, 1, null);
            $pos_x  = array_get($watermark_options, 2, 0);
            $pos_y  = array_get($watermark_options, 3, 0);

            $image->insert($source, $pos_x, $pos_y, $anchor);
        }


        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        |
        | Get out of dodge!
        |
        */

        try {
            $image->save($new_image_path, $quality);


/* Tiny  */

$key = $this->fetchParam('key', Config::get('tiny_key', false), false, false, false);
$input = $new_image_path;
$output = $new_image_path;

$request = curl_init();
curl_setopt_array($request, array(
  CURLOPT_URL => "https://api.tinypng.com/shrink",
  CURLOPT_USERPWD => "api:" . $key,
  CURLOPT_POSTFIELDS => file_get_contents($input),
  CURLOPT_BINARYTRANSFER => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HEADER => true,
  /* Uncomment below if you have trouble validating our SSL certificate.
     Download cacert.pem from: http://curl.haxx.se/ca/cacert.pem */
  CURLOPT_CAINFO => __DIR__ . "/cacert.pem",
  CURLOPT_SSL_VERIFYPEER => true
));

$response = curl_exec($request);
if (curl_getinfo($request, CURLINFO_HTTP_CODE) === 201) {
  /* Compression was successful, retrieve output from Location header. */
  $headers = substr($response, 0, curl_getinfo($request, CURLINFO_HEADER_SIZE));
  foreach (explode("\r\n", $headers) as $header) {
    if (substr($header, 0, 10) === "Location: ") {
      $request = curl_init();
      curl_setopt_array($request, array(
        CURLOPT_URL => substr($header, 10),
        CURLOPT_RETURNTRANSFER => true,
        /* Uncomment below if you have trouble validating our SSL certificate. */
        CURLOPT_CAINFO => __DIR__ . "/cacert.pem",
        CURLOPT_SSL_VERIFYPEER => true
      ));
      file_put_contents($output, curl_exec($request));
    }
  }
} else {
    print(curl_error($request));
  /* Something went wrong! */
  print("Compression failed");
}

/* Tiny */

        } catch(Exception $e) {
            Log::fatal('Could not write new images. Try checking your file permissions.', 'core', 'tiny');
            throw new Exception('Could not write new images. Try checking your file permissions.');
        }

        return File::cleanURL($new_image_path);

 }
}
