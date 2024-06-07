# Alt Redirect

> Ez pz redirects, simple, spicy and nicey.

## Features

- Redirects to and from
- Set the status of redirects
- Supports headers on redirects
- Regex Redirects
- Imports and Exports

## How to Install

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

``` bash
composer require alt-design/alt-redirect
```

## Basic usage

### Simple redirects
Just take a request to one URL and redirect to a new url, for example

From :
```
/old-page
```
To :
```
/new-page
```

### Regex redirects
These, other hand, allow much richer redirect functionality.   
Lets say you changed a wildcard URL path to be a query parameter on a new page, this can done like so

From :
```
/old-page/(.*)
```
To :
```
/new-page?wildcard=$1
```

the '$x' (where x is a number) elements are arranged in the order the corresponding '(.*)' appeared in the 'From' URL, this allows rearranging the regexed fields in the 'To' URL.

### Headers on redirect
You can add headers to the redirect response using the addon's config file. To get started, publish the config file to your site:

```bash
php artisan vendor:publish --tag=alt-redirect-config
```

In the `headers` property, you can provide an array of headers to be passed to the `redirect` method.

## Questions etc

Drop us a big shout-out if you have any questions, comments, or concerns. We're always looking to improve our addons, so if you have any feature requests, we'd love to hear them.

Also - check out our other addons!
- [Alt SEO Addon](https://github.com/alt-design/Alt-SEO-Addon)
- [Alt Sitemap Addon](https://github.com/alt-design/Alt-Sitemap-Addon)
- [Alt Akismet Addon](https://github.com/alt-design/Alt-Akismet-Addon)
- [Alt Password Protect Addon](https://github.com/alt-design/Alt-Password-Protect-Addon)
- [Alt Cookies Addon](https://github.com/alt-design/Alt-Cookies-Addon)
- [Alt Inbound Addon](https://github.com/alt-design/Alt-Inbound-Addon)

## Postcardware

Send us a postcard from your hometown if you like this addon. We love getting mail from other cool peeps!

Alt Design  
St Helens House  
Derby  
DE1 3EE  
UK  

