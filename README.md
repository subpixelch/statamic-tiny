Statamic Image Minifier via TinyPNG/TinyJPG API
===============================================

TinyPNG/TinyJPG Image minifier API ADDON for Statamic

This ADD-ON uses the API from www.tinypng.com to minify your JPG or PNG images.
*You need to have an API-Key from www.tinypng.com*

There is a free subscription-plan with no max. file-size and free 500 images minifing per month.

# Install
Copy all files in your statamic _add-ons/ folder

- _add-ons/tiny/pi.tiny.php
- _add-ons/tiny/cacert.pem


#Config
Add this line to your main Statamic settings.yaml file:
```YAML
_tiny_key: "YOUR_TINYPNG_API_KEY"
```

# Use
Use it as you'd use your {{ transform }} tag withing statamic, the only difference is that this only works with JPG/PNG-Files:
```HTML
{{ tiny src="{ your_image}" }}
```

