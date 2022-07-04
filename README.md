# Blogisthenics

Are you looking for a static site generator with the following features:

- A [SPA](https://en.wikipedia.org/wiki/Single-page_application) using the latest JavaScript Web Components?
- The newest CSS frameworks baked right in?
- Access to the fanciest, most modern templating engine?
- Integration with your favorite framework?

Then this project isn't for you because Blogisthenics doesn't offer any of those things! And never will! What do we give you instead?

- A way to create a boring ol' multi-page application using HTML, Markdown, and a minimal amount of CSS; where clicking on a link makes the whole page refresh. So old school!
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

#### Pages

Pages are `.html`, `.md`, and `.php` files that act as specific content for a path that will be added to your site. Pages are expected to be only partial HTML documents and must define a layout. If a layout is not explicitly defined in the Front Matter of a page we use the default layout from the site configuration.

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

Note the call to `$this->yield()`, when in a layout this is required to output the content being injected. If you attempt to call `$this->yield()` from a non-layout piece of Content an exception will be thrown. Check out the "Templating" section below for more details on using Pages and Layouts.

### Templating

Blogisthenics uses PHP itself as the templating engine. On top of that we add some functionality to allow the following features:

- Nesting an arbitrary level of layouts
- Auto-escaping all values, including ability to escape contextually.
- Providing access to the FrontMatter of the layout and page
- Provide read-only access to the loaded data
- Allow the creation of helper methods for outputting common pieces of content

The majority of the functionality described in this section refers to the `Cspray\Blogisthenics\Context` object. This object is defined as `$this` in your templates. The simplest templates might look something like the following.:

```html
<!-- Stored in the layouts directory with the filename `main.html.php` -->
<!DOCTYPE html>
<html>
    <head>
        <title><?= $this->title ?? 'Blogisthenics README' ?></title>
    </head>
    <body>
        <?= $this->yield() ?>
    </body>
</html>
```

```md
<!-- Stored in the content directory with the filename `index.md` -->
{
    "title": "Home Page"
}

# <?= $this->title ?>

Yep, that's right. The Front Matter is just a JSON object. Slap a new line on the end of that bad boy, then start writing your content. The values available in the page are the values found in your Front Matter.
```

When built, your site would include an `index.html` file that resembles the following:

```html
<!DOCTYPE html>
<html>
    <head>
        <title>Home Page</title>
    </head>
    <body>
        <h1>"Home Page"</h1>

        <p>
            Yep, that's right. The Front Matter is just a JSON object. Slap a new line on the end of that bad boy, then start writing your content. The values available in the page are the values found in your Front Matter.
        </p>
    </body>
</html>
```

#### Template Helpers

Sometimes what you want to do might be too advanced for a static front matter or would be easier to share across many pages and layouts if it was encapsulated in a method you could invoke inside a template. Using the `TemplateHelperProvider` you can add template helper methods easily and then get access to them in your templates.

To utilize template helpers you'll have to implement some PHP code. In addition to whatever your helper does you'll have to make sure it gets integrated with Blogisthenics. Fortunately, that's easy to do thanks to [Annotated Container](https://github.com/cspray/annotated-container). This code should live somewhere in the `src` directory of your Blogisthenics site.

```php
<?php declare(strict_types=1);

namespace Acme\BlogisthenicsDemo;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\TemplateHelperProvider;
use Cspray\Blogisthenics\MethodDelegator;

#[Service]
final class MyTemplateHelperProvider implements TemplateHelperProvider {

    public function addTemplateHelpers(MethodDelegator $methodDelegator) : void {
        $methodDelegator->addMethod('myHelper', function() {
            return 'This is my helper content!';
        });
    }
}
```

Inside your template you can now invoke `$this->myHelper()`!

```md
<!-- some file ending in .md -->
<?= $this->myHelper() ?>
```

#### Auto-Escaping

Since Content can be generated at runtime with Front Matter that could include data from external sources Blogisthenics takes the stance that _all_ of your data should be escaped properly. Utilizing the [laminas/escaper](https://github.com/laminas/escaper) project we automatically escape all values originating from the Context object. Meaning, all of your front matter, method helpers, and loaded JSON data get automatically HTML escaped at the time of output. If you don't want the value from a helper method to be escaped automatically wrap it in the value object `Cspray\Blogisthenics\SafeToNotEncode`.

Future updates will add a template API for contextually aware escaping. In other words, you'll be able to implicitly and explicitly escape a piece of data with awareness of whether it is CSS data, or JS data, or HTML data.

#### Template Formatting

As already mentioned, we support the ability to create pages out of Markdown documents. Specifically, Blogisthenics uses [GitHub Flavored Markdown]() provided by the [league/commonmark]() package. After all, writing a blog in _just_ HTML would start to get pretty old... blog articles are pretty good candidates for Markdown. Instead of baking Markdown support into Blogisthenics somewhere we expose an interface called `Cspray\Blogisthenics\Formatter` that provides an opportunity for a rendered template to have some additional formatting applied. The `Cspray\Blogisthenics\GitHubFlavoredMarkdownFormatter` is the implementation taking care of Markdown. You can implement your own `Formatter` instance if you find Blogisthenics minimalist approach too spartan for you. We'll be taking advantage of Annotated Container to easily integrate your Formatters into Blogisthenics. This code should live somewhere in the `src` directory of your Blogisthenics site.

```php
<?php declare(strict_types=1);

namespace Acme\BlogisthenicsDemo;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Formatter;

#[Service]
final class MyCustomFormatter implements Formatter {

    public function getFormatType() : string {
        return 'my-type';
    }

    public function format(string $contents) : string {
        return 'my formatted ' . $contents;
    }

}
```

Now any template you create that ends in `my-type` or `my-type.php` will be passed to `MyCustomFormatter` before the contents are written to disk.

### Dynamic Content

Sometimes it isn't possible to create all the necessary content ahead of time in static files. You may need to have access to the content of the site or some other information that requires you to provide the Content at runtime. We'll be using Annotated Container to 

### JSON Data

#### Loading Static Files

#### Loading Dynamic Data

## Why?!

I'm a masochist.

Also, when I look at the site generators out there I see a lot of libraries designed to build single-page applications, involve learning a new templating library, or using a language I don't wanna use. When I look at PHP-specific site generators I see stuff that brings in a lot of Laravel and Symfony, along with the templating engines they use. I honestly don't wanna learn these frameworks, or the parts that leak out of the site generator abstraction, when I'm working on my personal site.

Plus, creating things that have already been created, so I can learn how to do it is my jam.
