@assets
<script>
    window.toSentenceCase ??= function (str) {
        if (!str) return str;
        return String(str).toLowerCase().replace(/(^\s*\w|[.!?]\s*\w)/g, c => c.toUpperCase());
    };

    window.positionedPreviewEngine ??= function (fields, values, systemValues) {
        return {
            fields: fields || [],
            values: values || {},
            systemValues: systemValues || {},

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
            isMultiline(field) {
                // System values (name, program, etc.) are always single-line and must shrink
                return field.type === 'paragraph' && field.source_type !== 'system';
            },
            fieldTextStyle(field) {
                const value = this.displayValue(field);
                const fontSize = this.fitFontSize(field, value);
                const lineHeight = parseFloat(field.line_height || 1.3);
                const multiline = this.isMultiline(field);
                const maxLines = multiline ? parseInt(field.max_lines || 0, 10) : 0;
                const maxHeight = maxLines > 0 ? (maxLines * fontSize * lineHeight) + 8 : null;

                let caseCss = '';
                if (field.text_case === 'uppercase') caseCss = 'text-transform: uppercase;';
                else if (field.text_case === 'smallcaps') caseCss = 'text-transform: lowercase; font-variant: small-caps;';

                return `
                    font-family: '${field.font_family}', sans-serif;
                    font-size: ${fontSize}px;
                    font-weight: ${field.font_weight};
                    font-style: ${field.font_style || 'normal'};
                    color: ${field.text_color};
                    text-align: ${field.alignment};
                    line-height: ${lineHeight};
                    letter-spacing: ${field.letter_spacing}px;
                    display: block;
                    width: 100%;
                    overflow: hidden;
                    overflow-wrap: ${multiline ? 'break-word' : 'normal'};
                    word-break: ${multiline ? 'break-word' : 'normal'};
                    white-space: ${multiline ? 'pre-wrap' : 'nowrap'};
                    text-overflow: clip;
                    max-height: ${maxHeight ? `${maxHeight}px` : 'none'};
                    ${caseCss}
                `;
            },
            fieldBoxStyle(field) {
                const baseHeight = parseFloat(field.height || 0);
                const fontSize = this.fitFontSize(field, this.displayValue(field));
                const lineHeight = parseFloat(field.line_height || 1.3);
                const maxLines = this.isMultiline(field) ? parseInt(field.max_lines || 0, 10) : 0;
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
                if (this.isMultiline(field)) return baseSize;

                const width = Math.max((field.width || 0) - 16, 10) * 0.98;

                let text = `${value || ''}`.trim();
                if (field.text_case === 'uppercase' || field.text_case === 'smallcaps') {
                    text = text.toUpperCase();
                }
                if (!text) return baseSize;

                for (let size = baseSize; size >= minSize; size -= 0.5) {
                    if (this.singleLineFits(field, text, width, size)) return size;
                }
                return minSize;
            },
            singleLineFits(field, text, width, fontSize) {
                if (!window._measureCtx) {
                    window._measureCtx = document.createElement('canvas').getContext('2d');
                }
                const ctx = window._measureCtx;
                const style  = field.font_style === 'italic' ? 'italic ' : '';
                const weight = field.font_weight || 'normal';
                const family = field.font_family || 'Arial';
                ctx.font = `${style}${weight} ${fontSize}px '${family}'`;
                const letterSpacing = parseFloat(field.letter_spacing || 0);
                return ctx.measureText(text).width + (text.length * letterSpacing) <= width;
            },
            textFits(field, text, width, height, fontSize) {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
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

                    if (!words.length) {
                        lines.push('');
                        continue;
                    }

                    let current = '';
                    for (const word of words) {
                        const candidate = current ? `${current} ${word}` : word;
                        const candidateWidth = context.measureText(candidate).width + (candidate.length * letterSpacing);

                        if (candidateWidth <= width || !current) {
                            current = candidate;
                            continue;
                        }

                        lines.push(current);
                        current = word;
                    }

                    if (current) {
                        lines.push(current);
                    }
                }

                const totalHeight = lines.length * fontSize * lineHeightRatio;
                if (totalHeight > height) {
                    return false;
                }

                if (maxLines > 0 && lines.length > maxLines) {
                    return false;
                }

                return lines.every((line) => {
                    const measured = context.measureText(line).width + (line.length * letterSpacing);
                    return measured <= width;
                });
            }
        };
    }
</script>
@endassets