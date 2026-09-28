<script>
    if (typeof window.documentPreviewEngine !== 'function') {
        // Shared off-screen canvas for measuring text dimensions without memory leaks
        const _measurementCanvas = document.createElement('canvas');
        const _measurementContext = _measurementCanvas.getContext('2d');

         if (typeof window.toSentenceCase !== 'function') {
            window.toSentenceCase = function (str) {
                if (!str) return str;
                return String(str).toLowerCase().replace(/(^\s*\w|[.!?]\s*\w)/g, c => c.toUpperCase());
            };
        }

        function documentPreviewEngine(fields, values) {
            return {
                fields: fields || [],
                values: values || {},

                displayValue(field) {
                    let value;
                    if (field.source_type === 'system') {
                        value = this.systemValues[field.name] || '';
                    } else if (field.type === 'date' && field.date_mode === 'current') {
                        const now = new Date();
                        value = `${String(now.getMonth() + 1).padStart(2, '0')}/${String(now.getDate()).padStart(2, '0')}/${now.getFullYear()}`;
                    } else {
                        const raw = this.values[field.name];
                        if (raw === null || raw === undefined || raw === '') {
                            return '';
                        }
                        if (field.type === 'date') {
                            const parsed = new Date(raw);
                            value = !Number.isNaN(parsed.getTime())
                                ? `${String(parsed.getMonth() + 1).padStart(2, '0')}/${String(parsed.getDate()).padStart(2, '0')}/${parsed.getFullYear()}`
                                : raw;
                        } else {
                            value = raw;
                        }
                    }
                    if (field.text_case === 'sentence' && value) {
                        value = window.toSentenceCase(value);
                    }
                    return value;
                },
                fieldTextStyle(field) {
                    const value = this.displayValue(field);
                    const fontSize = this.fitFontSize(field, value);
                    const lineHeight = parseFloat(field.line_height || 1.3);
                    const maxLines = parseInt(field.max_lines || 0, 10);
                    const maxHeight = maxLines > 0 ? (maxLines * fontSize * lineHeight) + 8 : null;
                    const isParagraph = field.type === 'paragraph';

                    let caseCss = '';
                    if (field.text_case === 'uppercase') caseCss = 'text-transform: uppercase;';
                    else if (field.text_case === 'smallcaps') caseCss = 'text-transform: lowercase; font-variant: small-caps;';

                    return `
                        font-family: '${field.font_family}', sans-serif;
                        font-size: ${fontSize}px;
                        font-weight: ${field.font_weight};
                        color: ${field.text_color};
                        text-align: ${field.alignment};
                        line-height: ${lineHeight};
                        letter-spacing: ${field.letter_spacing}px;
                        display: block;
                        width: 100%;
                        overflow: hidden;
                        overflow-wrap: ${isParagraph ? 'break-word' : 'normal'};
                        word-break: ${isParagraph ? 'break-word' : 'normal'};
                        white-space: ${isParagraph ? 'pre-wrap' : 'nowrap'};
                        text-overflow: ${isParagraph ? 'clip' : 'ellipsis'};
                        max-height: ${maxHeight ? `${maxHeight}px` : 'none'};
                        ${caseCss}
                    `;
                },

                fieldBoxStyle(field) {
                    const baseHeight = parseFloat(field.height || 0);
                    const fontSize = this.fitFontSize(field, this.displayValue(field));
                    const lineHeight = parseFloat(field.line_height || 1.3);
                    const maxLines = parseInt(field.max_lines || 0, 10);
                    const computedHeight = maxLines > 0
                        ? Math.max(baseHeight, (maxLines * fontSize * lineHeight) + 8)
                        : baseHeight;

                    return `
                        left: ${field.x}px;
                        top: ${field.y}px;
                        width: ${field.width}px;
                        min-height: ${computedHeight}px;
                        height: ${computedHeight}px;
                        box-sizing: border-box;
                        background: transparent;
                        overflow: visible;
                    `;
                },

                fitFontSize(field, value) {
                    const baseSize = parseFloat(field.font_size || 12);
                    const minSize = 6;
                    if (field.type === 'paragraph') return baseSize;

                    const width = Math.max((field.width || 0) - 16, 10);
                    const lineHeightRatio = parseFloat(field.line_height || 1.3);
                    const maxLines = parseInt(field.max_lines || 0, 10);
                    const heightFromLines = maxLines > 0 ? (maxLines * baseSize * lineHeightRatio) : 0;
                    const height = Math.max(Math.max((field.height || 0) - 8, heightFromLines), 10);

                    let text = `${value || ''}`.trim();
                    if (field.text_case === 'uppercase' || field.text_case === 'smallcaps') {
                        text = text.toUpperCase();
                    }
                    if (!text) return baseSize;

                    for (let size = baseSize; size >= minSize; size -= 0.5) {
                        if (this.textFits(field, text, width, height, size)) return size;
                    }
                    return minSize;
                },

                textFits(field, text, width, height, fontSize) {
                    const context = _measurementContext;
                    const weight = field.font_weight || 'normal';
                    const family = field.font_family || 'Arial';
                    const lineHeightRatio = parseFloat(field.line_height || 1.3);
                    const letterSpacing = parseFloat(field.letter_spacing || 0);
                    const maxLines = parseInt(field.max_lines || 0, 10);

                    context.font = `${weight} ${fontSize}px ${family}`;
                    const paragraphs = text.split(/\r\n|\r|\n/);
                    const lines = [];

                    for (const paragraph of paragraphs) {
                        const words = paragraph.split(/\s+/).filter(Boolean);
                        if (!words.length) { lines.push(''); continue; }

                        let current = '';
                        for (const word of words) {
                            const candidate = current ? `${current} ${word}` : word;
                            const candidateWidth = context.measureText(candidate).width + (candidate.length * letterSpacing);
                            if (candidateWidth <= width || !current) { current = candidate; continue; }
                            lines.push(current);
                            current = word;
                        }
                        if (current) lines.push(current);
                    }

                    const totalHeight = lines.length * fontSize * lineHeightRatio;
                    if (totalHeight > height) return false;
                    if (maxLines > 0 && lines.length > maxLines) return false;

                    return lines.every((line) => {
                        const measured = context.measureText(line).width + (line.length * letterSpacing);
                        return measured <= width;
                    });
                }
            };
        };
    }
</script>