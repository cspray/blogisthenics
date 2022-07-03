# Blogisthenics

Are you looking for a static site generator with the following features:

- A [SPA](https://en.wikipedia.org/wiki/Single-page_application) using the latest JavaScript Web Components?
- The newest CSS frameworks baked right in?
- Access to the fanciest, most modern templating engine?
- Integration with your favorite framework?

Then this project isn't for you because Blogisthenics doesn't offer any of those things! And never will! What do we give you instead?

- A way to create a boring ol' multi-page application using HTML, Markdown, and CSS; where clicking on a link makes the whole page refresh. So old school!
- Powerful, no-frills templating engine that's been in use for over 20 years. PHP itself!
- Customize your dynamic content with Front Matter... not written in YAML!
- Absolutely no JavaScript or related tooling out-of-the-box. I prefer to have just 1 shitty language in my site generators, thank-you-very-much!
- Cohesive, type-safe, testable mechanisms for programmatically controlling the content of your site!

## Usage Guide

Oh, shit. You're still here? In all honesty, you probably shouldn't use this software! There's just an ass-load of site generators out there and nearly all of them are going to be more supported than whatever this ball of crap turns into. The stuff I write below is mostly for my own benefit so when I come back here in 6 months I can figure out what the hell is going on.

### Directory Structure

Blogisthenics follows a principle that there are reasonable defaults for your site configuration, but you can override any of the defaults to customize your installation. We suggest that you have a directory structure that resembles the following:

```
/.blogisthenics
    config.json         # Configure Blogisthenics, if not provided default config will be used
/content                # The actual content for your site goes here
    /assets             # CSS, JS, images ... isn't treated specially, convention to put stuff here
    /blog               # Your blog articles ... could be named whatever you want
    index.md            # Markdown files are ok. Front-matter parsing and layouts are supported
    about.html          # HTML files are ok too, you won't get any parsing or layout support though
    contact.html.php    # Add a PHP extension to enable front-matter parsing and layout support
/data
    ...                 # Store JSON files here to access in the KeyValueStore
/layouts
    main.html.php       # Store PHP template files here to use as layouts
    article.html.php    # Layouts can be nested as deep as you want, but there's probably a logical limit
/src
    ...                 # PHP code for whatever is required to build your site
```

### Content Overview

Content for your site gets lumped into three categories:

- Static Assets
- Layouts
- Pages

#### Static Assets

Static assets are any content in your site that should not be dynamically rendered, whatever is in the file gets copied over exactly, with the same path, when the site is built. Specifically the following functionalities are **not** supported by static assets.

- Front Matter Parsing
- Template processing, i.e. no variables
- Multiple extension formatting support

#### Layouts

Layouts are `.md` and `.php` files that act as the outer chrome for pages. Layouts can be inserted into other layouts. The below example demonstrates a minimal layout, typically named something like `main.html.php` or `default.html.php`.

```html
<!DOCTYPE html>
<html>
    <head>
        <title><?= $this->title ?? 'Blogisthenics' ?></title>
    </head>
    <body>
        <?= $this->yield() ?>
    </body>
</html>
```

Note the call to `$this->yield()`, when in a layout this is required to output the content being injected. If you attempt to call `$this->yield()` from a non-layout piece of Content an exception will be thrown.

#### Pages

Pages are `.html`, `.md`, and `.php` files that act as specific content for a path that will be added to your site. Pages are expected to be only partial HTML documents and must define a layout. If a layout is not explicitly defined in the Front Matter of a page we use the default layout from the SiteConfiguration.

### Programmatic API
