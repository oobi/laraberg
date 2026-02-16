<p align="center"><img height="300px" src="./logo-text.svg" alt="logo"></p>

# Laraberg

A Gutenberg block editor implementation for Laravel, updated to **Gutenberg v22.4.1**.

> **Note:** This package is based on the excellent work done by [Maurice Wijnia](https://github.com/VanOns/laraberg) at [Van Ons](https://van-ons.nl/). The original [`van-ons/laraberg`](https://github.com/VanOns/laraberg) package has been abandoned and is no longer maintained. This fork continues development with updated Gutenberg packages, React 18 support, and ongoing improvements.

## Quick start

### Requirements

| Dependency | Minimum version |
|------------|-----------------|
| PHP        | 8.1             |

### Installation

Install the package using Composer:

```bash
composer require oobi/laraberg
```

Add the vendor files to your project (CSS, JS & config):

```bash
php artisan vendor:publish --provider="Oobi\Laraberg\LarabergServiceProvider"
```

#### JavaScript and CSS files

The package provides a JS and CSS file that should be present on the page you
want to use the editor on:

```html
<link rel="stylesheet" href="{{ asset('vendor/laraberg/css/laraberg.css') }}">

<script src="{{ asset('vendor/laraberg/js/laraberg.js') }}"></script>
```

#### Dependencies

The Gutenberg editor expects React, ReactDOM, Moment and JQuery to be in the
environment it runs in. An easy way to do this would be to add the following
lines to your page:

```html
<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>

<script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
```

### Usage

#### Initializing the Editor

The Gutenberg editor should replace an existing textarea in a form. On submit, the
raw content from the editor will be put in the `value` attribute of this textarea:

```html
<textarea id="[id_here]" name="[name_here]" hidden></textarea>
```

In order to edit the content on an already existing model, we have to set the value
of the textarea to the raw content that the Gutenberg editor provided:

```html
<textarea id="[id_here]" name="[name_here]" hidden>{{ $model->content }}</textarea>
```

To initialize the editor, all we have to do is call the initialize method with
the ID of the textarea. You probably want to do this inside a `DOMContentLoaded` event.

And that's it! The editor will replace the textarea in the DOM, and on a form
submit the editor content will be available in the textarea's value attribute.

```js
Laraberg.init('[id_here]')
```

#### Configuration options

The `init()` function takes an optional configuration object which can be used
to change Laraberg's behaviour in some ways:

```js
const options = {}
Laraberg.init('[id_here]', options)
```

The `options` object should be an `EditorSettings` object:

```typescript
interface EditorSettings {
    height?: string;
    mediaUpload?: (upload: MediaUpload) => void;
    fetchHandler?: FetchHandler;
    disabledCoreBlocks?: string[];
    alignWide?: boolean;
    supportsLayout?: boolean;
    maxWidth?: number;
    imageEditing?: boolean;
    colors?: Color[];
    gradients?: Gradient[];
    fontSizes?: FontSize[];
}
```

#### Models

In order to add the editor content to a model, Laraberg provides the
`RendersContent` trait:

```php
use Oobi\Laraberg\Traits\RendersContent;

class MyModel extends Model
{
    use RendersContent;
}
```

This adds the `render` method to your model, which takes care of rendering the
raw editor content. By default, the `render` method renders the content in the
`content` column. This column can be changed by setting the `$contentColumn`
property on your model to the column that you want to use instead:

```php
use Oobi\Laraberg\Traits\RendersContent;

class MyModel extends Model
{
    use RendersContent;

    protected $contentColumn = 'my_column';
}
```

You can also pass the column name to the render method:

```php
$model->render('my_column');
```

#### Custom Blocks

Gutenberg allows developers to create custom blocks. For information on how to
create a custom block you should read the
[Gutenberg documentation].

Registering custom blocks is fairly easy. A Gutenberg block requires the
properties `title`, `icon` and `categories`. It also needs to implement the
functions `edit()` and `save()`:

```js
const myBlock =  {
  title: 'My First Block!',
  icon: 'universal-access-alt',
  category: 'my-category',

  edit() {
    return <h1>Hello editor.</h1>
  },

  save() {
    return <h1>Hello saved content.</h1>
  }
}

Laraberg.registerBlockType('my-namespace/my-block', myBlock)
```

##### Server-side blocks

Server-side blocks can be registered in Laravel. You probably want to create a
ServiceProvider and register your server-side blocks in its `boot` method:

```php
class BlockServiceProvider extends ServiceProvider
{
    public function boot() {
        Laraberg::registerBlockType(
            'my-namespace/my-block',
            [],
            function ($attributes, $content) {
                return view('blocks.my-block', compact('attributes', 'content'));
            }
        );
    }
}
```

#### WordPress exports

Laraberg uses the WordPress Gutenberg packages under the hood. A lot of these
packages expose functionality that lets you customize the editor. You can access these packages
in Javascript using the global `Laraberg` object.

- `Laraberg.wordpress.blockEditor`
- `Laraberg.wordpress.blocks`
- `Laraberg.wordpress.components`
- `Laraberg.wordpress.data`
- `Laraberg.wordpress.element`
- `Laraberg.wordpress.hooks`
- `Laraberg.wordpress.serverSideRender`

### Global Styles & theme.json

Laraberg includes a PHP-based Global Styles system that mirrors WordPress's
`WP_Theme_JSON` pipeline. It generates CSS from a `theme.json` configuration
file — providing preset CSS variables, layout constraints, admin color scheme
variables, and preset utility classes.

#### Setup

Add the `@larabergGlobalStyles` Blade directive to your editor view's `<head>`:

```blade
<head>
    @larabergGlobalStyles
    <link rel="stylesheet" href="{{ asset('vendor/laraberg/css/laraberg.css') }}">
</head>
```

This renders a `<link>` tag pointing to `/laraberg/css/global-styles.css`,
which is automatically served by Laraberg's route. The editor JavaScript
auto-discovers this tag and injects the styles into the iframe.

#### What it generates

The generated CSS includes:

- **Admin color scheme variables** — `--wp-admin-theme-color`, accent and RGB variants
- **Preset CSS variables** — colors, gradients, font sizes, duotone filters, spacings
- **Layout constraint rules** — content width, wide width, and alignment styles
- **Root container padding** — canvas padding for the editor
- **Preset utility classes** — `.has-{slug}-color`, `.has-{slug}-background-color`, `.has-{slug}-font-size`, etc.

#### Customizing with theme.json

Laraberg ships with core defaults in its own `config/theme.json`. To customize
these settings — for example, to change content width, add custom colors, or
define font sizes — create an override file in your application.

The system checks these locations in order, using the first one found:

1. **`config/laraberg-theme.json`** (recommended)
2. **`theme.json`** in your project root

Create your override file with only the settings you want to change. Values are
deep-merged over the core defaults — you don't need to duplicate the entire
file:

```json
{
    "version": 3,
    "settings": {
        "layout": {
            "contentSize": "720px",
            "wideSize": "1100px"
        },
        "color": {
            "palette": [
                { "name": "Primary", "slug": "primary", "color": "#1e40af" },
                { "name": "Secondary", "slug": "secondary", "color": "#9333ea" },
                { "name": "Dark", "slug": "dark", "color": "#1f2937" },
                { "name": "Light", "slug": "light", "color": "#f9fafb" }
            ]
        },
        "typography": {
            "fontSizes": [
                { "name": "Small", "slug": "small", "size": "0.875rem" },
                { "name": "Normal", "slug": "normal", "size": "1rem" },
                { "name": "Large", "slug": "large", "size": "1.5rem" },
                { "name": "Huge", "slug": "huge", "size": "2.25rem" }
            ]
        }
    }
}
```

> **Note:** Array-based settings like `palette` and `fontSizes` are replaced
> entirely (not merged item-by-item). Include all values you want in your array.

##### Layout settings

The `settings.layout` object controls content width constraints in the editor:

| Property      | Default  | Description                                              |
|---------------|----------|----------------------------------------------------------|
| `contentSize` | `800px`  | Max width for standard blocks (paragraphs, headings, etc) |
| `wideSize`    | `1200px` | Max width for blocks using the "Wide width" alignment     |

Blocks set to "Full width" alignment ignore both values and stretch to the
container edge.

##### Admin color scheme

The admin color scheme can be set in `config/laraberg.php`:

```php
'admin_color' => 'fresh', // Options: fresh, light, modern, blue, coffee, ectoplasm, midnight, ocean, sunrise
```

#### Clearing the cache

The Global Styles CSS is cached on first generation within each request. If you
change `theme.json` or `config/laraberg.php` in a running application, the CSS
updates automatically on the next request. To force a refresh in tests or
commands, call:

```php
\Oobi\Laraberg\Services\GlobalStyles\ThemeJson::flush();
```

## Contributing

Please see [contributing] for more information about how you can contribute.

## Changelog

Please see [changelog] for more information about what has changed recently.

## Upgrading

Please see [upgrading] for more information about how to upgrade.

## Security

Please see [security] for more information about how we deal with security.

## Credits

This package is a fork of [`van-ons/laraberg`](https://github.com/VanOns/laraberg), originally created by [Maurice Wijnia](https://github.com/mauricewijnia) at [Van Ons](https://van-ons.nl/). We are grateful for the foundation they built.

- [Van Ons](https://van-ons.nl/) — Original package authors
- [All Contributors][all-contributors]

## License

The scripts and documentation in this project are released under the [GPL-3.0 License][license].

[Gutenberg documentation]: https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/writing-your-first-block-type/
[contributing]: CONTRIBUTING.md
[changelog]: CHANGELOG.md
[upgrading]: UPGRADING.md
[security]: SECURITY.md
[all-contributors]: ../../contributors
[license]: LICENSE.md
