# Alt Redirect

> Ez pz redirects, simple, spicy and nicey.

## Features

- Redirects to and from
- Set the status of redirects
- Supports headers on redirects
- Regex Redirects
- Imports and Exports
- URI Query String Stripping

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

### Query String Stripping

This is a new feature we've added to remove query strings from URIs before they're processed by the redirect middleware. 

Because redirects are URI-based, an unexpected query string on a link would've cause your redirects to not function.

With this feature, you can strip out troublesome query strings before your redirects are processed.

Following this, you can choose to either add the query string back to the redirect URL, or strip it out entirely.
The default behaviour for this is to add the filtered query strings back after the redirect has been found. 

You can set a query string to be stripped using the "strip" toggle in the Query Strings admin panel.

The Query Strings functionality can be found in the nav as a child underneath the Alt Redirect Addon
```
- Alt Redirect
    - Query Strings <---
```

On this page you can manage the query strings you wish to strip, simply :
- add the key of the query string `( eg 'foo' for ?foo=bar )`
- Select your site
- Hit save

You'll see your query string in the list.

#### Artisan Command

We have provided an Artisan command to force the creation of defaults.

This will "force" create them, so they'll be installed even if the defaults have already been installed.

This is great for if you've deleted the defaults but want them back or if composer didn't install them during the addon install for some reason.

Simple run the command below and confirm when it asks you too :

```bash
php artisan alt-redirect:default-query-strings
```

## Questions etc

Drop us a big shout-out if you have any questions, comments, or concerns. We're always looking to improve our addons, so if you have any feature requests, we'd love to hear them.

Also - check out our other Statamic bits!

### Starter Kits
- [Alt Starter Kit](https://statamic.com/starter-kits/alt-design/alt-starter-kit) 

### Addons
- [Alt SEO Addon](https://github.com/alt-design/Alt-SEO-Addon)
- [Alt Sitemap Addon](https://github.com/alt-design/Alt-Sitemap-Addon)
- [Alt Akismet Addon](https://github.com/alt-design/Alt-Akismet-Addon)
- [Alt Password Protect Addon](https://github.com/alt-design/Alt-Password-Protect-Addon)
- [Alt Cookies Addon](https://github.com/alt-design/Alt-Cookies-Addon)
- [Alt Inbound Addon](https://github.com/alt-design/Alt-Inbound-Addon)
- [Alt Google 2FA Addon](https://github.com/alt-design/Alt-Google-2fa-Addon)

## Postcardware

Send us a postcard from your hometown if you like this addon. We love getting mail from other cool peeps!

Alt Design  
St Helens House  
Derby  
DE1 3EE  
UK  

