import EditorSettings from '@oobi/block-editor/dist/interfaces/editor-settings'
import { initializeEditor } from '@oobi/block-editor'
import defaultSettings, { getLarabergCssUrl } from './default-settings'

export const init = (
    target: string | HTMLInputElement | HTMLTextAreaElement,
    settings: EditorSettings = {}
) => {
    let element

    if (typeof target === 'string') {
        element = document.getElementById(target) || document.querySelector(target)
    } else {
        element = target
    }

    if (!element) {
        return
    }

    // Build the resolved assets at init time (DOM is ready)
    const cssUrl = getLarabergCssUrl();
    const resolvedAssets = {
        styles: `<link rel="stylesheet" href="${cssUrl}" />`,
        scripts: ''
    };

    // Merge settings with resolved assets
    const mergedSettings: EditorSettings = {
        ...defaultSettings,
        ...settings,
        __unstableResolvedAssets: settings.__unstableResolvedAssets || resolvedAssets
    };

    initializeEditor(element, mergedSettings)
}
