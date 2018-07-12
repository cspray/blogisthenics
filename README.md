# Blogisthenics

A static site generator written on top of PHP and [Amp], its primary use case being the creation of my personal blog at 
[cspray.github.io].

If your first thought upon coming to this repo is why would you write another static site generator please checkout the 
[Haven't you heard of Jekyll?]() section.

> This software is still in alpha phase and may not be feature complete and is likely to have bugs in more complex sites

## Features

- Lean and minimalistic. Out of the box Blogisthenics has a small amount of functionality. All bells and whistles are 
provided by plugins that must be explicitly defined. For more information see the [Usage Guide](#Usage_Guide).
- Asynchronous I/O. While unlikely to be a noticeable benefit unless your site is incredibly large plugins can take 
advantage of the functionality if network requests are necessary to generate data files. For more information about 
the benefits and usage of async I/O and network requests please read the documentation at [Amp].
- A CLI tool for site creation and management.

## Usage Guide

## CLI Tool

## How the sausage is made

## Haven't you heard of Jekyll?

[cspray.github.io]: https://cspray.github.io
[Amp]: https://amphp.org