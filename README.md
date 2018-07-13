# Jasg

Just a site generator (Jasg) written on top of PHP 7+ and [Amp], its primary use case being the creation of my personal blog at 
[cspray.github.io]. Though the primary use case is a blog there is nothing blog-aware in this library; blog-specific 
functionality is provided by a series of plugins. This makes Jasg suitable for any type of statically generated site.

Many aspects of this library and its supported plugins are inspired by my time creating content with [Jekyll]. Much :heart: 
and respect to the developers of this project. That being said, if you're curious why I decided to create my own static 
site generator instead of using the aforementioned software please checkout [Why not Jekyll?].

> This software is still in alpha phase and may not be feature complete and is likely to have bugs in more complex sites.

## Features

- **Lean and minimalistic.** Out of the box Blogisthenics has a small amount of functionality. All bells and whistles are 
provided by plugins that must be explicitly defined. For more information see the [Usage Guide](#Usage_Guide). Additionally 
we are very stringent about which dependencies we use; there are currently only 4 Composer dependencies and many design 
decisions were made with the idea of limiting the number of dependencies we require.
- **JSON as Front Matter, PHP as templating engine.** I prefer JSON over YAML; complex structures are easier for me to reason 
about and parsing YAML in PHP requires more dependencies. I also believe that for generating simple static sites PHP is 
perfectly reasonable default templating engine. _There are plans to allow the Front Matter type and Templating engines to 
be more dynamic in the future._
- **Asynchronous I/O.** While unlikely to be a noticeable benefit unless your site is incredibly large plugins can take 
advantage of the functionality if network requests are necessary to generate data files. For more information about 
the benefits and usage of async I/O and network requests please read the documentation at [Amp].
- **CLI Tool**. _NYI_

## Usage Guide

> All directory paths below are given assuming that root is the root directory of your Jasg installation.

### Types of Content 

Each file, in each directory, in your Jasg site's root will ultimately be converted to a specific PHP type that represents
the state of the file and determines where it is located in your built site, if the file is to be included at all.

- `Page`

- `StaticAsset`
- `Layout` 

### Site Configuration

All Jasg-powered sites are required to have a configuration file present at `/.jasg/config.json`. This file 
defines your site's configuration and determines a small amount of core functionality, primarily where your template 
layouts are located and what directory you want your site output to. Additionally this may be where you define plugin 
specific configuration, this is talked about in more detail in the _Plugin Guide_.

Here's an example configuration with the default values for Jasg site's created by the CLI tool. Explanations for each 
attribute is provided below:

```json
{
  "layout_directory": "_layouts",
  "output_directory": "_site",
  "default_layout": "default.html"
}
```

**`layout_directory`**

Relative to the site's root directory this is where all of your layout templates are located. For more information 
about layouts please see the "Layouts" section in the _Types of Content_ below.

**`output_directory`**

Relative to the site's root directory this is where your generated site will be output. You shouldn't ever put anything 
into this directory as it will be destroyed and regenerated whenever the site is built. Ultimately this will be a collection 
of HTML, CSS, and JS files.

**`default_layout`**

The name of the default `Layout` that all `Pages` will inherit if they do not specify one in their FrontMatter.

### Author Guide

Writing content with Jasg is intended to be explicit and straightforward. 

### Plugin Guide

## How the sausage is made

## Haven't you heard of Jekyll?

[cspray.github.io]: https://cspray.github.io
[Amp]: https://amphp.org
[Jekyll]: https://jekyllrb.com/