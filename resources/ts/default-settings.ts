import fetchHandler from './fetch-handler'
import EditorSettings from '@oobi/block-editor/dist/interfaces/editor-settings'

// Helper to get the laraberg CSS URL
// Tries to find an existing link tag, falls back to default path
export function getLarabergCssUrl(): string {
    // Check if there's already a laraberg CSS link on the page
    const existingLink = document.querySelector('link[href*="laraberg.css"]');
    if (existingLink) {
        return (existingLink as HTMLLinkElement).href;
    }
    // Default path
    return '/vendor/laraberg/css/laraberg.css';
}

// Helper to get the global styles CSS URL
// Discovers the link tag output by @larabergGlobalStyles
export function getGlobalStylesCssUrl(): string | null {
    const link = document.querySelector('link[data-laraberg-global-styles]');
    if (link) {
        return (link as HTMLLinkElement).href;
    }
    // Fallback: check for any link containing global-styles.css
    const fallback = document.querySelector('link[href*="global-styles.css"]');
    if (fallback) {
        return (fallback as HTMLLinkElement).href;
    }
    return null;
}

const defaultSettings: EditorSettings = {
    fetchHandler,
    mediaUpload: undefined,
    disabledCoreBlocks: [
        'core/freeform',
        'core/shortcode',
        'core/archives',
        'core/tag-cloud',
        'core/block',
        'core/rss',
        'core/search',
        'core/calendar',
        'core/categories',
        'core/more',
        'core/nextpage'
    ]
    // Note: __unstableResolvedAssets is set dynamically in init.ts
}

export default defaultSettings
