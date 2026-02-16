/**
 * Laraberg Addon Bootstrap
 *
 * Reads the category config injected by the @larabergBlocks directive
 * and registers all custom categories in the Gutenberg inserter.
 *
 * Any addon package can register categories via:
 *   app(ClientBlockRegistry::class)->registerCategory('slug', 'Title');
 *
 * The config is expected at window.LarabergBlocks.categories (set
 * as a <script> tag before this file loads).
 */
(function () {
    'use strict';

    var blocks = Laraberg.wordpress.blocks;
    var config = window.LarabergBlocks || {};
    var categories = config.categories || [];

    if (categories.length === 0) {
        console.log('[LarabergBlocks] No custom categories to register');
        return;
    }

    var existing = blocks.getCategories();
    var existingSlugs = {};
    existing.forEach(function (cat) {
        existingSlugs[cat.slug] = true;
    });

    // Build array of new categories (skip any that already exist)
    var newCategories = [];
    categories.forEach(function (cat) {
        if (!existingSlugs[cat.slug]) {
            var entry = { slug: cat.slug, title: cat.title };
            if (cat.icon) {
                entry.icon = cat.icon;
            }
            newCategories.push(entry);
            console.log('[LarabergBlocks] Registered category: ' + cat.title);
        }
    });

    if (newCategories.length > 0) {
        blocks.setCategories(newCategories.concat(existing));
    }

    // Expose the first category slug as default for convenience
    config.category = categories[0].slug;
    window.LarabergBlocks = config;
})();
