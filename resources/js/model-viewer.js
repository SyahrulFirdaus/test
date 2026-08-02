/**
 * Entry point terpisah untuk halaman "3D Models".
 * Three.js hanya diunduh di halaman ini, bukan pada seluruh website.
 */
import ModelWorkspace from './modules/model-workspace';
import initQuotationForm from './modules/quotation-form';

const root = document.querySelector('[data-model-viewer]');

if (root) {
    const unsupported = root.querySelector('[data-viewer-unsupported]');

    // Beberapa perangkat/browser lama tidak menyediakan konteks WebGL.
    const webglAvailable = (() => {
        try {
            const canvas = document.createElement('canvas');

            return Boolean(
                window.WebGLRenderingContext &&
                (canvas.getContext('webgl2') || canvas.getContext('webgl'))
            );
        } catch {
            return false;
        }
    })();

    if (webglAvailable) {
        const workspace = new ModelWorkspace(root);
        initQuotationForm(workspace, root);
    } else {
        if (unsupported) {
            unsupported.style.display = 'block';
        }

        const panel = root.querySelector('[data-viewer-panel]');

        if (panel) {
            panel.style.display = 'none';
        }
    }
}
