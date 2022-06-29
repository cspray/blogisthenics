# Blogisthenics

A site generator written in PHP. Are you looking for a static site generator with the following features:

- A [SPA](https://en.wikipedia.org/wiki/Single-page_application) using the latest JavaScript Web Components?
- The newest CSS frameworks baked right in?
- Access to the fanciest, most modern templating engine?
- Integration with your favorite framework?

Then this project isn't for you because Blogisthenics doesn't offer any of those things! And never will! What do we give you instead?

- A way to create a boring ol' [MPA]() using Markdown and HTML, where clicking on a link makes the whole page refresh. So old school!
- A powerful, no-frills templating engine that's been in use for over 20 years. PHP itself!
- Absolutely no JavaScript or related tooling out-of-the-box. I prefer to have just 1 shitty language in my site generators, thank-you-very-much!

## Usage Guide

Oh, shit. You're still here? In all honesty, you probably shouldn't use this software! There's just an ass-load of site generators out there and nearly all of them are going to be more supported than whatever this ball of crap turns into. The stuff I write below is mostly for my own benefit so when I come back here in 6 months I can figure out what the hell is going on.

### Directory Structure

Blogisthenics follows a principle that we provide reasonable defaults for your site configuration, but can override any of the defaults to customize your installation. We suggest that you have a directory structure that resembles the following:

```
/.blogisthenics
    config.json         # Configure this library, if not provided default values will be utilized
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


