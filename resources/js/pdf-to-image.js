/**
 * PDF -> image, in the browser, for template uploads. pdf.js is loaded only when a PDF is picked.
 *
 *   const pdf = await window.DocumatePdf.open(file);
 *   pdf.numPages;                       // how many pages
 *   await pdf.thumbnail(1, 160);        // data URL, for a page picker
 *   pdf.paper(1);                       // { size: 'A4' | 'Letter' | 'Legal' | 'A3' | 'Custom', orientation, widthCm, heightCm }
 *   await pdf.toImageFile(1);           // a JPEG File of that page (about 200 DPI, kept under the upload limit)
 */

let pdfjsPromise = null;

function loadPdfJs() {
    if (!pdfjsPromise) {
        pdfjsPromise = Promise.all([
            import('pdfjs-dist'),
            import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
        ]).then(([pdfjs, worker]) => {
            pdfjs.GlobalWorkerOptions.workerSrc = worker.default;
            return pdfjs;
        });
    }
    return pdfjsPromise;
}

// Paper sizes in PDF points (1/72 in), portrait.
const SIZES = {
    A4: [595, 842],
    Letter: [612, 792],
    Legal: [612, 1008],
    A3: [842, 1191],
};

const MAX_BYTES = 1.9 * 1024 * 1024; // the server takes 2 MB per upload

function canvasToBlob(canvas, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
}

async function renderPage(page, scale) {
    const viewport = page.getViewport({ scale });
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(viewport.width);
    canvas.height = Math.round(viewport.height);
    const ctx = canvas.getContext('2d');
    // White paper behind transparent PDFs, so the JPEG is not black.
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    await page.render({ canvasContext: ctx, viewport }).promise;
    return canvas;
}

export async function open(file) {
    const pdfjs = await loadPdfJs();
    const data = new Uint8Array(await file.arrayBuffer());
    const doc = await pdfjs.getDocument({ data }).promise;
    const baseName = (file.name || 'document').replace(/\.pdf$/i, '');

    const sizes = {};
    async function pageSize(n) {
        if (!sizes[n]) {
            const page = await doc.getPage(n);
            const vp = page.getViewport({ scale: 1 });
            sizes[n] = { w: vp.width, h: vp.height };
        }
        return sizes[n];
    }

    return {
        numPages: doc.numPages,
        name: baseName,

        async thumbnail(n, width = 160) {
            const page = await doc.getPage(n);
            const vp = page.getViewport({ scale: 1 });
            const canvas = await renderPage(page, width / vp.width);
            return canvas.toDataURL('image/jpeg', 0.8);
        },

        /** The page's paper: a standard size when it matches (within 2%), else Custom in cm. */
        async paper(n) {
            const { w, h } = await pageSize(n);
            const orientation = w > h ? 'landscape' : 'portrait';
            const [short, long] = [Math.min(w, h), Math.max(w, h)];
            for (const [name, [sw, sh]] of Object.entries(SIZES)) {
                if (Math.abs(short - sw) / sw < 0.02 && Math.abs(long - sh) / sh < 0.02) {
                    return { size: name, orientation, widthCm: null, heightCm: null };
                }
            }
            // custom_width / custom_height are in cm, portrait-first like the presets
            const cm = (pt) => Math.round((pt / 72) * 2.54 * 10) / 10;
            return { size: 'Custom', orientation, widthCm: cm(short), heightCm: cm(long) };
        },

        /** The page as a JPEG File: 200 DPI, stepping down until it fits the upload limit. */
        async toImageFile(n) {
            const page = await doc.getPage(n);
            let dpi = 200;
            let quality = 0.9;
            for (let attempt = 0; attempt < 6; attempt++) {
                const canvas = await renderPage(page, dpi / 72);
                const blob = await canvasToBlob(canvas, quality);
                if (blob && blob.size <= MAX_BYTES) {
                    const suffix = doc.numPages > 1 ? `-page-${n}` : '';
                    return new File([blob], `${baseName}${suffix}.jpg`, { type: 'image/jpeg' });
                }
                if (quality > 0.75) quality -= 0.08; else dpi = Math.round(dpi * 0.85);
            }
            throw new Error('This page is too detailed to convert under 2 MB.');
        },

        destroy() {
            doc.destroy();
        },
    };
}

window.DocumatePdf = { open };

/**
 * The paper for a picture of a form: the standard size whose shape matches (within 2.5%), otherwise a custom
 * size with the picture's own proportions (21 cm on the short side, like A4).
 */
export function paperFromSize(w, h) {
    if (!w || !h) return null;
    const orientation = w > h ? 'landscape' : 'portrait';
    const ratio = Math.max(w, h) / Math.min(w, h);
    const standard = [['A4', 297 / 210], ['Letter', 11 / 8.5], ['Legal', 14 / 8.5]];
    for (const [size, r] of standard) {
        if (Math.abs(ratio - r) / r < 0.025) return { size, orientation, widthCm: null, heightCm: null };
    }
    return { size: 'Custom', orientation, widthCm: 21, heightCm: Math.round(21 * ratio * 10) / 10 };
}

window.DocumatePaper = {
    fromSize: paperFromSize,
    fromImage(file) {
        return new Promise((resolve) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => { resolve(paperFromSize(img.naturalWidth, img.naturalHeight)); URL.revokeObjectURL(url); };
            img.onerror = () => { resolve(null); URL.revokeObjectURL(url); };
            img.src = url;
        });
    },
};
